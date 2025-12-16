<?php
/**
 * Üye - Toplantılarım
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();
Middleware::requireModulePermission('uye_toplantilar');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

// Turkish Date Helper
$trMonths = [
    'Jan' => 'Ocak', 'Feb' => 'Şubat', 'Mar' => 'Mart', 'Apr' => 'Nisan',
    'May' => 'Mayıs', 'Jun' => 'Haziran', 'Jul' => 'Temmuz', 'Aug' => 'Ağustos',
    'Sep' => 'Eylül', 'Oct' => 'Ekim', 'Nov' => 'Kasım', 'Dec' => 'Aralık'
];

function formatTextAsList($text) {
    if (empty($text)) return '';
    $escaped = htmlspecialchars($text);
    // Convert newlines to breaks first
    $withBreaks = nl2br($escaped);
    // If user used "- " or "* " or "• " within textual lines without newlines (rare but requested)
    // or just to enforce styling.
    // Let's just rely on nl2br for basic cases, but user asked specifically: "her aufzählungszeichenden sonra yeni satır baslasin"
    // "After every bullet point start a new line". This implies if it's inline like "Item 1 - Item 2", break it?
    // Or just ensuring list items are distinct.
    // Let's try to interpret "- " as a new line starter if it's not already.
    
    // Replace " - " with "<br>- " to force new line for inline bullets
    $formatted = str_replace([' - ', ' • ', ' * '], ['<br>- ', '<br>• ', '<br>* '], $withBreaks);
    
    // Also basic list rendering: ensure lines starting with - get proper spacing
    return $formatted;
}

$pageTitle = 'Toplantılarım';
$durumFiltre = $_GET['durum'] ?? 'yaklasan';
$katilimFiltre = $_GET['katilim'] ?? '';
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$allowedDurumFiltresi = ['yaklasan', 'gecmis', 'tum'];
// Backend valid statuses
$allowedKatilimDurumlari = ['beklemede', 'katilacak', 'katilmayacak', 'mazeret'];

if (!in_array($durumFiltre, $allowedDurumFiltresi, true)) {
    $durumFiltre = 'yaklasan';
}
if ($katilimFiltre && !in_array($katilimFiltre, $allowedKatilimDurumlari, true)) {
    $katilimFiltre = '';
}

$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Oturum doğrulaması başarısız oldu. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'katilim_guncelle') {
            $katilimciId = isset($_POST['katilimci_id']) ? (int) $_POST['katilimci_id'] : 0;
            $yeniDurum = $_POST['katilim_durumu'] ?? 'beklemede';
            $mazeretAciklama = trim($_POST['mazeret_aciklama'] ?? '');
            
            if (!in_array($yeniDurum, $allowedKatilimDurumlari, true)) {
                $errors[] = 'Geçersiz katılım durumu seçildi.';
            } else {
                $katilimci = $db->fetch("
                    SELECT * FROM toplanti_katilimcilar
                    WHERE katilimci_id = ? AND kullanici_id = ?
                ", [$katilimciId, $user['id']]);
                
                if (!$katilimci) {
                    $errors[] = 'Katılım kaydı bulunamadı.';
                } else {
                    // Validation: Mazeret is required if status is 'katilmayacak'
                    if ($yeniDurum === 'katilmayacak' && $mazeretAciklama === '') {
                        $errors[] = 'Lütfen katılamama nedeninizi (mazeret) belirtiniz.';
                    }
                    
                    if (empty($errors)) {
                        $db->query("
                            UPDATE toplanti_katilimcilar
                            SET katilim_durumu = ?, mazeret_aciklama = ?, yanit_tarihi = NOW()
                            WHERE katilimci_id = ?
                        ", [$yeniDurum, $mazeretAciklama ?: null, $katilimciId]);
                        
                        $messages[] = 'Katılım durumunuz güncellendi.';
                    }
                }
            }
        }
    }
}

$conditions = ["tk.kullanici_id = ?"];
$params = [$user['id']];
$orderBy = "t.toplanti_tarihi ASC";

switch ($durumFiltre) {
    case 'gecmis':
        $conditions[] = "t.toplanti_tarihi < NOW()";
        $orderBy = "t.toplanti_tarihi DESC";
        break;
    case 'tum':
        // filtre yok
        break;
    default:
        $conditions[] = "t.toplanti_tarihi >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        break;
}

if ($katilimFiltre) {
    $conditions[] = "tk.katilim_durumu = ?";
    $params[] = $katilimFiltre;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

$toplantilar = $db->fetchAll("
    SELECT t.*, tk.katilim_durumu, tk.katilimci_id, tk.mazeret_aciklama
    FROM toplanti_katilimcilar tk
    INNER JOIN toplantilar t ON tk.toplanti_id = t.toplanti_id
    $whereClause
    ORDER BY $orderBy
    LIMIT 100
", $params);

include __DIR__ . '/../includes/header.php';
?>



<!-- Modern Design Assets -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #009872;
        --primary-light: rgba(0, 152, 114, 0.1);
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --card-bg: rgba(255, 255, 255, 0.9);
        --glass-border: 1px solid rgba(255, 255, 255, 0.5);
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at 0% 0%, rgba(0, 152, 114, 0.08) 0%, transparent 50%),
                    radial-gradient(circle at 100% 100%, rgba(0, 152, 114, 0.05) 0%, transparent 50%),
                    #f8fafc;
        color: var(--text-dark);
    }

    /* CSS Overrides for Mobile Layout Fix */
    .dashboard-layout {
        display: block;
    }

    .sidebar-wrapper {
        display: none;
    }

    .content-wrapper {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        padding: 1rem !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .main-content {
        width: 100%;
    }

    /* Desktop View */
    @media (min-width: 992px) {
        .dashboard-layout {
            display: flex;
            flex-direction: row;
        }

        .sidebar-wrapper {
            display: block;
            width: 250px;
            flex-shrink: 0;
            z-index: 1000;
        }
        
        .main-content {
            flex-grow: 1;
            width: auto;
        }

        .content-wrapper {
            padding: 1.5rem 2rem !important;
            max-width: 1400px !important;
            margin: 0 auto !important;
        }
    }

    .card.meeting-card {
        border: 1px solid rgba(0,152,114,0.1);
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        border-radius: 1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card.meeting-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01);
        border-color: rgba(0,152,114,0.3);
    }

    .date-badge {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        color: var(--primary);
        border-radius: 0.75rem;
        border: 1px solid rgba(0,152,114,0.1);
        min-width: 70px;
        height: 70px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .status-badge.katilacak { background: #dcfce7; color: #166534; }
    .status-badge.katilmayacak { background: #fee2e2; color: #991b1b; }
    .status-badge.mazeret { background: #ffedd5; color: #9a3412; }
    .status-badge.beklemede { background: #f1f5f9; color: #475569; }

    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
    }

    .btn-primary:hover {
        background-color: var(--primary-600);
        border-color: var(--primary-600);
    }

    .filter-btn {
        border-radius: 9999px;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .filter-btn.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(0,152,114,0.3);
    }
    
    .filter-btn:not(.active) {
        background: white;
        color: var(--muted);
        border: 1px solid var(--border);
    }
    
    .filter-btn:not(.active):hover {
        background: #f8fafc;
        color: var(--text);
    }
</style>

<div class="dashboard-layout">
    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
        <!-- Header & Filters -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-4">
            <div>
                <h1 class="h3 fw-bold mb-2 text-dark">
                    Toplantılarım
                </h1>
                <p class="text-muted mb-0">Katılmanız beklenen toplantıları buradan yönetebilirsiniz.</p>
            </div>
            
            <div class="d-flex gap-2">
                <a href="?durum=yaklasan" class="filter-btn text-decoration-none <?php echo $durumFiltre === 'yaklasan' ? 'active' : ''; ?>">
                    Yaklaşan
                </a>
                <a href="?durum=gecmis" class="filter-btn text-decoration-none <?php echo $durumFiltre === 'gecmis' ? 'active' : ''; ?>">
                    Geçmiş
                </a>
                <a href="?durum=tum" class="filter-btn text-decoration-none <?php echo $durumFiltre === 'tum' ? 'active' : ''; ?>">
                    Tümü
                </a>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if (!empty($errors)): ?>
             <div class="alert alert-danger shadow-sm border-0 border-start border-4 border-danger rounded-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle fa-lg me-3"></i>
                     <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success shadow-sm border-0 border-start border-4 border-success rounded-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-lg me-3"></i>
                    <div>
                        <?php foreach ($messages as $message): ?>
                            <div><?php echo htmlspecialchars($message); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Meeting Grid -->
        <?php if (empty($toplantilar)): ?>
            <div class="text-center py-5 bg-white rounded-3 shadow-sm">
                <div class="mb-3 text-muted opacity-50">
                    <i class="fas fa-calendar-times fa-4x"></i>
                </div>
                <h5 class="text-muted">Listelenecek toplantı bulunamadı</h5>
                <p class="text-muted small">Şu an için gösterilecek bir toplantı kaydı mevcut değil.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($toplantilar as $toplanti): ?>
                    <?php
                        $tarih = new DateTime($toplanti['toplanti_tarihi']);
                        $monthShort = $tarih->format('M');
                        $trMonth = $trMonths[$monthShort] ?? $monthShort;
                        
                        // Map status codes to readable text and colors
                        // Enum: beklemede, katilacak, katilmayacak, mazeret
                        $durumProps = match ($toplanti['katilim_durumu']) {
                            'katilacak' => ['text' => 'Katılacağım', 'class' => 'success', 'icon' => 'check-circle'],
                            'katilmayacak' => ['text' => 'Katılamayacağım', 'class' => 'danger', 'icon' => 'times-circle'],
                            'mazeret' => ['text' => 'Mazeretli', 'class' => 'warning', 'icon' => 'exclamation-circle'],
                            default => ['text' => 'Beklemede', 'class' => 'secondary', 'icon' => 'question-circle'],
                        };
                        
                        // Card Border Color based on status
                        $borderClass = $toplanti['katilim_durumu'] === 'beklemede' ? 'border-warning' : 'border-0';
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card meeting-card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex gap-3 mb-4">
                                    <!-- Date Badge -->
                                    <div class="date-badge">
                                        <span class="h3 mb-0 fw-bold"><?php echo $tarih->format('d'); ?></span>
                                        <span class="small fw-semibold text-uppercase opacity-75"><?php echo $trMonth; ?></span>
                                    </div>
                                    
                                    <!-- Title & Meta -->
                                    <div class="flex-grow-1">
                                        <h5 class="card-title fw-bold mb-2 text-dark text-truncate-2">
                                            <?php echo htmlspecialchars($toplanti['baslik']); ?>
                                        </h5>
                                        <div class="d-flex align-items-center text-muted small">
                                            <i class="far fa-clock me-1 text-primary"></i>
                                            <?php echo $tarih->format('H:i'); ?>
                                            
                                            <?php if($toplanti['konum']): ?>
                                                <span class="mx-2 opacity-50">•</span>
                                                <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                                                <?php echo htmlspecialchars($toplanti['konum']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current Status Badge -->
                                <div class="mb-4">
                                    <?php 
                                    $badgeClass = match($toplanti['katilim_durumu']) {
                                        'katilacak' => 'katilacak',
                                        'katilmayacak' => 'katilmayacak',
                                        'mazeret' => 'mazeret',
                                        default => 'beklemede'
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $badgeClass; ?>">
                                        <i class="fas fa-<?php echo $durumProps['icon']; ?>"></i>
                                        <?php echo $durumProps['text']; ?>
                                    </span>
                                    <?php if($toplanti['mazeret_aciklama']): ?>
                                        <div class="mt-2 text-muted small fst-italic ps-2 border-start border-3 border-secondary">
                                            "<?php echo htmlspecialchars($toplanti['mazeret_aciklama']); ?>"
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Description Excerpt -->
                                <?php if($toplanti['aciklama']): ?>
                                    <div class="text-muted small mb-0 text-truncate-3" style="line-height: 1.6;">
                                        <?php echo formatTextAsList($toplanti['aciklama']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Footer -->
                            <div class="card-footer bg-transparent border-top p-4">
                                <form method="post">
                                    <input type="hidden" name="action" value="katilim_guncelle">
                                    <input type="hidden" name="katilimci_id" value="<?php echo $toplanti['katilimci_id']; ?>">
                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                    
                                    <div class="d-flex flex-column gap-3">
                                        <div class="input-group">
                                            <select name="katilim_durumu" class="form-select status-select border-end-0" style="border-radius: 0.5rem 0 0 0.5rem;" data-target="mazeret-<?php echo $toplanti['katilimci_id']; ?>">
                                                <option value="beklemede" <?php echo $toplanti['katilim_durumu'] === 'beklemede' ? 'selected' : ''; ?>>Durum Seçiniz...</option>
                                                <option value="katilacak" <?php echo $toplanti['katilim_durumu'] === 'katilacak' ? 'selected' : ''; ?>>✅ Katılacağım</option>
                                                <option value="katilmayacak" <?php echo $toplanti['katilim_durumu'] === 'katilmayacak' ? 'selected' : ''; ?>>❌ Katılamayacağım</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary px-4" style="border-radius: 0 0.5rem 0.5rem 0;">
                                                Kaydet
                                            </button>
                                        </div>
                                        
                                        <!-- Mazeret Input -->
                                        <div id="mazeret-<?php echo $toplanti['katilimci_id']; ?>" class="<?php echo $toplanti['katilim_durumu'] === 'katilmayacak' ? '' : 'd-none'; ?>">
                                            <input type="text" name="mazeret_aciklama" class="form-control" 
                                                   placeholder="Katılamama nedeniniz (Mazeret)..." 
                                                   value="<?php echo htmlspecialchars($toplanti['mazeret_aciklama'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    </div>
    </main>
</div>

<style>
/* Custom Utilities */
.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.text-truncate-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.hover-shadow:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
.transition-all {
    transition: all 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/Hide Mazeret input based on selection (trigger: katilmayacak)
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const mazeretDiv = document.getElementById(this.dataset.target);
            const inputInfo = mazeretDiv.querySelector('input');
            
            // Logic: If 'katilmayacak' -> show mazeret and make required
            if (this.value === 'katilmayacak') {
                mazeretDiv.classList.remove('d-none');
                inputInfo.required = true;
                // Optional: focus if status changed to this
                inputInfo.focus();
            } else {
                mazeretDiv.classList.add('d-none');
                inputInfo.required = false;
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

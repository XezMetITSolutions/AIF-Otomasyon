<?php
/**
 * Başkan - Toplantı Düzenleme & Detay
 * Yeniden yapılandırılmış minimal versiyon
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireBaskan();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Toplantı Detayı';

$toplanti_id = $_GET['id'] ?? null;
if (!$toplanti_id) {
    header('Location: /baskan/toplantilar.php');
    exit;
}

// Toplantı bilgilerini getir
$toplanti = $db->fetch("
    SELECT t.*, b.byk_adi, b.byk_kodu
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    WHERE t.toplanti_id = ? AND t.byk_id = ?
", [$toplanti_id, $user['byk_id']]);

if (!$toplanti) {
    header('Location: /baskan/toplantilar.php');
    exit;
}

// Katılımcıları getir
$katilimcilar = $db->fetchAll("
    SELECT 
        tk.*,
        k.ad,
        k.soyad,
        k.email,
        ab.alt_birim_adi
    FROM toplanti_katilimcilar tk
    INNER JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
    LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.katilim_durumu, k.ad, k.soyad
", [$toplanti_id]);

// Gündem maddelerini getir
$gundem_maddeleri = $db->fetchAll("
    SELECT * FROM toplanti_gundem
    WHERE toplanti_id = ?
    ORDER BY sira_no
", [$toplanti_id]);

// Kararları getir
$kararlar = $db->fetchAll("
    SELECT 
        tk.*,
        tg.baslik as gundem_baslik
    FROM toplanti_kararlar tk
    LEFT JOIN toplanti_gundem tg ON tk.gundem_id = tg.gundem_id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.karar_id
", [$toplanti_id]);

// Tutanağı getir
$tutanak = $db->fetch("
    SELECT * FROM toplanti_tutanak
    WHERE toplanti_id = ?
", [$toplanti_id]);

$error = '';
$success = $_GET['success'] ?? '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_toplanti') {
            $baslik = trim($_POST['baslik'] ?? '');
            $aciklama = trim($_POST['aciklama'] ?? '');
            $toplanti_tarihi = $_POST['toplanti_tarihi'] ?? '';
            $bitis_tarihi = $_POST['bitis_tarihi'] ?? null;
            $konum = trim($_POST['konum'] ?? '');
            $toplanti_turu = $_POST['toplanti_turu'] ?? 'normal';
            $durum = $_POST['durum'] ?? 'planlandi';
            
            try {
                $db->query("
                    UPDATE toplantilar SET
                        baslik = ?,
                        aciklama = ?,
                        toplanti_tarihi = ?,
                        bitis_tarihi = ?,
                        konum = ?,
                        toplanti_turu = ?,
                        durum = ?
                    WHERE toplanti_id = ?
                ", [$baslik, $aciklama, $toplanti_tarihi, $bitis_tarihi, $konum, $toplanti_turu, $durum, $toplanti_id]);
            } catch (PDOException $e) {
                // Self-healing
                $msg = $e->getMessage();
                if (strpos($msg, 'Unknown column') !== false) {
                    if (strpos($msg, 'bitis_tarihi') !== false) {
                        $db->query("ALTER TABLE toplantilar ADD COLUMN bitis_tarihi DATETIME NULL AFTER toplanti_tarihi");
                    }
                    if (strpos($msg, 'toplanti_turu') !== false) {
                        $db->query("ALTER TABLE toplantilar ADD COLUMN toplanti_turu ENUM('normal', 'acil', 'ozel', 'olagan', 'olağanüstü') DEFAULT 'normal' AFTER gundem");
                    }
                    $db->query("
                        UPDATE toplantilar SET
                            baslik = ?,
                            aciklama = ?,
                            toplanti_tarihi = ?,
                            bitis_tarihi = ?,
                            konum = ?,
                            toplanti_turu = ?,
                            durum = ?
                        WHERE toplanti_id = ?
                    ", [$baslik, $aciklama, $toplanti_tarihi, $bitis_tarihi, $konum, $toplanti_turu, $durum, $toplanti_id]);
                } else {
                    throw $e;
                }
            }
            
            $success = 'Bilgiler güncellendi.';
            header("Location: /baskan/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
/* Minimal Custom Styling */
.meeting-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.nav-tabs .nav-link {
    border: none;
    color: #666;
    font-weight: 500;
    padding: 1rem 1.5rem;
}

.nav-tabs .nav-link.active {
    background: #667eea;
    color: white;
    border-radius: 5px;
}

.tab-content {
    padding: 2rem 0;
}

@media (min-width: 992px) {
    main.container-fluid {
        padding-left: 280px !important;
        padding-right: 20px !important;
    }
}
</style>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper" data-toplanti-id="<?php echo $toplanti_id; ?>">
        
        <!-- Header -->
        <div class="meeting-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="mb-3"><?php echo htmlspecialchars($toplanti['baslik']); ?></h1>
                    <p class="mb-0">
                        <i class="fas fa-calendar me-2"></i>
                        <?php echo date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])); ?>
                        <span class="ms-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($toplanti['konum'] ?? 'Konum Belirtilmedi'); ?>
                        </span>
                    </p>
                </div>
                <div>
                    <a href="/admin/toplanti-pdf.php?id=<?php echo $toplanti_id; ?>" class="btn btn-light" target="_blank">
                        <i class="fas fa-file-pdf me-2"></i>PDF
                    </a>
                    <a href="/baskan/toplantilar.php" class="btn btn-outline-light ms-2">
                        <i class="fas fa-arrow-left me-2"></i>Geri
                    </a>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="meetingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bilgiler-tab" data-bs-toggle="tab" data-bs-target="#bilgiler" type="button" role="tab">
                    <i class="fas fa-info-circle me-2"></i>Bilgiler
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="katilimcilar-tab" data-bs-toggle="tab" data-bs-target="#katilimcilar" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Katılımcılar
                    <span class="badge bg-primary ms-1"><?php echo count($katilimcilar); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gundem-tab" data-bs-toggle="tab" data-bs-target="#gundem" type="button" role="tab">
                    <i class="fas fa-list me-2"></i>Gündem
                    <span class="badge bg-primary ms-1"><?php echo count($gundem_maddeleri); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="kararlar-tab" data-bs-toggle="tab" data-bs-target="#kararlar" type="button" role="tab">
                    <i class="fas fa-gavel me-2"></i>Kararlar
                    <span class="badge bg-primary ms-1"><?php echo count($kararlar); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tutanak-tab" data-bs-toggle="tab" data-bs-target="#tutanak" type="button" role="tab">
                    <i class="fas fa-file-alt me-2"></i>Tutanak
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="meetingTabContent">
            <div class="tab-pane fade show active" id="bilgiler" role="tabpanel">
                <?php include __DIR__ . '/../includes/toplanti-tabs/bilgiler.php'; ?>
            </div>
            <div class="tab-pane fade" id="katilimcilar" role="tabpanel">
                <?php include __DIR__ . '/../includes/toplanti-tabs/katilimcilar.php'; ?>
            </div>
            <div class="tab-pane fade" id="gundem" role="tabpanel">
                <?php include __DIR__ . '/../includes/toplanti-tabs/gundem.php'; ?>
            </div>
            <div class="tab-pane fade" id="kararlar" role="tabpanel">
                <?php include __DIR__ . '/../includes/toplanti-tabs/kararlar.php'; ?>
            </div>
            <div class="tab-pane fade" id="tutanak" role="tabpanel">
                <?php include __DIR__ . '/../includes/toplanti-tabs/tutanak.php'; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Minimal Custom Script - Only for API operations -->
<script>
const ToplantiYonetimi = {
    toplanti_id: <?php echo $toplanti_id; ?>,
    
    apiRequest: function(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .catch(error => {
            console.error('Error:', error);
            return { success: false, error: 'Network error' };
        });
    },
    
    showAlert: function(type, message, duration = 3000) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), duration);
    }
};

// Hash navigation support
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tabId = hash.replace('#', '');
        const tabEl = document.getElementById(tabId + '-tab');
        if (tabEl) {
            const tab = new bootstrap.Tab(tabEl);
            tab.show();
        }
    }
});
</script>

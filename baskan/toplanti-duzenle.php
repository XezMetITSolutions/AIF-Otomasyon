<?php
/**
 * Başkan - Toplantı Düzenleme & Detay
 * Modern Dashboard Tasarımı
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

// Form gönderildiğinde (Basit Update)
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
            ", [
                $baslik,
                $aciklama,
                $toplanti_tarihi,
                $bitis_tarihi,
                $konum,
                $toplanti_turu,
                $durum,
                $toplanti_id
            ]);

            // Otomatik Gündem Oluşturma (Eğer hiç gündem yoksa ve açıklamada maddeler varsa)
            $mevcut_gundem = $db->fetch("SELECT COUNT(*) as sayi FROM toplanti_gundem WHERE toplanti_id = ?", [$toplanti_id]);
            if ($mevcut_gundem['sayi'] == 0 && !empty($aciklama)) {
                $lines = explode("\n", $aciklama);
                $sira = 1;
                foreach ($lines as $line) {
                    $line = trim($line);
                    // Bullet styles: -, *, •, 1.
                    if (preg_match('/^[-*•]\s+(.*)$/', $line, $m) || preg_match('/^\d+\.\s+(.*)$/', $line, $m)) {
                        $gundem_baslik = trim($m[1]);
                        if (!empty($gundem_baslik)) {
                            $db->query("INSERT INTO toplanti_gundem (toplanti_id, sira_no, baslik, durum) VALUES (?, ?, ?, 'beklemede')", 
                                [$toplanti_id, $sira++, $gundem_baslik]);
                        }
                    }
                }
            }
            
            $success = 'Bilgiler güncellendi' . ($sira > 1 ? ' ve otomatik gündem oluşturuldu.' : '.');
            header("Location: /baskan/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
            exit;
        }
    } catch (Exception $e) {
        $msg = $e->getMessage();
        // Self-Healing for missing columns
        if (strpos($msg, 'Unknown column') !== false) {
             if (strpos($msg, 'bitis_tarihi') !== false) {
                 $db->query("ALTER TABLE toplantilar ADD COLUMN bitis_tarihi DATETIME NULL AFTER toplanti_tarihi");
             }
             if (strpos($msg, 'toplanti_turu') !== false) {
                 $db->query("ALTER TABLE toplantilar ADD COLUMN toplanti_turu ENUM('normal', 'acil', 'ozel', 'olagan', 'olaganüstü') DEFAULT 'normal' AFTER gundem");
             }
             // Retry Update
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
            ", [
                $baslik,
                $aciklama,
                $toplanti_tarihi,
                $bitis_tarihi,
                $konum,
                $toplanti_turu,
                $durum,
                $toplanti_id
            ]);
            $success = 'Bilgiler güncellendi (Sistem onarımı yapıldı).';
            header("Location: /baskan/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
            exit;
        } else {
            $error = $msg;
        }
    }
}

// Stats
$katilim_stats = [
    'beklemede' => 0,
    'katildi' => 0,
    'ozur_diledi' => 0,
    'izinli' => 0,
    'katilmadi' => 0
];
foreach ($katilimcilar as $k) {
    if (isset($katilim_stats[$k['katilim_durumu']])) {
        $katilim_stats[$k['katilim_durumu']]++;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Custom CSS for Baskan Redesign -->
<style>
/* Modern Gradient Header */
.hero-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 25px rgba(30, 60, 114, 0.2);
    position: relative;
    overflow: hidden;
}

.hero-header::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.hero-header::after {
    content: '';
    position: absolute;
    bottom: -30px;
    left: 100px;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-badge {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Glass Tabs */
.nav-pills-glass {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    padding: 0.5rem;
    border-radius: 15px;
    display: inline-flex;
    gap: 0.5rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}

.nav-pills-glass .nav-link {
    color: #555;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-pills-glass .nav-link:hover {
    background: rgba(0, 0, 0, 0.05);
}

.nav-pills-glass .nav-link.active {
    background: #1e3c72;
    color: white;
    box-shadow: 0 4px 12px rgba(30, 60, 114, 0.3);
}

/* Glass Cards overrides */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.card-header {
    background: #fff;
    border-bottom: 1px solid #eee;
    padding: 1.25rem 1.5rem;
}

.stat-box {
    background: #fff;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    text-align: center;
}

/* Sidebar Layout Fix */
@media (min-width: 992px) {
    main.container-fluid {
        padding-left: 290px; /* Sidebar width (280px) + gap (10px) */
    }
}
</style>

<link rel="stylesheet" href="/assets/css/toplanti-yonetimi.css">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper" data-toplanti-id="<?php echo $toplanti_id; ?>">
        
        <!-- Hero Header -->
        <div class="hero-header">
            <div class="hero-content">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div class="mb-3 d-flex gap-2">
                             <span class="hero-badge">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])); ?>
                            </span>
                            <span class="hero-badge">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($toplanti['konum'] ?? 'Konum Belirtilmedi'); ?>
                            </span>
                             <span class="hero-badge" style="background: rgba(255,255,255,0.3)">
                                <?php echo ucfirst($toplanti['durum']); ?>
                            </span>
                        </div>
                        <h1 class="display-6 fw-bold mb-2"><?php echo htmlspecialchars($toplanti['baslik']); ?></h1>
                        <p class="mb-0 opacity-75">
                            <i class="fas fa-layer-group me-2"></i><?php echo htmlspecialchars($toplanti['byk_adi']); ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                         <a href="/admin/toplanti-pdf.php?id=<?php echo $toplanti_id; ?>" class="btn btn-light text-primary" target="_blank">
                            <i class="fas fa-file-pdf me-2"></i>Rapor Al
                        </a>
                        <a href="/baskan/toplantilar.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Listeye Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Modern Tabs -->
        <div class="d-flex justify-content-center">
            <ul class="nav nav-pills nav-pills-glass mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="pill" href="#bilgiler">
                        <i class="fas fa-info-circle me-2"></i>Bilgiler
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#katilimcilar">
                        <i class="fas fa-users me-2"></i>Katılımcılar
                        <span class="badge bg-white text-primary ms-1"><?php echo count($katilimcilar); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#gundem">
                        <i class="fas fa-list me-2"></i>Gündem
                        <span class="badge bg-white text-primary ms-1"><?php echo count($gundem_maddeleri); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#kararlar">
                        <i class="fas fa-gavel me-2"></i>Kararlar
                        <span class="badge bg-white text-primary ms-1"><?php echo count($kararlar); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#tutanak">
                        <i class="fas fa-file-alt me-2"></i>Tutanak
                    </a>
                </li>
            </ul>
        </div>

        <!-- Content Area -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="bilgiler">
                <?php include __DIR__ . '/../includes/toplanti-tabs/bilgiler.php'; ?>
            </div>
            <div class="tab-pane fade" id="katilimcilar">
                <?php include __DIR__ . '/../includes/toplanti-tabs/katilimcilar.php'; ?>
            </div>
            <div class="tab-pane fade" id="gundem">
                <?php include __DIR__ . '/../includes/toplanti-tabs/gundem.php'; ?>
            </div>
            <div class="tab-pane fade" id="kararlar">
                <?php include __DIR__ . '/../includes/toplanti-tabs/kararlar.php'; ?>
            </div>
            <div class="tab-pane fade" id="tutanak">
                <?php include __DIR__ . '/../includes/toplanti-tabs/tutanak.php'; ?>
            </div>
        </div>
    </div>
</main>

<script src="/assets/js/toplanti-yonetimi.js"></script>

<?php
include __DIR__ . '/../includes/footer.php';
?>

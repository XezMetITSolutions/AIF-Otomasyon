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

            // Otomatik Gündem Senkronizasyonu (Açıklama maddelerine göre güncelle)
            if (!empty($aciklama)) {
                $lines = explode("\n", $aciklama);
                $yeni_maddeler = [];
                $sira = 1;
                
                // 1. Yeni maddeleri parse et
                foreach ($lines as $line) {
                    $line = trim($line);
                    // Bullet styles: -, *, •, +, o, –, —, 1., 1), a., a), >
                    // \p{N} matches any number
                    if (preg_match('/^\s*([-*•+o–—>]|\d+[.)]|[a-zA-Z][.)])\s+(.*)$/u', $line, $m)) {
                        $baslik = trim($m[2]);
                        if (!empty($baslik)) {
                            $yeni_maddeler[$sira++] = $baslik;
                        }
                    }
                }

                // 2. Mevcut maddeleri getir
                $mevcut_gundem = $db->fetchAll("SELECT * FROM toplanti_gundem WHERE toplanti_id = ? ORDER BY sira_no", [$toplanti_id]);
                $mevcut_map = [];
                foreach ($mevcut_gundem as $mg) {
                    $mevcut_map[$mg['sira_no']] = $mg;
                }

                // 3. Senkronizasyon (Update/Insert)
                foreach ($yeni_maddeler as $sira_no => $baslik) {
                    if (isset($mevcut_map[$sira_no])) {
                        // Varsa güncelle
                        if ($mevcut_map[$sira_no]['baslik'] !== $baslik) {
                            $db->query("UPDATE toplanti_gundem SET baslik = ? WHERE gundem_id = ?", [$baslik, $mevcut_map[$sira_no]['gundem_id']]);
                        }
                    } else {
                        // Yoksa ekle
                        $db->query("INSERT INTO toplanti_gundem (toplanti_id, sira_no, baslik, durum) VALUES (?, ?, ?, 'beklemede')", 
                            [$toplanti_id, $sira_no, $baslik]);
                    }
                }

                // 4. Fazlalıkları sil (Delete)
                // Açıklamayı sildiyse gündemi de silsin mi? Evet, senkronizasyon mantığı budur.
                // Ancak kararlara bağlıysa silinemez (DB hatası verebilir), try-catch ile yönetelim.
                foreach ($mevcut_map as $sira_no => $mg) {
                    if (!isset($yeni_maddeler[$sira_no])) {
                        try {
                            $db->query("DELETE FROM toplanti_gundem WHERE gundem_id = ?", [$mg['gundem_id']]);
                        } catch (Exception $e) {
                            // Silinemiyorsa (muhtemelen karar bağlı), pass geç.
                        }
                    }
                }
            }
            
            $success = 'Bilgiler ve gündem maddeleri güncellendi.';
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
    </div>
</main>

<?php
$pageSpecificJS = ['/assets/js/toplanti-yonetimi.js?v=' . time()];
include __DIR__ . '/../includes/footer.php';
?>

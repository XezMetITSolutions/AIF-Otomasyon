<?php
/**
 * Başkan - Toplantı Düzenleme & Detay
 * Modern Dashboard Tasarımı
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';



$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Toplantı Detayı';

$toplanti_id = $_GET['id'] ?? null;
if (!$toplanti_id) {
    header('Location: /panel/toplantilar.php');
    exit;
}

// Toplantı bilgilerini getir
$toplanti = $db->fetch("
    SELECT t.*, b.byk_adi, b.byk_kodu
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    WHERE t.toplanti_id = ? AND t.byk_id = ?
", [$toplanti_id, $user['byk_id']]);

$currentUserId = $user['id'] ?? $user['kullanici_id'];
$isCreator = ($toplanti['olusturan_id'] == $currentUserId);
$isSecretary = (isset($toplanti['sekreter_id']) && $toplanti['sekreter_id'] == $currentUserId);
$canManageContent = $isCreator || $isSecretary;

if (!$toplanti) {
    header('Location: /panel/toplantilar.php');
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
    ORDER BY FIELD(tk.katilim_durumu, 'katilacak', 'beklemede', 'katilmayacak'), k.ad, k.soyad
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
            $konum = trim($_POST['konum'] ?? '');
            $toplanti_turu = $_POST['toplanti_turu'] ?? 'normal';
            $durum = $_POST['durum'] ?? 'planlandi';
            $sekreter_id = !empty($_POST['sekreter_id']) ? $_POST['sekreter_id'] : null;

            $db->query("
                UPDATE toplantilar SET
                    baslik = ?,
                    aciklama = ?,
                    toplanti_tarihi = ?,
                    konum = ?,
                    toplanti_turu = ?,
                    durum = ?,
                    sekreter_id = ?
                WHERE toplanti_id = ?
            ", [
                $baslik,
                $aciklama,
                $toplanti_tarihi,
                $konum,
                $toplanti_turu,
                $durum,
                $sekreter_id,
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
                        $db->query(
                            "INSERT INTO toplanti_gundem (toplanti_id, sira_no, baslik, durum) VALUES (?, ?, ?, 'beklemede')",
                            [$toplanti_id, $sira_no, $baslik]
                        );
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
            header("Location: /panel/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
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
            if (strpos($msg, 'sekreter_id') !== false) {
                $db->query("ALTER TABLE toplantilar ADD COLUMN sekreter_id INT NULL DEFAULT NULL");
            }
            if (strpos($msg, 'baskan_degerlendirmesi') !== false) {
                $db->query("ALTER TABLE toplantilar ADD COLUMN baskan_degerlendirmesi TEXT NULL");
            }
            // Retry Update
            $db->query("
                UPDATE toplantilar SET
                    baslik = ?,
                    aciklama = ?,
                    toplanti_tarihi = ?,
                    konum = ?,
                    toplanti_turu = ?,
                    durum = ?,
                    sekreter_id = ?
                WHERE toplanti_id = ?
            ", [
                $baslik,
                $aciklama,
                $toplanti_tarihi,
                $konum,
                $toplanti_turu,
                $durum,
                $sekreter_id,
                $toplanti_id
            ]);
            $success = 'Bilgiler güncellendi (Sistem onarımı yapıldı).';
            header("Location: /panel/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
            exit;
        } else {
            $error = $msg;
        }
    }
}

// Stats
$katilim_stats = [
    'beklemede' => 0,
    'katilacak' => 0,
    'katilmayacak' => 0,
    'mazeret' => 0
];
foreach ($katilimcilar as $k) {
    if (isset($katilim_stats[$k['katilim_durumu']])) {
        $katilim_stats[$k['katilim_durumu']]++;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<!-- ... (skipping unchanged content) ... -->
<li class="nav-item">
    <a class="nav-link" data-bs-toggle="pill" href="#katilimcilar">
        <i class="fas fa-users me-2"></i>Katılımcılar
        <span
            class="badge bg-white text-primary ms-1"><?php echo $katilim_stats['katilacak'] . '/' . count($katilimcilar); ?></span>
    </a>
</li>
?>

<!-- Custom CSS for Baskan Redesign -->
<style>
    /* Modern Gradient Header */
    /* Modern Minimal Header */
    .modern-header {
        background: #fff;
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.03);
        position: relative;
        overflow: hidden;
    }

    /* Subtle accent line on top */
    .modern-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
    }

    .header-meta {
        display: inline-flex;
        align-items: center;
        gap: 1.5rem;
        color: #6c757d;
        font-size: 0.95rem;
        margin-bottom: 1rem;
    }

    .header-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .header-breadcrumb {
        font-size: 0.9rem;
        color: #0d6efd;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        display: block;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
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
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        text-align: center;
    }

    /* Layout overrides */
    .content-wrapper {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }



    /* Sidebar Layout Fix */
    @media (min-width: 992px) {
        main.container-fluid {
            padding-left: 280px !important;
            padding-right: 20px !important;
            /* Slight padding for aesthetics */
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>

<link rel="stylesheet" href="/assets/css/toplanti-yonetimi.css">

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper" data-toplanti-id="<?php echo $toplanti_id; ?>">

        <!-- Modern Minimal Header -->
        <div class="modern-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <span class="header-breadcrumb">
                        <i class="fas fa-layer-group me-1"></i><?php echo htmlspecialchars($toplanti['byk_adi']); ?>
                    </span>
                    <h1 class="page-title"><?php echo htmlspecialchars($toplanti['baslik']); ?></h1>

                    <div class="header-meta">
                        <div class="header-meta-item">
                            <i class="far fa-calendar-alt text-primary"></i>
                            <?php echo date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])); ?>
                        </div>
                        <div class="header-meta-item">
                            <i class="fas fa-map-marker-alt text-danger"></i>
                            <?php echo htmlspecialchars($toplanti['konum'] ?? 'Konum Belirtilmedi'); ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="/admin/toplanti-pdf.php?id=<?php echo $toplanti_id; ?>" class="btn btn-primary"
                        target="_blank">
                        <i class="fas fa-file-pdf me-2"></i>Rapor Al
                    </a>
                    <button type="button" class="btn btn-warning" onclick="ToplantiYonetimi.sendReport(this, true)">
                        <i class="fas fa-flask me-2"></i>Test Gönder
                    </button>
                    <button type="button" class="btn btn-success" onclick="ToplantiYonetimi.sendReport(this)">
                        <i class="fas fa-paper-plane me-2"></i>Raporu Gönder
                    </button>
                    <a href="/panel/toplantilar.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Listeye Dön
                    </a>
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
                        <span
                            class="badge bg-white text-primary ms-1"><?php echo $katilim_stats['katilacak'] . '/' . count($katilimcilar); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#gundem">
                        <i class="fas fa-list me-2"></i>Gündem
                        <span class="badge bg-white text-primary ms-1"><?php echo count($gundem_maddeleri); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#degerlendirme">
                        <i class="fas fa-clipboard-check me-2"></i>Değerlendirme
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
            <div class="tab-pane fade" id="degerlendirme">
                <?php include __DIR__ . '/../includes/toplanti-tabs/degerlendirme.php'; ?>
            </div>

        </div>

        <script src="/assets/js/toplanti-yonetimi.js?v=<?php echo time(); ?>"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof ToplantiYonetimi !== 'undefined') {
                    ToplantiYonetimi.init(<?php echo $toplanti_id; ?>);
                }
            });
            // Also trigger for SPA
            $(document).on('page:loaded', function () {
                if (typeof ToplantiYonetimi !== 'undefined') {
                    ToplantiYonetimi.init(<?php echo $toplanti_id; ?>);
                }
            });
        </script>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>
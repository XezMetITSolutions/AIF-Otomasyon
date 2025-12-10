<?php
/**
 * Ana Yönetici - Toplantı Düzenleme
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Toplantı Düzenle';

$toplanti_id = $_GET['id'] ?? null;
if (!$toplanti_id) {
    header('Location: /admin/toplantilar.php');
    exit;
}

// Toplantı bilgilerini getir
$toplanti = $db->fetch("
    SELECT t.*, b.byk_adi, b.byk_kodu
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    WHERE t.toplanti_id = ?
", [$toplanti_id]);

if (!$toplanti) {
    header('Location: /admin/toplantilar.php');
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

// BYK listesi
$bykler = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk ORDER BY byk_adi");

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
            
            $success = 'Toplantı bilgileri güncellendi!';
        }
        
        // Sayfayı yenile
        header("Location: /admin/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
        exit;
        
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
            $success = 'Toplantı bilgileri güncellendi (Sistem onarımı yapıldı).';
            header("Location: /admin/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
            exit;
        } else {
            $error = $msg;
        }
    }
}

// Katılım istatistikleri
$katilim_stats = [
    'beklemede' => 0,
    'katilacak' => 0,
    'katilmayacak' => 0,
    'mazeret' => 0,
    'katildi' => 0,
    'katilmadi' => 0
];
foreach ($katilimcilar as $k) {
    $katilim_stats[$k['katilim_durumu']]++;
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/assets/css/toplanti-yonetimi.css">

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper" data-toplanti-id="<?php echo $toplanti_id; ?>">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($toplanti['baslik']); ?>
            </h1>
            <div>
                <a href="/admin/toplanti-pdf.php?id=<?php echo $toplanti_id; ?>" class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>PDF İndir
                </a>
                <a href="/admin/toplantilar.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Geri Dön
                </a>
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

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#bilgiler">
                    <i class="fas fa-info-circle me-2"></i>Temel Bilgiler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#katilimcilar">
                    <i class="fas fa-users me-2"></i>Katılımcılar
                    <span class="badge bg-primary ms-1"><?php echo ($katilim_stats['katildi'] + $katilim_stats['katilacak']) . '/' . count($katilimcilar); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#gundem">
                    <i class="fas fa-list me-2"></i>Gündem
                    <span class="badge bg-primary ms-1"><?php echo count($gundem_maddeleri); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#kararlar">
                    <i class="fas fa-gavel me-2"></i>Kararlar
                    <span class="badge bg-primary ms-1"><?php echo count($kararlar); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tutanak">
                    <i class="fas fa-file-alt me-2"></i>Tutanak
                    <?php if ($tutanak): ?>
                        <span class="badge bg-success ms-1">
                            <i class="fas fa-check"></i>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Temel Bilgiler Tab -->
            <div class="tab-pane fade show active" id="bilgiler">
                <?php include __DIR__ . '/../includes/toplanti-tabs/bilgiler.php'; ?>
            </div>

            <!-- Katılımcılar Tab -->
            <div class="tab-pane fade" id="katilimcilar">
                <?php include __DIR__ . '/../includes/toplanti-tabs/katilimcilar.php'; ?>
            </div>

            <!-- Gündem Tab -->
            <div class="tab-pane fade" id="gundem">
                <?php include __DIR__ . '/../includes/toplanti-tabs/gundem.php'; ?>
            </div>

            <!-- Kararlar Tab -->
            <div class="tab-pane fade" id="kararlar">
                <?php include __DIR__ . '/../includes/toplanti-tabs/kararlar.php'; ?>
            </div>

            <!-- Tutanak Tab -->
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

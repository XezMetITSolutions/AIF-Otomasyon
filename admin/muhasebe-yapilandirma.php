<?php
/**
 * Ana Yönetici - Muhasebe Yapılandırması
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Muhasebe Yapılandırması';

$messages = [];
$errors = [];

// --- Otomatik DB Migrasyonu ---
try {
    // 1. byk tablosuna muhasebe_baskani_id ekle
    $checkCol = $db->fetch("SHOW COLUMNS FROM byk LIKE 'muhasebe_baskani_id'");
    if (!$checkCol) {
        $db->query("ALTER TABLE byk ADD COLUMN muhasebe_baskani_id INT DEFAULT NULL");
        $db->query("ALTER TABLE byk ADD CONSTRAINT fk_byk_muhasebe FOREIGN KEY (muhasebe_baskani_id) REFERENCES kullanicilar(kullanici_id) ON DELETE SET NULL");
        $messages[] = "BYK tablosuna muhasebe_baskani_id kolonu eklendi.";
    }

    // 2. harcama_talepleri tablosuna onay_asamasi ve onay durumu ekle
    $checkColH = $db->fetch("SHOW COLUMNS FROM harcama_talepleri LIKE 'onay_asamasi'");
    if (!$checkColH) {
        // onay_asamasi: 1 = Birim Onayı, 2 = AT Onayı, 3 = Tamamlandı
        $db->query("ALTER TABLE harcama_talepleri ADD COLUMN onay_asamasi TINYINT DEFAULT 1"); 
        $db->query("ALTER TABLE harcama_talepleri ADD COLUMN birim_onay_tarihi DATETIME DEFAULT NULL");
        $db->query("ALTER TABLE harcama_talepleri ADD COLUMN birim_onaylayan_id INT DEFAULT NULL");
        $messages[] = "Harcama talepleri tablosu güncellendi (Onay aşamaları eklendi).";
    }

} catch (Exception $e) {
    $errors[] = "Veritabanı güncelleme hatası: " . $e->getMessage();
}

// --- Form İşleme ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['byk_muhasebe_update'])) {
        try {
            foreach ($_POST['muhasebe_baskani'] as $bykId => $userId) {
                // userId '0' veya boş ise NULL yap
                $val = !empty($userId) ? $userId : null;
                $db->query("UPDATE byk SET muhasebe_baskani_id = ? WHERE byk_id = ?", [$val, $bykId]);
            }
            $messages[] = "Muhasebe başkanları başarıyla güncellendi.";
        } catch (Exception $e) {
            $errors[] = "Güncelleme hatası: " . $e->getMessage();
        }
    }
}

// --- Verileri Çekme ---
$bykList = $db->fetchAll("
    SELECT b.*, CONCAT(k.ad, ' ', k.soyad) as mevcut_baskan_adi
    FROM byk b
    LEFT JOIN kullanicilar k ON b.muhasebe_baskani_id = k.kullanici_id
    ORDER BY b.byk_kodu ASC, b.byk_adi ASC
");

// Tüm kullanıcıları çek (Dropdown için)
$users = $db->fetchAll("
    SELECT kullanici_id, ad, soyad, email 
    FROM kullanicilar 
    WHERE aktif = 1 
    ORDER BY ad ASC, soyad ASC
");

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-calculator me-2"></i>Muhasebe Yapılandırması
            </h1>
        </div>

        <?php if (!empty($messages)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php foreach ($messages as $msg): ?>
                    <div><i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
             <div class="alert alert-danger alert-dismissible fade show">
                <?php foreach ($errors as $err): ?>
                    <div><i class="fas fa-exclamation-triangle me-1"></i> <?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Birim (BYK) Muhasebe Başkanları</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Her birim için bir muhasebe başkanı belirleyin. 
                    Harcama talepleri önce ilgili birimin muhasebe başkanına, onaylanırsa AT (Ana Teşkilat) muhasebesine düşecektir.
                </p>
                <p class="small text-info"><i class="fas fa-info-circle"></i> "AT" kodlu birim, Ana Teşkilat olarak kabul edilir. AT'den gelen talepler doğrudan AT onayına düşer.</p>

                <form method="POST">
                    <input type="hidden" name="byk_muhasebe_update" value="1">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 100px;">BYK Kodu</th>
                                    <th>Birim Adı</th>
                                    <th>Mevcut Muhasebe Başkanı</th>
                                    <th>Yeni Seçim</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bykList as $byk): ?>
                                    <tr <?php echo ($byk['byk_kodu'] === 'AT') ? 'class="table-warning"' : ''; ?>>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($byk['byk_kodu']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($byk['byk_adi']); ?></strong>
                                            <?php if ($byk['byk_kodu'] === 'AT'): ?>
                                                <span class="badge bg-primary ms-2">ANA TEŞKİLAT</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo $byk['mevcut_baskan_adi'] ? htmlspecialchars($byk['mevcut_baskan_adi']) : '-'; ?>
                                        </td>
                                        <td>
                                            <select name="muhasebe_baskani[<?php echo $byk['byk_id']; ?>]" class="form-select select2-user">
                                                <option value="">-- Seçiniz --</option>
                                                <?php foreach ($users as $u): ?>
                                                    <option value="<?php echo $u['kullanici_id']; ?>" 
                                                        <?php echo ($byk['muhasebe_baskani_id'] == $u['kullanici_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($u['ad'] . ' ' . $u['soyad']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
// Eğer select2 kütüphanesi varsa başlatılabilir
// $(document).ready(function() { $('.select2-user').select2(); });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

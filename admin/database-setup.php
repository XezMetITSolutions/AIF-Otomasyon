<?php
/**
 * Database Setup - Tablo Oluşturma Sayfası
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Database Setup';

$messages = [];
$errors = [];

// Setup işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    try {
        // 1. Raggal Talepleri Tablosu
        $db->query("
            CREATE TABLE IF NOT EXISTS `raggal_talepleri` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `kullanici_id` INT NOT NULL,
                `baslangic_tarihi` DATETIME NOT NULL,
                `bitis_tarihi` DATETIME NOT NULL,
                `aciklama` TEXT,
                `durum` VARCHAR(50) DEFAULT 'beklemede',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = "✓ Raggal talepleri tablosu oluşturuldu/kontrol edildi.";

        // 2. Demirbaş Talepleri Tablosu
        $db->query("
            CREATE TABLE IF NOT EXISTS `demirbas_talepleri` (
                `talep_id` INT AUTO_INCREMENT PRIMARY KEY,
                `kullanici_id` INT NOT NULL,
                `demirbas_id` INT DEFAULT NULL,
                `baslik` VARCHAR(255),
                `aciklama` TEXT,
                `baslangic_tarihi` DATETIME DEFAULT NULL,
                `bitis_tarihi` DATETIME DEFAULT NULL,
                `durum` VARCHAR(50) DEFAULT 'bekliyor',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = "✓ Demirbaş talepleri tablosu oluşturuldu/kontrol edildi.";

        // 3. Foreign key için demirbaş tablosunu kontrol et ve ekle
        try {
            $db->query("
                ALTER TABLE `demirbas_talepleri` 
                ADD CONSTRAINT `fk_demirbas_talep_item` 
                FOREIGN KEY (`demirbas_id`) REFERENCES `demirbaslar`(`demirbas_id`) ON DELETE SET NULL
            ");
            $messages[] = "✓ Demirbaş foreign key eklendi.";
        } catch (Exception $e) {
            // Foreign key zaten varsa hata vermez
            if (strpos($e->getMessage(), 'Duplicate key') === false) {
                $messages[] = "ℹ Foreign key zaten mevcut veya eklenemedi: " . $e->getMessage();
            }
        }

        $messages[] = "<strong>✓ Tüm tablolar başarıyla oluşturuldu!</strong>";

    } catch (Exception $e) {
        $errors[] = "Hata: " . $e->getMessage();
    }
}

// Mevcut tabloları kontrol et
$tableStatus = [];
try {
    $result = $db->fetchAll("SHOW TABLES LIKE 'raggal_talepleri'");
    $tableStatus['raggal_talepleri'] = !empty($result);
} catch (Exception $e) {
    $tableStatus['raggal_talepleri'] = false;
}

try {
    $result = $db->fetchAll("SHOW TABLES LIKE 'demirbas_talepleri'");
    $tableStatus['demirbas_talepleri'] = !empty($result);
} catch (Exception $e) {
    $tableStatus['demirbas_talepleri'] = false;
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-database me-2"></i>Database Setup
            </h1>
        </div>

        <?php if (!empty($messages)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php foreach ($messages as $msg): ?>
                    <div><?php echo $msg; ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo $err; ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Tablo Durumu</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tablo Adı</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>raggal_talepleri</code></td>
                                    <td>
                                        <?php if ($tableStatus['raggal_talepleri']): ?>
                                            <span class="badge bg-success">✓ Mevcut</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Yok</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>demirbas_talepleri</code></td>
                                    <td>
                                        <?php if ($tableStatus['demirbas_talepleri']): ?>
                                            <span class="badge bg-success">✓ Mevcut</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Yok</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="card-title mb-0">Kurulum</h5>
                    </div>
                    <div class="card-body">
                        <p>Bu sayfa eksik veritabanı tablolarını otomatik olarak oluşturur.</p>
                        <p><strong>Oluşturulacak Tablolar:</strong></p>
                        <ul>
                            <li><code>raggal_talepleri</code> - Raggal rezervasyon talepleri</li>
                            <li><code>demirbas_talepleri</code> - Demirbaş rezervasyon talepleri</li>
                        </ul>
                        
                        <form method="POST" onsubmit="return confirm('Tabloları oluşturmak istediğinize emin misiniz?');">
                            <button type="submit" name="setup" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-cog me-2"></i>Tabloları Oluştur / Kontrol Et
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tablo Yapıları</h5>
                    </div>
                    <div class="card-body">
                        <h6>raggal_talepleri</h6>
                        <pre class="bg-light p-3"><code>CREATE TABLE `raggal_talepleri` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `baslangic_tarihi` DATETIME NOT NULL,
  `bitis_tarihi` DATETIME NOT NULL,
  `aciklama` TEXT,
  `durum` VARCHAR(50) DEFAULT 'beklemede',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`)
)</code></pre>

                        <h6 class="mt-4">demirbas_talepleri</h6>
                        <pre class="bg-light p-3"><code>CREATE TABLE `demirbas_talepleri` (
  `talep_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kullanici_id` INT NOT NULL,
  `demirbas_id` INT DEFAULT NULL,
  `baslik` VARCHAR(255),
  `aciklama` TEXT,
  `baslangic_tarihi` DATETIME DEFAULT NULL,
  `bitis_tarihi` DATETIME DEFAULT NULL,
  `durum` VARCHAR(50) DEFAULT 'bekliyor',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar`(`kullanici_id`),
  FOREIGN KEY (`demirbas_id`) REFERENCES `demirbaslar`(`demirbas_id`)
)</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$db = Database::getInstance();
$pageTitle = 'Divan Üyelerini Güncelle';

$names = [
    'Yasin Çakmak',
    'Fatih Demir',
    'Ali Gümüş',
    'Volkan Meral',
    'Namık Demirkıran',
    'Sinan Yiğit',
    'Ömer Kutlucan',
    'Hüseyin Ayhan',
    'Hatice Armağan',
    'Selda Avcı',
    'Fikret Özcan',
    'Fahrettin Yıldız',
    'Adem İmamoğlu',
    'İbrahim Çetin',
    'Hüseyin Akyıldız',
    'Umut Burçak'
];

// Kolon kontrolü ve ekleme
try {
    $checkColumn = $db->fetch("SHOW COLUMNS FROM `kullanicilar` LIKE 'divan_uyesi'");
    if (!$checkColumn) {
        $db->query("ALTER TABLE `kullanicilar` ADD COLUMN `divan_uyesi` TINYINT(1) DEFAULT 0 AFTER `aktif`");
    }
} catch (Exception $e) {
    // Hata oluşursa devam et, belki yetki yoktur ama kolon vardır
}

$results = [];

if (isset($_POST['update'])) {
    foreach ($names as $fullName) {
        // İsim soyisim ayrıştırma
        $parts = explode(' ', $fullName);
        $soyad = array_pop($parts);
        $ad = implode(' ', $parts);

        // Tam eşleşme kontrolü
        $user = $db->fetch("SELECT * FROM kullanicilar WHERE CONCAT(ad, ' ', soyad) = ?", [$fullName]);

        if ($user) {
            $db->query("UPDATE kullanicilar SET divan_uyesi = 1 WHERE kullanici_id = ?", [$user['kullanici_id']]);
            $results[] = ['name' => $fullName, 'status' => 'success', 'message' => 'Güncellendi'];
        } else {
            $results[] = ['name' => $fullName, 'status' => 'danger', 'message' => 'Kullanıcı Bulunamadı'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Divan Üyelerini Toplu Güncelle</h1>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (empty($results)): ?>
                    <p>Aşağıdaki kullanıcılar "Divan Üyesi" olarak işaretlenecektir:</p>
                    <ul>
                        <?php foreach ($names as $name): ?>
                            <li><?php echo htmlspecialchars($name); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <form method="POST">
                        <button type="submit" name="update" class="btn btn-primary">
                            <i class="fas fa-sync me-2"></i>Güncellemeyi Başlat
                        </button>
                    </form>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>İsim</th>
                                    <th>Durum</th>
                                    <th>Mesaj</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $result['status']; ?>">
                                                <?php echo $result['status'] == 'success' ? 'Başarılı' : 'Hata'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($result['message']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="/admin/kullanicilar.php" class="btn btn-success mt-3">Kullanıcı Listesine Dön</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

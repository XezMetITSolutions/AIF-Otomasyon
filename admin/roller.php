<?php
/**
 * Ana Yönetici - Rol & Yetki Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Rol & Yetki Yönetimi';

// Roller
$roller = $db->fetchAll("
    SELECT r.*, COUNT(k.kullanici_id) as kullanici_sayisi
    FROM roller r
    LEFT JOIN kullanicilar k ON r.rol_id = k.rol_id AND k.aktif = 1
    GROUP BY r.rol_id
    ORDER BY r.rol_yetki_seviyesi DESC
");

include __DIR__ . '/../includes/header.php';
?>

<main class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-2 p-0">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-shield me-2"></i>Rol & Yetki Yönetimi
                </h1>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($roller); ?></strong> rol
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rol Adı</th>
                                    <th>Açıklama</th>
                                    <th>Yetki Seviyesi</th>
                                    <th>Kullanıcı Sayısı</th>
                                    <th>Oluşturma Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roller as $rol): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rol['rol_adi']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($rol['rol_aciklama'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $rol['rol_yetki_seviyesi'] == 3 ? 'danger' : ($rol['rol_yetki_seviyesi'] == 2 ? 'warning' : 'info'); ?>">
                                                Seviye <?php echo $rol['rol_yetki_seviyesi']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $rol['kullanici_sayisi']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($rol['olusturma_tarihi'])); ?></td>
                                        <td>
                                            <a href="/admin/rol-yetkiler.php?id=<?php echo $rol['rol_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-key"></i> Yetkiler
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


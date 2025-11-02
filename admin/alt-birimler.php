<?php
/**
 * Ana Yönetici - Alt Birimler Yönetimi (byk_sub_units tablosu)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Alt Birimler Yönetimi';

// Alt birimler (byk_sub_units tablosundan)
$altBirimler = [];

try {
    // Doğrudan byk_sub_units tablosunu kullan
    $altBirimler = $db->fetchAll("
        SELECT 
            bsu.id,
            bsu.byk_category_id,
            bsu.name,
            bsu.description,
            bsu.created_at,
            bsu.updated_at,
            bc.name as byk_adi,
            bc.code as byk_kodu,
            bc.color as byk_renk
        FROM byk_sub_units bsu
        INNER JOIN byk_categories bc ON bsu.byk_category_id = bc.id
        ORDER BY bc.code ASC, bsu.name ASC
    ");
} catch (Exception $e) {
    // byk_sub_units yoksa veya hata varsa eski alt_birimler tablosunu kullan
    try {
        $altBirimler = $db->fetchAll("
            SELECT ab.*, b.byk_adi
            FROM alt_birimler ab
            INNER JOIN byk b ON ab.byk_id = b.byk_id
            ORDER BY b.byk_adi, ab.alt_birim_adi
        ");
    } catch (Exception $e2) {
        $altBirimler = [];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-sitemap me-2"></i>Alt Birimler Yönetimi
            </h1>
            <a href="/admin/alt-birim-ekle.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Yeni Alt Birim Ekle
            </a>
        </div>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>İşlem başarıyla tamamlandı.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    $errorMessages = [
                        'notfound' => 'Alt birim bulunamadı.',
                        'permission' => 'Bu işlem için yetkiniz bulunmamaktadır.'
                    ];
                    echo $errorMessages[$_GET['error']] ?? 'Bir hata oluştu.';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                Toplam: <strong><?php echo count($altBirimler); ?></strong> alt birim
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>BYK</th>
                                <th>Alt Birim Adı</th>
                                <th>Açıklama</th>
                                <th>Oluşturma Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($altBirimler)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Henüz alt birim eklenmemiş.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($altBirimler as $altBirim): ?>
                                    <tr>
                                        <td>
                                            <span class="badge" style="background-color: <?php echo htmlspecialchars($altBirim['byk_renk'] ?? '#009872'); ?>; color: white;">
                                                <?php echo htmlspecialchars($altBirim['byk_adi'] ?? ''); ?>
                                            </span>
                                            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($altBirim['byk_kodu'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($altBirim['name'] ?? $altBirim['alt_birim_adi'] ?? ''); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($altBirim['description'] ?? $altBirim['aciklama'] ?? '-'); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                                $tarih = $altBirim['created_at'] ?? $altBirim['olusturma_tarihi'] ?? null;
                                                echo $tarih ? date('d.m.Y', strtotime($tarih)) : '-';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="/admin/alt-birim-duzenle.php?id=<?php echo $altBirim['id'] ?? $altBirim['alt_birim_id']; ?>" class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger confirm-delete" 
                                                        data-id="<?php echo $altBirim['id'] ?? $altBirim['alt_birim_id']; ?>" 
                                                        data-type="alt_birim" 
                                                        data-name="<?php echo htmlspecialchars($altBirim['name'] ?? $altBirim['alt_birim_adi'] ?? ''); ?>" 
                                                        title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

<?php
/**
 * Ana Yönetici - BYK Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'BYK Yönetimi';

// BYK Kategorileri (byk_categories tablosundan)
// Doğrudan byk_categories tablosunu kullan - basit sorgu
$bykList = [];

try {
    // En basit sorgu - sadece byk_categories tablosunu kullan
    $bykList = $db->fetchAll("
        SELECT 
            id,
            code,
            name,
            color,
            description,
            created_at,
            updated_at,
            0 as kullanici_sayisi
        FROM byk_categories
        ORDER BY code ASC
    ");
    
    // Her BYK için kullanıcı sayısını ayrı ayrı hesapla
    foreach ($bykList as &$byk) {
        $kullaniciSayisi = 0;
        
        // users tablosundan say
        try {
            $usersCount = $db->fetch("SELECT COUNT(*) as cnt FROM users WHERE byk_category_id = ? AND status = 'active'", [$byk['id']]);
            if ($usersCount) {
                $kullaniciSayisi += (int)$usersCount['cnt'];
            }
        } catch (Exception $e) {
            // users tablosu yoksa devam et
        }
        
        // kullanicilar tablosundan say (byk_kodu ile eşleştir)
        try {
            $kullanicilarCount = $db->fetch("
                SELECT COUNT(*) as cnt 
                FROM kullanicilar k
                INNER JOIN byk b ON k.byk_id = b.byk_id
                WHERE b.byk_kodu = ? AND k.aktif = 1
            ", [$byk['code']]);
            if ($kullanicilarCount) {
                $kullaniciSayisi += (int)$kullanicilarCount['cnt'];
            }
        } catch (Exception $e) {
            // byk tablosu yoksa veya hata varsa devam et
        }
        
        $byk['kullanici_sayisi'] = $kullaniciSayisi;
    }
    unset($byk);
    
} catch (Exception $e) {
    // byk_categories tablosu yoksa veya hata varsa eski byk tablosunu dene
    try {
        $bykList = $db->fetchAll("
            SELECT b.*, COUNT(k.kullanici_id) as kullanici_sayisi
            FROM byk b
            LEFT JOIN kullanicilar k ON b.byk_id = k.byk_id AND k.aktif = 1
            GROUP BY b.byk_id
            ORDER BY b.olusturma_tarihi DESC
        ");
    } catch (Exception $e2) {
        $bykList = [];
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
                    <i class="fas fa-building me-2"></i>BYK Yönetimi
                </h1>
                <a href="/admin/byk-ekle.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Yeni BYK Ekle
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Toplam: <strong><?php echo count($bykList); ?></strong> BYK
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>BYK Adı</th>
                                    <th>BYK Kodu</th>
                                    <th>Kullanıcı Sayısı</th>
                                    <th>Renk Kodu</th>
                                    <th>Durum</th>
                                    <th>Oluşturma Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bykList)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Henüz BYK eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bykList as $byk): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($byk['name'] ?? $byk['byk_adi'] ?? ''); ?></td>
                                            <td><code><?php echo htmlspecialchars($byk['code'] ?? $byk['byk_kodu'] ?? ''); ?></code></td>
                                            <td><?php echo $byk['kullanici_sayisi'] ?? 0; ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo htmlspecialchars($byk['color'] ?? $byk['renk_kodu'] ?? '#007bff'); ?>;">
                                                    <?php echo htmlspecialchars($byk['color'] ?? $byk['renk_kodu'] ?? '#007bff'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    Aktif
                                                </span>
                                            </td>
                                            <td><?php echo isset($byk['created_at']) ? date('d.m.Y', strtotime($byk['created_at'])) : (isset($byk['olusturma_tarihi']) ? date('d.m.Y', strtotime($byk['olusturma_tarihi'])) : '-'); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/admin/byk-duzenle.php?id=<?php echo $byk['id'] ?? $byk['byk_id']; ?>" class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger confirm-delete" data-id="<?php echo $byk['id'] ?? $byk['byk_id']; ?>" data-type="byk" data-name="<?php echo htmlspecialchars($byk['name'] ?? $byk['byk_adi'] ?? ''); ?>" title="Sil">
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


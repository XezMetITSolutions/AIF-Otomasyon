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

// Filtreleme
$search = $_GET['search'] ?? '';
$bykFilter = $_GET['byk'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(bsu.name LIKE ? OR bsu.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($bykFilter) {
    $where[] = "bsu.byk_category_id = ?";
    $params[] = $bykFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// BYK listesi (filtre için)
try {
    $bykList = $db->fetchAll("SELECT id, code, name, color FROM byk_categories ORDER BY code");
} catch (Exception $e) {
    $bykList = [];
}

// Alt birimler (byk_sub_units tablosundan)
$altBirimler = [];

try {
    // Doğrudan byk_sub_units tablosunu kullan
    $query = "
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
        $whereClause
        ORDER BY bc.code ASC, bsu.name ASC
    ";
    
    $altBirimler = $db->fetchAll($query, $params);
    
    // Her alt birim için description'dan sorumlu bilgisini çıkar ve kullanıcı ID'sini bul
    foreach ($altBirimler as &$altBirim) {
        $sorumlu = null;
        $sorumluId = null;
        $description = $altBirim['description'] ?? '';
        
        // Description formatı: "BYК_CODE - Alt Birim Adı | Sorumlu: Ad Soyad"
        if (strpos($description, '| Sorumlu:') !== false) {
            $parts = explode('| Sorumlu:', $description);
            if (isset($parts[1])) {
                $sorumlu = trim($parts[1]);
                
                // Sorumlu ad-soyad ile kullanıcıyı bul
                try {
                    $nameParts = explode(' ', $sorumlu, 2);
                    if (count($nameParts) == 2) {
                        $kullanici = $db->fetch("
                            SELECT kullanici_id 
                            FROM kullanicilar 
                            WHERE ad = ? AND soyad = ? AND aktif = 1
                            LIMIT 1
                        ", [$nameParts[0], $nameParts[1]]);
                        
                        if ($kullanici) {
                            $sorumluId = $kullanici['kullanici_id'];
                        }
                    }
                } catch (Exception $e) {
                    // Hata durumunda devam et
                }
            }
        }
        
        $altBirim['sorumlu'] = $sorumlu;
        $altBirim['sorumlu_id'] = $sorumluId;
    }
    unset($altBirim);
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
        
        <!-- Filtreler -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Arama</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Alt birim adı, açıklama...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BYK</label>
                        <select class="form-select" name="byk">
                            <option value="">Tüm BYK'lar</option>
                            <?php foreach ($bykList as $byk): ?>
                                <option value="<?php echo $byk['id']; ?>" <?php echo $bykFilter == $byk['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($byk['name']); ?> (<?php echo htmlspecialchars($byk['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filtrele
                        </button>
                        <?php if ($search || $bykFilter): ?>
                            <a href="/admin/alt-birimler.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Temizle
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
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
                                <th>Yetkilisi</th>
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
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($altBirim['name'] ?? $altBirim['alt_birim_adi'] ?? ''); ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                                $sorumlu = $altBirim['sorumlu'] ?? null;
                                                $sorumluId = $altBirim['sorumlu_id'] ?? null;
                                                
                                                if (!empty($sorumlu)) {
                                                    if ($sorumluId) {
                                                        // Profil linki ile göster
                                                        echo '<a href="/admin/kullanici-duzenle.php?id=' . $sorumluId . '" class="text-primary text-decoration-none">';
                                                        echo '<i class="fas fa-user me-1"></i>' . htmlspecialchars($sorumlu);
                                                        echo '</a>';
                                                    } else {
                                                        // Profil linki olmadan göster
                                                        echo '<span class="text-primary"><i class="fas fa-user me-1"></i>' . htmlspecialchars($sorumlu) . '</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                            ?>
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

<?php
/**
 * Üye - Etkinlikler
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();
Middleware::requireModulePermission('uye_etkinlikler');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Etkinlikler';
$bykId = $user['byk_id'];
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$tarihFiltre = $_GET['tarih'] ?? 'yaklasan';
$gecerliFiltreler = ['yaklasan', 'bugun', 'gecmis', 'tum'];
if (!in_array($tarihFiltre, $gecerliFiltreler, true)) {
    $tarihFiltre = 'yaklasan';
}

$etkinlikler = [];
$seciliEtkinlik = null;
$orderBy = $tarihFiltre === 'gecmis' ? 'DESC' : 'ASC';
$conditions = ["e.byk_id = ?"];
$params = [$bykId];

switch ($tarihFiltre) {
    case 'bugun':
        $conditions[] = "DATE(e.baslangic_tarihi) = CURDATE()";
        break;
    case 'gecmis':
        $conditions[] = "e.baslangic_tarihi < NOW()";
        break;
    case 'tum':
        // ek koşul yok
        break;
    default: // yaklaşan
        $conditions[] = "e.baslangic_tarihi >= NOW()";
        break;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

if ($bykId) {
    $etkinlikler = $db->fetchAll("
        SELECT e.*, ab.alt_birim_adi
        FROM etkinlikler e
        LEFT JOIN alt_birimler ab ON e.alt_birim_id = ab.alt_birim_id
        $whereClause
        ORDER BY e.baslangic_tarihi $orderBy
        LIMIT 100
    ", $params);

    if ($selectedId) {
        $seciliEtkinlik = $db->fetch("
            SELECT e.*, ab.alt_birim_adi
            FROM etkinlikler e
            LEFT JOIN alt_birimler ab ON e.alt_birim_id = ab.alt_birim_id
            WHERE e.etkinlik_id = ? AND e.byk_id = ?
        ", [$selectedId, $bykId]);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-calendar me-2"></i>Etkinlikler
                </h1>
                <small class="text-muted">BYK takviminizdeki etkinlikler</small>
            </div>
            <div class="btn-group">
                <a href="?tarih=yaklasan" class="btn btn-outline-primary <?php echo $tarihFiltre === 'yaklasan' ? 'active' : ''; ?>">
                    <i class="fas fa-hourglass-start me-1"></i>Yaklaşan
                </a>
                <a href="?tarih=bugun" class="btn btn-outline-primary <?php echo $tarihFiltre === 'bugun' ? 'active' : ''; ?>">
                    <i class="fas fa-sun me-1"></i>Bugün
                </a>
                <a href="?tarih=gecmis" class="btn btn-outline-primary <?php echo $tarihFiltre === 'gecmis' ? 'active' : ''; ?>">
                    <i class="fas fa-history me-1"></i>Geçmiş
                </a>
                <a href="?tarih=tum" class="btn btn-outline-primary <?php echo $tarihFiltre === 'tum' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group me-1"></i>Tümü
                </a>
            </div>
        </div>
        
        <?php if (!$bykId): ?>
            <div class="alert alert-warning">
                Etkinlikleri görüntülemek için BYK atanmış olmanız gerekir. Lütfen yönetici ile iletişime geçin.
            </div>
        <?php elseif ($selectedId && !$seciliEtkinlik): ?>
            <div class="alert alert-danger">
                İstenen etkinlik bulunamadı veya yetkiniz yok.
            </div>
        <?php endif; ?>
        
        <?php if ($seciliEtkinlik): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?php echo htmlspecialchars($seciliEtkinlik['baslik']); ?></strong>
                    <span class="badge bg-info"><?php echo date('d.m.Y H:i', strtotime($seciliEtkinlik['baslangic_tarihi'])); ?></span>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <?php echo nl2br(htmlspecialchars($seciliEtkinlik['aciklama'] ?? 'Açıklama bulunmuyor.')); ?>
                    </p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Başlangıç</small>
                                <strong><?php echo date('d.m.Y H:i', strtotime($seciliEtkinlik['baslangic_tarihi'])); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Bitiş</small>
                                <strong><?php echo date('d.m.Y H:i', strtotime($seciliEtkinlik['bitis_tarihi'])); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Konum</small>
                                <strong><?php echo htmlspecialchars($seciliEtkinlik['konum'] ?? '-'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Toplam: <strong><?php echo count($etkinlikler); ?></strong> etkinlik</span>
                <?php if ($selectedId): ?>
                    <a href="/uye/etkinlikler.php?tarih=<?php echo urlencode($tarihFiltre); ?>" class="btn btn-sm btn-outline-secondary">
                        Seçimi Temizle
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($etkinlikler)): ?>
                    <p class="text-center text-muted mb-0">Listelenecek etkinlik bulunmamaktadır.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>Alt Birim</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>Konum</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($etkinlikler as $etkinlik): ?>
                                    <tr class="<?php echo $selectedId === (int) $etkinlik['etkinlik_id'] ? 'table-primary' : ''; ?>">
                                        <td><?php echo htmlspecialchars($etkinlik['baslik']); ?></td>
                                        <td><?php echo htmlspecialchars($etkinlik['alt_birim_adi'] ?? '-'); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($etkinlik['baslangic_tarihi'])); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($etkinlik['bitis_tarihi'])); ?></td>
                                        <td><?php echo htmlspecialchars($etkinlik['konum'] ?? '-'); ?></td>
                                        <td class="text-end">
                                            <a href="/uye/etkinlikler.php?id=<?php echo $etkinlik['etkinlik_id']; ?>&tarih=<?php echo urlencode($tarihFiltre); ?>" class="btn btn-sm btn-outline-primary">
                                                Detay
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>



<?php
/**
 * Admin - Şube Ziyaretleri
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Yetki kontrolü
Middleware::requireSuperAdmin();

// Yönlendir (Kod tekrarını önlemek için panel'dekini kullanabiliriz ama admin yetkisi ile)
// Ancak panel'deki dosyalar include/sidebar.php kullanıyor ve o dosya da role'e göre davranıyor.
// Bu yüzden direkt panel'dekini buraya kopyalayıp yetki kontrolünü admin'e çekmek daha güvenli.

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

// AT Birimi Kontrolü
$isAT = false;
$userByk = $db->fetch("SELECT b.byk_kodu FROM byk b WHERE b.byk_id = ?", [$user['byk_id']]);
if ($userByk && $userByk['byk_kodu'] === 'AT') {
    $isAT = true;
}

if (!$isAT) {
    header('Location: /access-denied.php');
    exit;
}

$pageTitle = 'Şube Ziyaretleri Yönetimi';

// Turkish Date Helper
$trMonths = [
    'Jan' => 'Ocak', 'Feb' => 'Şubat', 'Mar' => 'Mart', 'Apr' => 'Nisan',
    'May' => 'Mayıs', 'Jun' => 'Haziran', 'Jul' => 'Temmuz', 'Aug' => 'Ağustos',
    'Sep' => 'Eylül', 'Oct' => 'Ekim', 'Nov' => 'Kasım', 'Dec' => 'Aralık'
];

// Admin her şeyi yönetebilir
$canManage = true;

// Filters
$tab = $_GET['tab'] ?? 'planlanan';
$grupFilter = $_GET['grup'] ?? '';
$bykFilter = $_GET['byk'] ?? '';

// Gruplar ve BYK'lar
$gruplar = $db->fetchAll("SELECT grup_id, grup_adi, renk_kodu FROM ziyaret_gruplari ORDER BY grup_adi");
$bykList = $db->fetchAll("SELECT byk_id, byk_adi FROM byk ORDER BY byk_adi");

// Query
$where = [];
$params = [];
if ($tab === 'planlanan') $where[] = "z.durum = 'planlandi'";
else $where[] = "z.durum = 'tamamlandi'";

if ($grupFilter) { $where[] = "z.grup_id = ?"; $params[] = $grupFilter; }
if ($bykFilter) { $where[] = "z.byk_id = ?"; $params[] = $bykFilter; }

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$ziyaretler = $db->fetchAll("
    SELECT z.*, b.byk_adi, g.grup_adi, g.renk_kodu, CONCAT(u.ad, ' ', u.soyad) as olusturan
    FROM sube_ziyaretleri z
    INNER JOIN byk b ON z.byk_id = b.byk_id
    INNER JOIN ziyaret_gruplari g ON z.grup_id = g.grup_id
    INNER JOIN kullanicilar u ON z.olusturan_id = u.kullanici_id
    $whereClause
    ORDER BY z.ziyaret_tarihi DESC
", $params);

include __DIR__ . '/../includes/header.php';
// Admin sidebar'ı için path'leri ayarla (sidebar.php içinde zaten logic var)
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="fas fa-map-location-dot me-2 text-primary"></i>Şube Ziyaretleri Yönetimi</h1>
                <p class="text-muted small mb-0">Tüm bölgelerdeki şube ziyaretlerini takip edin.</p>
            </div>
            <div class="btn-group">
                <a href="/panel/ziyaret-gruplari.php" class="btn btn-outline-primary"><i class="fas fa-users-rectangle me-2"></i>Gruplar</a>
                <a href="/panel/yeni-ziyaret.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Yeni Ziyaret</a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                    <div class="col-auto">
                        <ul class="nav nav-pills small">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'planlanan' ? 'active' : ''; ?>" href="?tab=planlanan">Planlanan</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'tamamlanan' ? 'active' : ''; ?>" href="?tab=tamamlanan">Tamamlanan</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <select name="grup" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Tüm Gruplar</option>
                            <?php foreach ($gruplar as $g): ?>
                                <option value="<?php echo $g['grup_id']; ?>" <?php echo $grupFilter == $g['grup_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['grup_adi']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="byk" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Tüm Şubeler</option>
                            <?php foreach ($bykList as $b): ?>
                                <option value="<?php echo $b['byk_id']; ?>" <?php echo $bykFilter == $b['byk_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['byk_adi']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Şube</th>
                        <th>Grup</th>
                        <th>Planlayan</th>
                        <th>Durum</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ziyaretler)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Kayıt bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ziyaretler as $z): ?>
                            <tr>
                                <td><?php echo date('d.m.Y', strtotime($z['ziyaret_tarihi'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($z['byk_adi']); ?></strong></td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo $z['renk_kodu']; ?>; color: white;">
                                        <?php echo htmlspecialchars($z['grup_adi']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($z['olusturan']); ?></td>
                                <td>
                                    <?php if ($z['durum'] === 'planlandi'): ?>
                                        <span class="badge bg-warning">Planlandı</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Tamamlandı</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($z['durum'] === 'tamamlandi'): ?>
                                            <a href="/panel/ziyaret-detay.php?id=<?php echo $z['ziyaret_id']; ?>" class="btn btn-info text-white"><i class="fas fa-eye"></i></a>
                                        <?php else: ?>
                                            <a href="/panel/yeni-ziyaret.php?rapor=<?php echo $z['ziyaret_id']; ?>" class="btn btn-primary"><i class="fas fa-file-pen"></i></a>
                                        <?php endif; ?>
                                        <a href="/panel/yeni-ziyaret.php?edit=<?php echo $z['ziyaret_id']; ?>" class="btn btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                        <button class="btn btn-outline-danger" onclick="deleteZiyaret(<?php echo $z['ziyaret_id']; ?>)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function deleteZiyaret(id) {
    if (confirm('Bu ziyareti silmek istediğinize emin misiniz?')) {
        // Silme işlemi için bir API veya sayfa gerekebilir. 
        // Şimdilik pas geçiyorum veya basit bir submit yapabiliriz.
        alert('Silme özelliği henüz aktif değil.');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

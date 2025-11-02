<?php
/**
 * Ana Yönetici - Çalışma Takvimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Çalışma Takvimi';

// Filtreleme
$search = $_GET['search'] ?? '';
$bykFilter = $_GET['byk'] ?? '';
$monthFilter = $_GET['ay'] ?? '';
$yearFilter = $_GET['yil'] ?? date('Y');

$where = [];
$params = [];

if ($search) {
    $where[] = "(e.baslik LIKE ? OR e.aciklama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($bykFilter) {
    // bykFilter byk_categories.id ise, önce byk_kodu bul sonra byk_id'yi bul
    try {
        $bykCategory = $db->fetch("SELECT code FROM byk_categories WHERE id = ?", [$bykFilter]);
        if ($bykCategory) {
            $byk = $db->fetch("SELECT byk_id FROM byk WHERE byk_kodu = ?", [$bykCategory['code']]);
            if ($byk) {
                $where[] = "e.byk_id = ?";
                $params[] = $byk['byk_id'];
            } else {
                $where[] = "e.byk_id = ?";
                $params[] = $bykFilter;
            }
        } else {
            $where[] = "e.byk_id = ?";
            $params[] = $bykFilter;
        }
    } catch (Exception $e) {
        $where[] = "e.byk_id = ?";
        $params[] = $bykFilter;
    }
}

if ($monthFilter) {
    $where[] = "MONTH(e.baslangic_tarihi) = ?";
    $params[] = $monthFilter;
}

if ($yearFilter) {
    $where[] = "YEAR(e.baslangic_tarihi) = ?";
    $params[] = $yearFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// BYK listesi (filtre için)
try {
    $bykList = $db->fetchAll("SELECT id as byk_id, name as byk_adi, code as byk_kodu, color as byk_renk FROM byk_categories ORDER BY code");
} catch (Exception $e) {
    $bykList = $db->fetchAll("SELECT * FROM byk WHERE aktif = 1 ORDER BY byk_adi");
}

// Etkinlikler
try {
    $etkinlikler = $db->fetchAll("
        SELECT e.*, 
               COALESCE(bc.name, b.byk_adi, '-') as byk_adi,
               COALESCE(bc.code, b.byk_kodu, '') as byk_kodu,
               COALESCE(bc.color, b.renk_kodu, '#009872') as byk_renk,
               CONCAT(u.ad, ' ', u.soyad) as olusturan
        FROM etkinlikler e
        LEFT JOIN byk b ON e.byk_id = b.byk_id
        LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
        LEFT JOIN kullanicilar u ON e.olusturan_id = u.kullanici_id
        $whereClause
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 500
    ", $params);
} catch (Exception $e) {
    $etkinlikler = $db->fetchAll("
        SELECT e.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as olusturan
        FROM etkinlikler e
        INNER JOIN byk b ON e.byk_id = b.byk_id
        INNER JOIN kullanicilar u ON e.olusturan_id = u.kullanici_id
        $whereClause
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 500
    ", $params);
}

// Etkinlikleri JSON formatına çevir (takvim için)
$calendarEvents = [];
foreach ($etkinlikler as $etkinlik) {
    $baslangic = new DateTime($etkinlik['baslangic_tarihi']);
    $bitis = new DateTime($etkinlik['bitis_tarihi']);
    
    $calendarEvents[] = [
        'id' => $etkinlik['etkinlik_id'],
        'title' => $etkinlik['baslik'],
        'start' => $baslangic->format('Y-m-d\TH:i:s'),
        'end' => $bitis->format('Y-m-d\TH:i:s'),
        'allDay' => (date('H:i:s', strtotime($etkinlik['baslangic_tarihi'])) == '00:00:00' && 
                     date('H:i:s', strtotime($etkinlik['bitis_tarihi'])) == '23:59:59'),
        'backgroundColor' => $etkinlik['byk_renk'] ?? '#009872',
        'borderColor' => $etkinlik['byk_renk'] ?? '#009872',
        'textColor' => '#ffffff',
        'extendedProps' => [
            'byk' => $etkinlik['byk_adi'] ?? '',
            'byk_kodu' => $etkinlik['byk_kodu'] ?? '',
            'konum' => $etkinlik['konum'] ?? '',
            'aciklama' => $etkinlik['aciklama'] ?? '',
            'olusturan' => $etkinlik['olusturan'] ?? ''
        ]
    ];
}

$pageSpecificCSS = [
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css'
];

$pageSpecificJS = [
    'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'
];

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Çalışma Takvimi
                </h1>
                <div class="btn-group">
                    <a href="/admin/etkinlik-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Yeni Etkinlik Ekle
                    </a>
                    <a href="/database/import-events-2026.php" class="btn btn-success">
                        <i class="fas fa-file-import me-2"></i>2026 Etkinliklerini Import Et
                    </a>
                </div>
            </div>
            
            <!-- Filtreler -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Arama</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Etkinlik adı, açıklama...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">BYK</label>
                            <select class="form-select" name="byk">
                                <option value="">Tüm BYK'lar</option>
                                <?php foreach ($bykList as $byk): ?>
                                    <option value="<?php echo $byk['byk_id']; ?>" <?php echo $bykFilter == $byk['byk_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($byk['byk_adi'] ?? $byk['byk_kodu'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ay</label>
                            <select class="form-select" name="ay">
                                <option value="">Tüm Aylar</option>
                                <?php
                                $aylar = [
                                    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                                    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                                    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
                                ];
                                foreach ($aylar as $num => $ayAdi):
                                ?>
                                    <option value="<?php echo $num; ?>" <?php echo $monthFilter == $num ? 'selected' : ''; ?>>
                                        <?php echo $ayAdi; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Yıl</label>
                            <input type="number" class="form-control" name="yil" value="<?php echo htmlspecialchars($yearFilter); ?>" min="2020" max="2030">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filtrele
                            </button>
                            <?php if ($search || $bykFilter || $monthFilter || $yearFilter != date('Y')): ?>
                                <a href="/admin/etkinlikler.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Temizle
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Görünüm Seçici -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-info me-2">Toplam: <strong><?php echo count($etkinlikler); ?></strong> etkinlik</span>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="calendarViewBtn">
                                <i class="fas fa-calendar-alt me-1"></i>Takvim
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="listViewBtn">
                                <i class="fas fa-list me-1"></i>Liste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Takvim Görünümü -->
            <div class="card" id="calendarView">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
            
            <!-- Liste Görünümü -->
            <div class="card d-none" id="listView">
                <div class="card-header">
                    Toplam: <strong><?php echo count($etkinlikler); ?></strong> etkinlik
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Başlık</th>
                                    <th>BYK</th>
                                    <th>Konum</th>
                                    <th>Oluşturan</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($etkinlikler)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Henüz etkinlik eklenmemiş.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($etkinlikler as $etkinlik): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('d.m.Y', strtotime($etkinlik['baslangic_tarihi'])); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php 
                                                        $baslangicSaat = date('H:i', strtotime($etkinlik['baslangic_tarihi']));
                                                        $bitisSaat = date('H:i', strtotime($etkinlik['bitis_tarihi']));
                                                        if ($baslangicSaat != '00:00' || $bitisSaat != '23:59') {
                                                            echo $baslangicSaat . ' - ' . $bitisSaat;
                                                        } else {
                                                            echo 'Tüm gün';
                                                        }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($etkinlik['baslik']); ?></strong>
                                                <?php if (!empty($etkinlik['aciklama'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($etkinlik['aciklama'], 0, 50)); ?><?php echo strlen($etkinlik['aciklama']) > 50 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($etkinlik['byk_adi'])): ?>
                                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($etkinlik['byk_renk'] ?? '#009872'); ?>; color: white;">
                                                        <?php echo htmlspecialchars($etkinlik['byk_adi']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($etkinlik['konum']) ? htmlspecialchars($etkinlik['konum']) : '<span class="text-muted">-</span>'; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($etkinlik['olusturan']) ? htmlspecialchars($etkinlik['olusturan']) : '<span class="text-muted">-</span>'; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="/admin/etkinlik-duzenle.php?id=<?php echo $etkinlik['etkinlik_id']; ?>" class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger confirm-delete" 
                                                            data-id="<?php echo $etkinlik['etkinlik_id']; ?>" 
                                                            data-type="etkinlik" 
                                                            data-name="<?php echo htmlspecialchars($etkinlik['baslik']); ?>" 
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


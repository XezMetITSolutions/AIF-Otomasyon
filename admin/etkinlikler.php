<?php
/**
 * Ana Yönetici - Çalışma Takvimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireAuth();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

// Yetki kontrolü
$isSuperAdmin = $user['role'] === 'super_admin';
$canManage = $isSuperAdmin || $auth->hasModulePermission('baskan_etkinlikler');

$pageTitle = 'Çalışma Takvimi';

// Filtreleme
$search = $_GET['search'] ?? '';
$bykFilter = $_GET['byk'] ?? '';
$monthFilter = $_GET['ay'] ?? '';
$yearFilter = $_GET['yil'] ?? '';

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
    $bykList = $db->fetchAll("SELECT id as byk_id, name as byk_adi, code as byk_kodu, color as byk_renk FROM byk_categories WHERE code IN ('AT', 'GT', 'KGT', 'gt', 'KT') ORDER BY code");
} catch (Exception $e) {
    $bykList = $db->fetchAll("SELECT * FROM byk WHERE aktif = 1 AND byk_kodu IN ('AT', 'GT', 'KGT', 'gt', 'KT') ORDER BY byk_adi");
}

// Etkinlikler
// Önce basit sorgu ile etkinlikleri al (collation hatasını önlemek için byk_categories JOIN'i yapmıyoruz)
try {
    $etkinlikler = $db->fetchAll("
        SELECT e.*, 
               COALESCE(b.byk_adi, '-') as byk_adi,
               COALESCE(b.byk_kodu, '') as byk_kodu,
               COALESCE(b.renk_kodu, e.renk_kodu, '#009872') as byk_renk,
               COALESCE(CONCAT(u.ad, ' ', u.soyad), '-') as olusturan
        FROM etkinlikler e
        LEFT JOIN byk b ON e.byk_id = b.byk_id
        LEFT JOIN kullanicilar u ON e.olusturan_id = u.kullanici_id
        $whereClause
        ORDER BY e.baslangic_tarihi ASC
        LIMIT 500
    ", $params);

    // Şimdi her etkinlik için byk_categories'den renk ve isim bilgisini al (collation hatasını önlemek için PHP'de)
    if (!empty($etkinlikler)) {
        try {
            // byk_categories'den tüm BYK bilgilerini al
            $bykCategories = $db->fetchAll("SELECT code, name, color FROM byk_categories");
            $bykCategoryMap = [];
            foreach ($bykCategories as $cat) {
                $bykCategoryMap[$cat['code']] = [
                    'name' => $cat['name'],
                    'color' => $cat['color']
                ];
            }

            // Etkinliklerdeki BYK kodlarına göre bilgileri güncelle
            foreach ($etkinlikler as &$etkinlik) {
                $bykKodu = $etkinlik['byk_kodu'] ?? '';
                if (!empty($bykKodu) && isset($bykCategoryMap[$bykKodu])) {
                    // byk_categories'den gelen bilgileri kullan
                    $etkinlik['byk_adi'] = $bykCategoryMap[$bykKodu]['name'];
                    if (!empty($bykCategoryMap[$bykKodu]['color'])) {
                        $etkinlik['byk_renk'] = $bykCategoryMap[$bykKodu]['color'];
                    }
                }

                // Eğer renk hala boş veya varsayılan ise, etkinlik tablosundaki renk_kodu'nu kullan
                if (empty($etkinlik['byk_renk']) || $etkinlik['byk_renk'] == '#009872') {
                    if (!empty($etkinlik['renk_kodu'])) {
                        $etkinlik['byk_renk'] = $etkinlik['renk_kodu'];
                    }
                }
            }
            unset($etkinlik);
        } catch (Exception $e3) {
            // byk_categories hatası - etkinliklerdeki renk_kodu'nu kullan
            foreach ($etkinlikler as &$etkinlik) {
                if (empty($etkinlik['byk_renk']) || $etkinlik['byk_renk'] == '#009872') {
                    if (!empty($etkinlik['renk_kodu'])) {
                        $etkinlik['byk_renk'] = $etkinlik['renk_kodu'];
                    }
                }
            }
            unset($etkinlik);
        }
    }
} catch (Exception $e) {
    // En basit sorgu - sadece etkinlikler tablosu
    try {
        $etkinlikler = $db->fetchAll("
            SELECT e.*, 
                   '-' as byk_adi,
                   '' as byk_kodu,
                   COALESCE(e.renk_kodu, '#009872') as byk_renk,
                   '-' as olusturan
            FROM etkinlikler e
            $whereClause
            ORDER BY e.baslangic_tarihi ASC
            LIMIT 500
        ", $params);
    } catch (Exception $e2) {
        // Son çare - boş array
        $etkinlikler = [];
    }
}

// Etkinlikler yoksa boş array döndür
if (!is_array($etkinlikler)) {
    $etkinlikler = [];
}

// DEBUG: Etkinlik sayısını kontrol et
// var_dump("Etkinlik Sayısı: " . count($etkinlikler));

// BYK kodlarına göre varsayılan renkler (events_2026_backup.php'den)
// Önce byk_categories tablosundan tüm renkleri al
$bykColorMap = [];
try {
    $bykColors = $db->fetchAll("SELECT code, color FROM byk_categories WHERE color IS NOT NULL AND color != ''");
    foreach ($bykColors as $bykColor) {
        $bykColorMap[$bykColor['code']] = $bykColor['color'];
    }
} catch (Exception $e) {
    // byk_categories tablosu yoksa varsayılan renkleri kullan
}

// Eğer etkinliklerden renk bilgisi gelmediyse, byk_id üzerinden bul
if (!empty($etkinlikler)) {
    foreach ($etkinlikler as &$etkinlik) {
        // Eğer byk_renk hala varsayılan veya boşsa, etkinlik tablosundaki renk_kodu'nu kullan
        if (empty($etkinlik['byk_renk']) || $etkinlik['byk_renk'] == '#009872') {
            if (!empty($etkinlik['renk_kodu'])) {
                $etkinlik['byk_renk'] = $etkinlik['renk_kodu'];
            }
        }
    }
    unset($etkinlik);
}

// Varsayılan renkler (byk_categories'de yoksa)
$bykDefaultColors = [
    'AT' => '#dc3545',  // Kırmızı
    'KT' => '#6f42c1',  // Mor
    'KGT' => '#198754', // Yeşil
    'GT' => '#0d6efd'   // Mavi
];

// Etkinlikleri JSON formatına çevir (takvim için)
$calendarEvents = [];
if (!empty($etkinlikler) && is_array($etkinlikler)) {
    // Debug: Etkinlik sayısını kontrol et
    error_log("Etkinlik sayısı: " . count($etkinlikler));

    foreach ($etkinlikler as $etkinlik) {
        // Gerekli alanların varlığını kontrol et
        if (empty($etkinlik['baslangic_tarihi']) || empty($etkinlik['bitis_tarihi']) || empty($etkinlik['baslik'])) {
            error_log("Geçersiz etkinlik atlandı: " . print_r($etkinlik, true));
            continue; // Geçersiz etkinlik atlanır
        }

        try {
            $baslangic = new DateTime($etkinlik['baslangic_tarihi']);
            $bitis = new DateTime($etkinlik['bitis_tarihi']);
        } catch (Exception $e) {
            // Geçersiz tarih formatı - atla
            continue;
        }

        // BYK rengini belirle - önce veritabanından gelen rengi kullan
        $bykRenk = $etkinlik['byk_renk'] ?? null;
        $bykKodu = $etkinlik['byk_kodu'] ?? '';

        // Eğer renk boş veya geçersiz ise, bykColorMap'ten dene
        if (empty($bykRenk) || !preg_match('/^#[0-9A-Fa-f]{6}$/i', $bykRenk)) {
            if (!empty($bykKodu) && isset($bykColorMap[$bykKodu])) {
                $bykRenk = $bykColorMap[$bykKodu];
            } elseif (!empty($bykKodu) && isset($bykDefaultColors[$bykKodu])) {
                $bykRenk = $bykDefaultColors[$bykKodu];
            } else {
                $bykRenk = '#009872'; // Varsayılan renk
            }
        }

        // Renk formatını düzelt (# olmadan gelirse ekle)
        if (!empty($bykRenk) && substr($bykRenk, 0, 1) !== '#') {
            $bykRenk = '#' . $bykRenk;
        }

        // Geçersiz renk formatını kontrol et ve düzelt
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/i', $bykRenk)) {
            if (!empty($bykKodu)) {
                $bykRenk = $bykColorMap[$bykKodu] ?? $bykDefaultColors[$bykKodu] ?? '#009872';
            } else {
                $bykRenk = '#009872';
            }
        }

        $calendarEvents[] = [
            'id' => $etkinlik['etkinlik_id'],
            'title' => $etkinlik['baslik'],
            'start' => $baslangic->format('Y-m-d\TH:i:s'),
            'end' => $bitis->format('Y-m-d\TH:i:s'),
            'allDay' => (date('H:i:s', strtotime($etkinlik['baslangic_tarihi'])) == '00:00:00' &&
                date('H:i:s', strtotime($etkinlik['bitis_tarihi'])) == '23:59:59'),
            'backgroundColor' => $bykRenk,
            'borderColor' => $bykRenk,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'byk' => $etkinlik['byk_adi'] ?? '',
                'byk_kodu' => $bykKodu,
                'byk_renk' => $bykRenk,
                'konum' => $etkinlik['konum'] ?? '',
                'aciklama' => $etkinlik['aciklama'] ?? '',
                'olusturan' => $etkinlik['olusturan'] ?? ''
            ]
        ];
    }

    // Debug: Oluşturulan event sayısını kontrol et
    error_log("Calendar Events oluşturuldu: " . count($calendarEvents));
} else {
    error_log("Etkinlikler array'i boş veya geçersiz! Count: " . (is_array($etkinlikler) ? count($etkinlikler) : 'N/A'));
}

// Debug: Final calendarEvents count
error_log("Final Calendar Events Count: " . count($calendarEvents));

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
                <?php if ($canManage): ?>
                    <a href="/admin/etkinlik-ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Yeni Etkinlik Ekle
                    </a>
                    <a href="/database/import-events-2026.php" class="btn btn-success">
                        <i class="fas fa-file-import me-2"></i>2026 Etkinliklerini Import Et
                    </a>
                    <a href="/admin/etkinlikler-debug.php" class="btn btn-warning">
                        <i class="fas fa-bug me-2"></i>Debug
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Arama</label>
                        <input type="text" class="form-control" name="search"
                            value="<?php echo htmlspecialchars($search); ?>" placeholder="Etkinlik adı, açıklama...">
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
                                1 => 'Ocak',
                                2 => 'Şubat',
                                3 => 'Mart',
                                4 => 'Nisan',
                                5 => 'Mayıs',
                                6 => 'Haziran',
                                7 => 'Temmuz',
                                8 => 'Ağustos',
                                9 => 'Eylül',
                                10 => 'Ekim',
                                11 => 'Kasım',
                                12 => 'Aralık'
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
                        <input type="number" class="form-control" name="yil"
                            value="<?php echo htmlspecialchars($yearFilter); ?>" min="2020" max="2030">
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
                        <span class="badge bg-info me-2">Toplam: <strong><?php echo count($etkinlikler); ?></strong>
                            etkinlik</span>
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
                                            <a href="/admin/etkinlik-duzenle.php?id=<?php echo $etkinlik['etkinlik_id']; ?>"
                                                class="text-dark text-decoration-none">
                                                <strong><?php echo htmlspecialchars($etkinlik['baslik']); ?></strong>
                                            </a>
                                            <?php if (!empty($etkinlik['aciklama'])): ?>
                                                <br><small
                                                    class="text-muted"><?php echo htmlspecialchars(substr($etkinlik['aciklama'], 0, 50)); ?><?php echo strlen($etkinlik['aciklama']) > 50 ? '...' : ''; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($etkinlik['byk_adi'])): ?>
                                                <span class="badge"
                                                    style="background-color: <?php echo htmlspecialchars($etkinlik['byk_renk'] ?? '#009872'); ?>; color: white;">
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
                                                <?php if ($canManage): ?>
                                                    <a href="/admin/etkinlik-duzenle.php?id=<?php echo $etkinlik['etkinlik_id']; ?>"
                                                        class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger confirm-delete"
                                                        data-id="<?php echo $etkinlik['etkinlik_id']; ?>" data-type="etkinlik"
                                                        data-name="<?php echo htmlspecialchars($etkinlik['baslik']); ?>"
                                                        title="Sil">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                        title="Yetkiniz yok">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                <?php endif; ?>
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

<!-- Etkinlik Detay Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Etkinlik Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- İçerik JavaScript ile doldurulacak -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <?php if ($canManage): ?>
                    <a href="#" id="eventEditBtn" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>Düzenle
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Görünüm değiştirme
        const calendarViewBtn = document.getElementById('calendarViewBtn');
        const listViewBtn = document.getElementById('listViewBtn');
        const calendarView = document.getElementById('calendarView');
        const listView = document.getElementById('listView');

        calendarViewBtn.addEventListener('click', function () {
            calendarViewBtn.classList.add('active');
            listViewBtn.classList.remove('active');
            calendarView.classList.remove('d-none');
            listView.classList.add('d-none');
        });

        listViewBtn.addEventListener('click', function () {
            listViewBtn.classList.add('active');
            calendarViewBtn.classList.remove('active');
            listView.classList.remove('d-none');
            calendarView.classList.add('d-none');
        });

        // Takvim etkinlikleri
        const calendarEvents = <?php echo json_encode($calendarEvents, JSON_UNESCAPED_UNICODE); ?>;

        // Debug: Console'a yazdır
        console.log('Calendar Events:', calendarEvents);
        console.log('Calendar Events Count:', calendarEvents ? calendarEvents.length : 0);

        // FullCalendar başlat
        const calendarEl = document.getElementById('calendar');

        if (!calendarEl) {
            console.error('Calendar element not found!');
            return;
        }
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'tr',
            firstDay: 1, // Haftanın ilk günü Pazartesi
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            buttonText: {
                today: 'Bugün',
                month: 'Ay',
                week: 'Hafta',
                day: 'Gün',
                list: 'Liste'
            },
            events: calendarEvents,
            eventClick: function (info) {
                const event = info.event;
                const extendedProps = event.extendedProps;

                // Modal içeriğini doldur
                const modalBody = document.getElementById('eventModalBody');
                const modalTitle = document.getElementById('eventModalLabel');
                const editBtn = document.getElementById('eventEditBtn');

                modalTitle.textContent = event.title;
                editBtn.href = '/admin/etkinlik-duzenle.php?id=' + event.id;

                let html = '<div class="mb-3">';
                html += '<strong>Başlık:</strong> ' + event.title;
                html += '</div>';

                if (extendedProps.byk) {
                    html += '<div class="mb-3">';
                    html += '<strong>BYK:</strong> <span class="badge" style="background-color: ' + event.backgroundColor + '; color: white;">' + extendedProps.byk + '</span>';
                    html += '</div>';
                }

                html += '<div class="mb-3">';
                html += '<strong>Başlangıç:</strong> ' + event.start.toLocaleString('tr-TR');
                html += '</div>';

                html += '<div class="mb-3">';
                html += '<strong>Bitiş:</strong> ' + event.end.toLocaleString('tr-TR');
                html += '</div>';

                if (extendedProps.konum) {
                    html += '<div class="mb-3">';
                    html += '<strong>Konum:</strong> ' + extendedProps.konum;
                    html += '</div>';
                }

                if (extendedProps.aciklama) {
                    html += '<div class="mb-3">';
                    html += '<strong>Açıklama:</strong><br>' + extendedProps.aciklama;
                    html += '</div>';
                }

                if (extendedProps.olusturan) {
                    html += '<div class="mb-3">';
                    html += '<strong>Oluşturan:</strong> ' + extendedProps.olusturan;
                    html += '</div>';
                }

                modalBody.innerHTML = html;

                // Modal'ı göster
                const modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();

                info.jsEvent.preventDefault();
            },
            eventDisplay: 'block',
            height: 'auto',
            contentHeight: 'auto'
        });

        calendar.render();
    });
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>
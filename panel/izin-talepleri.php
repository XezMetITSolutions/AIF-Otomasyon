<?php
/**
 * İzin Talepleri Modülü
 * Taleplerim (Herkes) + Onay İşlemleri (Yetkili)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Temel Uye kontrolü
Middleware::requireUye();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

// Yetkiler
$hasPermissionBaskan = $auth->hasModulePermission('baskan_izin_talepleri');
$hasPermissionUye = $auth->hasModulePermission('uye_izin_talepleri');

if (!$hasPermissionBaskan && !$hasPermissionUye) {
    // Hiçbir yetkisi yoksa erişim engellensin veya dashboard'a yönlendirilsin
    header('Location: /panel/dashboard.php');
    exit;
}

// Sekme tespiti (tab=yeni/gecmis/onay)
// Varsayılan: Eğer başkan yetkisi varsa 'onay', yoksa 'talebim'
$activeTab = $_GET['tab'] ?? ($hasPermissionBaskan ? 'onay' : 'talebim');

$pageTitle = 'İzin Talepleri';
$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$errors = [];
$messages = [];

$durumBadgeMap = [
    'beklemede' => 'warning',
    'onaylandi' => 'success',
    'reddedildi' => 'danger'
];

// --- POST İŞLEMLERİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Oturum doğrulaması başarısız oldu. Lütfen sayfayı yenileyin.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Yeni Talep Oluşturma (Üye)
        if ($action === 'yeni_izin' && $hasPermissionUye) {
            $baslangic = $_POST['baslangic_tarihi'] ?? '';
            $bitis = $_POST['bitis_tarihi'] ?? '';
            $izinNedeni = trim($_POST['izin_nedeni'] ?? '');
            $aciklama = trim($_POST['aciklama'] ?? '');
            
            if (!$baslangic || !$bitis) {
                $errors[] = 'Başlangıç ve bitiş tarihleri zorunludur.';
            } else {
                $baslangicDate = DateTime::createFromFormat('Y-m-d', $baslangic);
                $bitisDate = DateTime::createFromFormat('Y-m-d', $bitis);
                if (!$baslangicDate || !$bitisDate) {
                    $errors[] = 'Geçerli tarih formatı kullanın.';
                } elseif ($bitisDate < $baslangicDate) {
                    $errors[] = 'Bitiş tarihi başlangıç tarihinden önce olamaz.';
                }
            }

            if (strlen($izinNedeni) > 255) {
                $errors[] = 'İzin nedeni 255 karakteri aşamaz.';
            }

            if (empty($errors)) {
                $db->query("
                    INSERT INTO izin_talepleri (kullanici_id, baslangic_tarihi, bitis_tarihi, izin_nedeni, aciklama, durum)
                    VALUES (?, ?, ?, ?, ?, 'beklemede')
                ", [
                    $user['id'],
                    $baslangic,
                    $bitis,
                    $izinNedeni ?: null,
                    $aciklama ?: null
                ]);
                $messages[] = 'İzin talebiniz başarıyla oluşturuldu.';
                $activeTab = 'talebim'; // Talep sonrası sekmeye dön
            }

        // 2. Onay/Red İşlemi (Başkan)
        } elseif (($action === 'approve' || $action === 'reject') && $hasPermissionBaskan) {
            $izinId = (int)($_POST['izin_id'] ?? 0);
            $aciklama = trim($_POST['aciklama'] ?? '');

            // İzin kontrol (Aynı BYK olması vs.)
            $izin = $db->fetch("
                SELECT it.*, k.byk_id
                FROM izin_talepleri it
                INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
                WHERE it.izin_id = ? AND k.byk_id = ?
            ", [$izinId, $user['byk_id']]);

            if (!$izin) {
                $errors[] = 'İzin talebi bulunamadı.';
            } elseif ($izin['durum'] !== 'beklemede') {
                $errors[] = 'Bu talep zaten yanıtlanmış.';
            } else {
                $yeniDurum = ($action === 'approve') ? 'onaylandi' : 'reddedildi';
                $db->query("
                    UPDATE izin_talepleri
                    SET durum = ?,
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE izin_id = ?
                ", [$yeniDurum, $user['id'], $aciklama ?: null, $izinId]);
                
                $messages[] = 'İşlem başarıyla gerçekleşti: ' . ucfirst($yeniDurum);
                $activeTab = 'onay';
            }

        // 3. Tekli Silme (Başkan)
        } elseif ($action === 'delete' && $hasPermissionBaskan) {
            $izinId = (int)($_POST['izin_id'] ?? 0);
            if ($izinId) {
                $db->query("
                    DELETE it FROM izin_talepleri it
                    INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
                    WHERE it.izin_id = ? AND k.byk_id = ?
                ", [$izinId, $user['byk_id']]);
                $messages[] = 'İzin talebi silindi.';
                $activeTab = 'onay';
            }

        // 4. Toplu Silme (Başkan)
        } elseif ($action === 'bulk_delete' && $hasPermissionBaskan) {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $params = array_merge($ids, [$user['byk_id']]);
                $db->query("
                    DELETE it FROM izin_talepleri it
                    INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
                    WHERE it.izin_id IN ($placeholders) AND k.byk_id = ?
                ", $params);
                $messages[] = count($ids) . ' izin talebi silindi.';
                $activeTab = 'onay';
            }

        // 5. Düzenleme (Başkan)
        } elseif ($action === 'edit' && $hasPermissionBaskan) {
            $izinId = (int)($_POST['izin_id'] ?? 0);
            $baslangic = $_POST['baslangic_tarihi'] ?? '';
            $bitis = $_POST['bitis_tarihi'] ?? '';
            $izinNedeni = trim($_POST['izin_nedeni'] ?? '');
            $durum = $_POST['durum'] ?? 'beklemede';
            $aciklama = trim($_POST['aciklama'] ?? '');

            if ($izinId && $baslangic && $bitis) {
                $db->query("
                    UPDATE izin_talepleri it
                    INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
                    SET it.baslangic_tarihi = ?, it.bitis_tarihi = ?, it.izin_nedeni = ?, it.durum = ?, it.aciklama = ?
                    WHERE it.izin_id = ? AND k.byk_id = ?
                ", [$baslangic, $bitis, $izinNedeni, $durum, $aciklama, $izinId, $user['byk_id']]);
                $messages[] = 'İzin talebi güncellendi.';
                $activeTab = 'onay';
            } else {
                $errors[] = 'Tüm alanları doldurunuz.';
            }
        }
    }
}

// --- VERİ ÇEKME ---

// 1. Kullanıcının Kendi Talepleri
$myRequests = [];
if ($hasPermissionUye) {
    $myRequests = $db->fetchAll("
        SELECT *
        FROM izin_talepleri
        WHERE kullanici_id = ?
        ORDER BY olusturma_tarihi DESC
    ", [$user['id']]);
}

// 2. Onay Bekleyenler / Yönetim Listesi (Sadece Başkan için)
$pendingRequests = [];
$durumFilter = $_GET['durum'] ?? 'beklemede';

if ($hasPermissionBaskan) {
    $filters = ['k.byk_id = ?'];
    $params = [$user['byk_id']];
    
    if ($durumFilter) {
        $filters[] = "it.durum = ?";
        $params[] = $durumFilter;
    }
    
    $where = 'WHERE ' . implode(' AND ', $filters);
    
    $pendingRequests = $db->fetchAll("
        SELECT it.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, k.telefon
        FROM izin_talepleri it
        INNER JOIN kullanicilar k ON it.kullanici_id = k.kullanici_id
        $where
        ORDER BY it.olusturma_tarihi DESC
        LIMIT 100
    ", $params);
}

include __DIR__ . '/../includes/header.php';
?>

<style>
/* Modern Glass Table/Card Styles override if needed */
.nav-pills .nav-link {
    color: #495057;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    border-radius: 0.75rem;
    transition: all 0.2s;
}
.nav-pills .nav-link.active {
    background-color: var(--primary, #009872);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 152, 114, 0.2);
}
.nav-pills .nav-link:hover:not(.active) {
    background-color: rgba(0,0,0,0.05);
}
</style>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-calendar-check me-2"></i>İzin Talepleri
                </h1>
                <p class="text-muted mb-0">İzin süreçlerinizi buradan yönetin.</p>
            </div>
            
            <!-- Navigation Tabs -->
            <ul class="nav nav-pills bg-white p-1 rounded-4 border shadow-sm">
                <?php if ($hasPermissionUye): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeTab === 'talebim') ? 'active' : ''; ?>" href="?tab=talebim">
                        <i class="fas fa-user me-2"></i>Taleplerim
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($hasPermissionBaskan): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activeTab === 'onay') ? 'active' : ''; ?>" href="?tab=onay">
                        <i class="fas fa-gavel me-2"></i>Yönetim (Onay)
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Messages -->
        <?php if (!empty($errors)): ?>
             <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php foreach ($messages as $message): ?>
                    <div><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
                 <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Content Area -->
        <div class="tab-content" id="pills-tabContent">
            
            <!-- TAB 1: TALEPLERİM (ÜYE) -->
            <?php if ($hasPermissionUye && $activeTab === 'talebim'): ?>
            <div class="tab-pane fade show active" role="tabpanel">
                <div class="row g-4">
                    <!-- New Request Form -->
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent border-bottom-0 fw-bold">
                                Yeni İzin Talebi
                            </div>
                            <div class="card-body pt-0">
                                <form method="post">
                                    <input type="hidden" name="action" value="yeni_izin">
                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Başlangıç</label>
                                        <input type="date" name="baslangic_tarihi" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Bitiş</label>
                                        <input type="date" name="bitis_tarihi" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Neden</label>
                                        <input type="text" name="izin_nedeni" class="form-control" maxlength="255" placeholder="Örn. Tatil, sağlık vb.">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Açıklama</label>
                                        <textarea name="aciklama" class="form-control" rows="3" placeholder="Detaylı açıklama (opsiyonel)"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-1"></i>Talep Gönder
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- My Request List -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Geçmiş Taleplerim</span>
                                <span class="badge bg-light text-dark"><?php echo count($myRequests); ?> Kayıt</span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($myRequests)): ?>
                                    <div class="text-center p-5 text-muted">
                                        <i class="fas fa-folder-open fa-2x mb-3 opacity-25"></i>
                                        <p>Henüz bir izin talebiniz bulunmuyor.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Tarih Aralığı</th>
                                                    <th>Neden</th>
                                                    <th>Durum</th>
                                                    <th>Açıklama</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($myRequests as $req): 
                                                    $badge = $durumBadgeMap[$req['durum']] ?? 'secondary';
                                                ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-medium text-dark"><?php echo date('d.m.Y', strtotime($req['baslangic_tarihi'])); ?></span>
                                                            <small class="text-muted"><?php echo date('d.m.Y', strtotime($req['bitis_tarihi'])); ?></small>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($req['izin_nedeni'] ?? '-'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $badge; ?> bg-opacity-10 text-<?php echo $badge; ?> px-3 py-2 rounded-pill border border-<?php echo $badge; ?> border-opacity-10">
                                                            <?php echo ucfirst($req['durum']); ?>
                                                        </span>
                                                        <?php if($req['onay_aciklama']): ?>
                                                            <i class="fas fa-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Yönetici Notu: <?php echo htmlspecialchars($req['onay_aciklama']); ?>"></i>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="small text-muted text-truncate" style="max-width: 200px;">
                                                        <?php echo htmlspecialchars($req['aciklama'] ?? ''); ?>
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
                </div>
            </div>
            <?php endif; ?>

            <!-- TAB 2: YÖNETİM (BAŞKAN) -->
            <?php if ($hasPermissionBaskan && $activeTab === 'onay'): ?>
            <div class="tab-pane fade show active" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="btn-group">
                            <a href="?tab=onay&durum=beklemede" class="btn btn-sm <?php echo $durumFilter === 'beklemede' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                <i class="fas fa-hourglass-half me-1"></i>Bekleyen
                            </a>
                            <a href="?tab=onay&durum=onaylandi" class="btn btn-sm <?php echo $durumFilter === 'onaylandi' ? 'btn-success' : 'btn-outline-success'; ?>">
                                <i class="fas fa-check me-1"></i>Onaylanan
                            </a>
                            <a href="?tab=onay&durum=reddedildi" class="btn btn-sm <?php echo $durumFilter === 'reddedildi' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                <i class="fas fa-times me-1"></i>Reddedilen
                            </a>
                            <a href="?tab=onay&durum=" class="btn btn-sm <?php echo $durumFilter === '' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">
                                Tümü
                            </a>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display:none;" onclick="bulkDeleteIzin()">
                                <i class="fas fa-trash me-1"></i>Seçilenleri Sil
                            </button>
                            <span class="badge bg-light text-dark border"><?php echo count($pendingRequests); ?> sonuç</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                         <?php if (empty($pendingRequests)): ?>
                            <div class="text-center p-5 text-muted">
                                <i class="fas fa-clipboard-check fa-2x mb-3 opacity-25"></i>
                                <p>Bu filtrede gösterilecek kayıt bulunamadı.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-3" style="width: 40px;">
                                                <input type="checkbox" id="selectAllIzin" class="form-check-input">
                                            </th>
                                            <th>Kullanıcı</th>
                                            <th>Tarih Aralığı</th>
                                            <th>Gün</th>
                                            <th>Neden</th>
                                            <th>Durum</th>
                                            <th class="text-end pe-3">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingRequests as $req):
                                            $gunSayisi = (new DateTime($req['baslangic_tarihi']))->diff(new DateTime($req['bitis_tarihi']))->days + 1;
                                            $badge = $durumBadgeMap[$req['durum']] ?? 'secondary';
                                        ?>
                                        <tr>
                                            <td class="ps-3">
                                                <input type="checkbox" class="form-check-input row-checkbox-izin" value="<?php echo $req['izin_id']; ?>">
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($req['kullanici_adi']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($req['email']); ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span><?php echo date('d.m.Y', strtotime($req['baslangic_tarihi'])); ?></span>
                                                    <small class="text-muted"><?php echo date('d.m.Y', strtotime($req['bitis_tarihi'])); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo $gunSayisi; ?> gün</td>
                                            <td>
                                                <div class="fw-medium"><?php echo htmlspecialchars($req['izin_nedeni'] ?? '-'); ?></div>
                                                <?php if($req['aciklama']): ?>
                                                    <small class="text-muted d-inline-block text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($req['aciklama']); ?>">
                                                        <?php echo htmlspecialchars($req['aciklama']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $badge; ?> bg-opacity-10 text-<?php echo $badge; ?> px-2 py-1 border border-<?php echo $badge; ?> border-opacity-10">
                                                    <?php echo ucfirst($req['durum']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="btn-group">
                                                    <?php if ($req['durum'] === 'beklemede'): ?>
                                                        <form method="post" onsubmit="return confirm('Onaylıyor musunuz?');">
                                                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="izin_id" value="<?php echo $req['izin_id']; ?>">
                                                            <button class="btn btn-sm btn-success" type="submit" title="Onayla">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal" data-izin="<?php echo $req['izin_id']; ?>" title="Reddet">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-primary" onclick='editIzin(<?php echo json_encode($req); ?>)' title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="post" class="d-inline-block" onsubmit="return confirm('Bu izin talebini silmek istediğinizden emin misiniz?');">
                                                        <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="izin_id" value="<?php echo $req['izin_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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
            
            <!-- Reddet Modal (Sadece Başkan ve Onay Tabında Gerekli) -->
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-times text-danger me-2"></i>Talebini Reddet</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="izin_id" id="rejectIzinId">
                            <input type="hidden" name="action" value="reject">
                            <div class="mb-3">
                                <label class="form-label">Red Açıklaması</label>
                                <textarea name="aciklama" class="form-control" rows="3" placeholder="Neden reddedildiğini yazınız..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-danger">Reddet</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editIzinModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="izin_id" id="edit_izin_id">
                        <div class="modal-header">
                            <h5 class="modal-title">İzin Talebini Düzenle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Başlangıç Tarihi</label>
                                <input type="date" name="baslangic_tarihi" id="edit_baslangic" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Bitiş Tarihi</label>
                                <input type="date" name="bitis_tarihi" id="edit_bitis" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">İzin Nedeni</label>
                                <input type="text" name="izin_nedeni" id="edit_izin_nedeni" class="form-control" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Durum</label>
                                <select name="durum" id="edit_durum" class="form-select">
                                    <option value="beklemede">Beklemede</option>
                                    <option value="onaylandi">Onaylandı</option>
                                    <option value="reddedildi">Reddedildi</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Açıklama</label>
                                <textarea name="aciklama" id="edit_aciklama" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                const rejectModal = document.getElementById('rejectModal');
                if (rejectModal) {
                    rejectModal.addEventListener('show.bs.modal', event => {
                        const button = event.relatedTarget;
                        const izinId = button.getAttribute('data-izin');
                        rejectModal.querySelector('#rejectIzinId').value = izinId;
                    });
                }
                
                // Tooltips initialization
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                  return new bootstrap.Tooltip(tooltipTriggerEl)
                })

                // Bulk operations for izin
                const selectAll = document.getElementById('selectAllIzin');
                const rowCheckboxes = document.querySelectorAll('.row-checkbox-izin');
                const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        rowCheckboxes.forEach(cb => cb.checked = this.checked);
                        updateBulkDeleteBtn();
                    });
                }

                rowCheckboxes.forEach(cb => {
                    cb.addEventListener('change', updateBulkDeleteBtn);
                });

                function updateBulkDeleteBtn() {
                    const checked = document.querySelectorAll('.row-checkbox-izin:checked');
                    if (bulkDeleteBtn) {
                        bulkDeleteBtn.style.display = checked.length > 0 ? 'block' : 'none';
                    }
                }
            });

            function bulkDeleteIzin() {
                const checked = document.querySelectorAll('.row-checkbox-izin:checked');
                if (checked.length === 0) {
                    alert('Lütfen en az bir kayıt seçin.');
                    return;
                }
                
                if (!confirm(checked.length + ' izin talebini silmek istediğinizden emin misiniz?')) {
                    return;
                }
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>"><input type="hidden" name="action" value="bulk_delete">';
                
                checked.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            }

            function editIzin(req) {
                document.getElementById('edit_izin_id').value = req.izin_id;
                document.getElementById('edit_baslangic').value = req.baslangic_tarihi;
                document.getElementById('edit_bitis').value = req.bitis_tarihi;
                document.getElementById('edit_izin_nedeni').value = req.izin_nedeni || '';
                document.getElementById('edit_durum').value = req.durum;
                document.getElementById('edit_aciklama').value = req.aciklama || '';
                
                const modal = new bootstrap.Modal(document.getElementById('editIzinModal'));
                modal.show();
            }
            </script>
            <?php endif; ?>
            
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

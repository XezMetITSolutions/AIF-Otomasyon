<?php
/**
 * Demirbaş Talepleri Paneli
 * Üye: Demirbaş Talep Formu
 * Başkan: Demirbaş Talep Onay/Red
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireAuth(); // Genel oturum kontrolü

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

$hasPermissionBaskan = $auth->hasModulePermission('baskan_demirbas_talepleri');
$hasPermissionUye = true; // Her üye demirbaş talep edebilir varsayımı

// Sekme kontrolü
$activeTab = $_GET['tab'] ?? ($hasPermissionBaskan ? 'onay' : 'talep');

$pageTitle = 'Demirbaş İşlemleri';
$messages = [];
$errors = [];

// === POST İŞLEMLERİ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. Yeni Talep Oluşturma (Üye)
    if ($action === 'create_request') {
        $demirbas_id = $_POST['demirbas_id'] ?? null;
        $baslangic = $_POST['baslangic'] ?? null;
        $bitis = $_POST['bitis'] ?? null;
        $aciklama = trim($_POST['aciklama'] ?? '');

        if (!$demirbas_id || !$baslangic || !$bitis) {
            $errors[] = 'Lütfen tüm zorunlu alanları doldurun.';
        } else {
             // Demirbaş adını ve sorumlusunu al
            $demirbasItem = $db->fetch("SELECT ad, sorumlu_kisi_id FROM demirbaslar WHERE id = ?", [$demirbas_id]);
            $baslik = $demirbasItem ? $demirbasItem['ad'] . ' Talebi' : 'Genel Talep';

            try {
                $db->query(
                    "INSERT INTO demirbas_talepleri (kullanici_id, demirbas_id, baslik, aciklama, baslangic_tarihi, bitis_tarihi, durum) VALUES (?, ?, ?, ?, ?, ?, 'bekliyor')",
                    [$user['id'], $demirbas_id, $baslik, $aciklama, $baslangic, $bitis]
                );

                // Sorumlu Kişiye Bildirim
                if ($demirbasItem && isset($demirbasItem['sorumlu_kisi_id'])) {
                    Notification::add(
                        $demirbasItem['sorumlu_kisi_id'],
                        'Yeni Demirbaş Talebi',
                        "{$user['name']} tarafından '{$demirbasItem['ad']}' için talep oluşturuldu.",
                        'bilgi',
                        '/panel/demirbas-talepleri.php?tab=onay'
                    );
                }
                $messages[] = 'Demirbaş talebiniz başarıyla oluşturuldu.';
                $activeTab = 'talep'; // Kal
            } catch (Exception $e) {
                $errors[] = 'Hata: ' . $e->getMessage();
            }
        }
    }

    // 2. Onay/Red (Başkan)
    if (($action === 'approve' || $action === 'reject') && $hasPermissionBaskan) {
        $talepId = $_POST['id'] ?? null;
        if ($talepId) {
            $status = ($action === 'approve') ? 'onaylandi' : 'reddedildi';
            $db->query("UPDATE demirbas_talepleri SET durum = ? WHERE id = ?", [$status, $talepId]);
            
            // Talep Sahibine Bildirim
            $talep = $db->fetch("SELECT kullanici_id, baslik FROM demirbas_talepleri WHERE id = ?", [$talepId]);
            if ($talep) {
                Notification::add(
                    $talep['kullanici_id'],
                    'Demirbaş Talebi ' . ucfirst($status),
                    "'{$talep['baslik']}' talebiniz yönetici tarafından " . ($status == 'onaylandi' ? 'onaylandı' : 'reddedildi') . ".",
                    ($status == 'onaylandi' ? 'basarili' : 'hata'),
                    '/panel/demirbas-talepleri.php?tab=talep'
                );
            }

            $messages[] = 'Talep durumu güncellendi: ' . ucfirst($status);
            $activeTab = 'onay';
        }
    }

    // 3. Tekli Silme (Başkan)
    if ($action === 'delete' && $hasPermissionBaskan) {
        $talepId = $_POST['id'] ?? null;
        if ($talepId) {
            $db->query("DELETE FROM demirbas_talepleri WHERE id = ?", [$talepId]);
            $messages[] = 'Demirbaş talebi silindi.';
            $activeTab = 'onay';
        }
    }

    // 4. Toplu Silme (Başkan)
    if ($action === 'bulk_delete' && $hasPermissionBaskan) {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM demirbas_talepleri WHERE id IN ($placeholders)", $ids);
            $messages[] = count($ids) . ' demirbaş talebi silindi.';
            $activeTab = 'onay';
        }
    }

    // 5. Düzenleme (Başkan)
    if ($action === 'edit' && $hasPermissionBaskan) {
        $talepId = $_POST['id'] ?? null;
        $baslangic = $_POST['baslangic'] ?? '';
        $bitis = $_POST['bitis'] ?? '';
        $durum = $_POST['durum'] ?? 'bekliyor';
        $aciklama = trim($_POST['aciklama'] ?? '');

        if ($talepId && $baslangic && $bitis) {
            $db->query("
                UPDATE demirbas_talepleri
                SET baslangic_tarihi = ?, bitis_tarihi = ?, durum = ?, aciklama = ?
                WHERE id = ?
            ", [$baslangic, $bitis, $durum, $aciklama, $talepId]);
            $messages[] = 'Demirbaş talebi güncellendi.';
            $activeTab = 'onay';
        } else {
            $errors[] = 'Tüm alanları doldurunuz.';
        }
    }
}

// === VERİ ÇEKME ===

// 1. Müsait Demirbaşlar (Talep Ekranı İçin)
$availableItems = $db->fetchAll("
    SELECT d.*, CONCAT(u.ad, ' ', u.soyad) as sorumlu_adi 
    FROM demirbaslar d 
    LEFT JOIN kullanicilar u ON d.sorumlu_kisi_id = u.kullanici_id 
    WHERE d.durum = 'musait'
    ORDER BY d.kategori, d.ad
");

// 2. Benim Taleplerim
$myRequests = $db->fetchAll("
    SELECT t.*, d.ad as demirbas_adi 
    FROM demirbas_talepleri t 
    LEFT JOIN demirbaslar d ON t.demirbas_id = d.id
    WHERE t.kullanici_id = ? 
    ORDER BY t.created_at DESC
", [$user['id']]);

// 3. Onay Bekleyen / Tüm Talepler (Yönetici)
$allRequests = [];
if ($hasPermissionBaskan) {
    // Tüm talepler (Filtre eklenebilir)
    $allRequests = $db->fetchAll("
        SELECT t.*, 
               CONCAT(u.ad, ' ', u.soyad) as kullanici_adi,
               d.ad as demirbas_adi,
               d.fotograf_yolu
        FROM demirbas_talepleri t 
        JOIN kullanicilar u ON t.kullanici_id = u.kullanici_id 
        LEFT JOIN demirbaslar d ON t.demirbas_id = d.id
        ORDER BY t.created_at DESC
        LIMIT 100
    ");
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root { --primary: #009872; }
    .nav-pills .nav-link { color: #495057; font-weight: 500; padding: 0.75rem 1.25rem; border-radius: 0.75rem; transition: all 0.2s; }
    .nav-pills .nav-link.active { background-color: var(--primary); color: white; box-shadow: 0 4px 6px -1px rgba(0, 152, 114, 0.2); }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); transition: 0.2s; }
</style>

<div class="dashboard-layout">
    <div class="sidebar-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?></div>
    
    <main class="main-content">
        <div class="content-wrapper">
             <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                     <h1 class="h3 mb-1"><i class="fas fa-box me-2"></i>Demirbaş İşlemleri</h1>
                     <p class="text-muted mb-0">Demirbaş takibi, talep ve onay süreçleri.</p>
                </div>
                
                <ul class="nav nav-pills bg-white p-1 rounded-4 border shadow-sm">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activeTab === 'talep') ? 'active' : ''; ?>" href="?tab=talep">
                            <i class="fas fa-hand-holding me-2"></i>Talep Et & Geçmişim
                        </a>
                    </li>
                    <?php if ($hasPermissionBaskan): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activeTab === 'onay') ? 'active' : ''; ?>" href="?tab=onay">
                            <i class="fas fa-tasks me-2"></i>Yönetim (Onay)
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if (!empty($messages)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php foreach($messages as $m) echo "<div>$m</div>"; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="tab-content">
                
                <!-- TAB 1: TALEP ETME & GEÇMİŞİM -->
                <?php if ($activeTab === 'talep'): ?>
                <div class="tab-pane fade show active">
                    
                    <h5 class="mb-3 text-secondary"><i class="fas fa-check-circle me-2"></i>Müsait Demirbaşlar</h5>
                    <div class="row g-3 mb-5">
                       <?php if (empty($availableItems)): ?>
                            <div class="col-12"><div class="alert alert-light border">Müsait demirbaş bulunamadı.</div></div>
                       <?php else: ?>
                            <?php foreach ($availableItems as $item): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="card h-100 border-0 shadow-sm card-hover">
                                    <?php if ($item['fotograf_yolu']): ?>
                                        <img src="/<?php echo htmlspecialchars($item['fotograf_yolu']); ?>" class="card-img-top" style="height: 160px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                                            <i class="fas fa-box fa-3x text-secondary opacity-25"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($item['ad']); ?>"><?php echo htmlspecialchars($item['ad']); ?></h6>
                                        <p class="small text-muted mb-2"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['kategori']); ?></p>
                                        <p class="small text-muted mb-3"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($item['konum']); ?></p>
                                        <button class="btn btn-outline-primary btn-sm w-100 stretched-link" onclick='openRequestModal(<?php echo json_encode($item); ?>)'>Talep Et</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                       <?php endif; ?>
                    </div>

                    <h5 class="mb-3 text-secondary"><i class="fas fa-history me-2"></i>Geçmiş Taleplerim</h5>
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Demirbaş</th>
                                        <th>Süre</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($myRequests)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">Geçmiş talebiniz yok.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($myRequests as $req): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($req['created_at'])); ?></td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($req['demirbas_adi'] ?? ''); ?></td>
                                            <td class="small">
                                                <?php echo date('d.m.Y H:i', strtotime($req['baslangic_tarihi'])); ?> - <br>
                                                <?php echo date('d.m.Y H:i', strtotime($req['bitis_tarihi'])); ?>
                                            </td>
                                            <td>
                                                <?php if($req['durum']=='bekliyor'): ?><span class="badge bg-warning text-dark">Bekliyor</span>
                                                <?php elseif($req['durum']=='onaylandi'): ?><span class="badge bg-success">Onaylandı</span>
                                                <?php else: ?><span class="badge bg-danger">Reddedildi</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="requestModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <input type="hidden" name="action" value="create_request">
                            <input type="hidden" name="demirbas_id" id="reqDemirbasId">
                            <div class="modal-header">
                                <h5 class="modal-title">Talep Et: <span id="reqDemirbasAd" class="fw-bold text-primary"></span></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Başlangıç</label>
                                        <input type="datetime-local" name="baslangic" class="form-control" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Bitiş</label>
                                        <input type="datetime-local" name="bitis" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">Açıklama</label>
                                        <textarea name="aciklama" class="form-control" rows="3" placeholder="Kullanım amacı..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                <button type="submit" class="btn btn-primary">Talebi Oluştur</button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    function openRequestModal(item) {
                        const m = new bootstrap.Modal(document.getElementById('requestModal'));
                        document.getElementById('reqDemirbasId').value = item.id;
                        document.getElementById('reqDemirbasAd').innerText = item.ad;
                        m.show();
                    }
                </script>
                <?php endif; ?>

                <!-- TAB 2: YÖNETİM ONANY (BAŞKAN) -->
                <?php if ($hasPermissionBaskan && $activeTab === 'onay'): ?>
                <div class="tab-pane fade show active">
                    <div class="card border-0 shadow-sm">
                         <div class="card-header bg-transparent py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">Tüm Talepler</h6>
                                <div class="d-flex gap-2 align-items-center">
                                    <button class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display:none;" onclick="bulkDeleteDemirbas()">
                                        <i class="fas fa-trash me-1"></i>Seçilenleri Sil
                                    </button>
                                    <span class="badge bg-light text-dark border"><?php echo count($allRequests); ?> Kayıt</span>
                                </div>
                            </div>
                         </div>
                         <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3" style="width: 40px;">
                                            <input type="checkbox" id="selectAllDemirbas" class="form-check-input">
                                        </th>
                                        <th>Tarih</th>
                                        <th>Kullanıcı</th>
                                        <th>Demirbaş</th>
                                        <th>Süre/Açıklama</th>
                                        <th>Durum</th>
                                        <th class="text-end pe-3">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allRequests)): ?>
                                        <tr><td colspan="7" class="text-center py-4 text-muted">Kayıt yok.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($allRequests as $req): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <input type="checkbox" class="form-check-input row-checkbox-demirbas" value="<?php echo $req['id']; ?>">
                                            </td>
                                            <td><small><?php echo date('d.m.Y', strtotime($req['created_at'])); ?></small></td>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($req['kullanici_adi']); ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($req['fotograf_yolu']): ?>
                                                        <img src="/<?php echo htmlspecialchars($req['fotograf_yolu']); ?>" class="rounded me-2" style="width:32px;height:32px;object-fit:cover;">
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($req['demirbas_adi']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small text-muted">
                                                    <?php echo date('d.m.Y H:i', strtotime($req['baslangic_tarihi'])); ?> - 
                                                    <?php echo date('d.m.Y H:i', strtotime($req['bitis_tarihi'])); ?>
                                                </div>
                                                <div class="small fst-italic text-truncate" style="max-width:200px;"><?php echo htmlspecialchars($req['aciklama']); ?></div>
                                            </td>
                                            <td>
                                                <?php if($req['durum']=='bekliyor'): ?><span class="badge bg-warning text-dark">Bekliyor</span>
                                                <?php elseif($req['durum']=='onaylandi'): ?><span class="badge bg-success">Onaylandı</span>
                                                <?php else: ?><span class="badge bg-danger">Reddedildi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="btn-group">
                                                    <?php if ($req['durum'] === 'bekliyor'): ?>
                                                        <form method="POST" class="d-inline-block">
                                                            <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Onayla"><i class="fas fa-check"></i></button>
                                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" title="Reddet"><i class="fas fa-times"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-primary" onclick='editDemirbas(<?php echo json_encode($req); ?>)' title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline-block" onsubmit="return confirm('Bu demirbaş talebini silmek istediğinizden emin misiniz?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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

                <!-- Edit Modal -->
                <div class="modal fade" id="editDemirbasModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" id="edit_demirbas_id">
                            <div class="modal-header">
                                <h5 class="modal-title">Demirbaş Talebini Düzenle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Başlangıç</label>
                                    <input type="datetime-local" name="baslangic" id="edit_baslangic" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Bitiş</label>
                                    <input type="datetime-local" name="bitis" id="edit_bitis" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Durum</label>
                                    <select name="durum" id="edit_durum" class="form-select">
                                        <option value="bekliyor">Bekliyor</option>
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
                    const selectAll = document.getElementById('selectAllDemirbas');
                    const rowCheckboxes = document.querySelectorAll('.row-checkbox-demirbas');
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
                        const checked = document.querySelectorAll('.row-checkbox-demirbas:checked');
                        if (bulkDeleteBtn) {
                            bulkDeleteBtn.style.display = checked.length > 0 ? 'block' : 'none';
                        }
                    }
                });

                function bulkDeleteDemirbas() {
                    const checked = document.querySelectorAll('.row-checkbox-demirbas:checked');
                    if (checked.length === 0) {
                        alert('Lütfen en az bir kayıt seçin.');
                        return;
                    }
                    
                    if (!confirm(checked.length + ' demirbaş talebini silmek istediğinizden emin misiniz?')) {
                        return;
                    }
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="action" value="bulk_delete">';
                    
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

                function editDemirbas(req) {
                    document.getElementById('edit_demirbas_id').value = req.id;
                    
                    const start = new Date(req.baslangic_tarihi);
                    const end = new Date(req.bitis_tarihi);
                    start.setMinutes(start.getMinutes() - start.getTimezoneOffset());
                    end.setMinutes(end.getMinutes() - end.getTimezoneOffset());
                    
                    document.getElementById('edit_baslangic').value = start.toISOString().slice(0, 16);
                    document.getElementById('edit_bitis').value = end.toISOString().slice(0, 16);
                    document.getElementById('edit_durum').value = req.durum;
                    document.getElementById('edit_aciklama').value = req.aciklama || '';
                    
                    const modal = new bootstrap.Modal(document.getElementById('editDemirbasModal'));
                    modal.show();
                }
                </script>
                <?php endif; ?>
                
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

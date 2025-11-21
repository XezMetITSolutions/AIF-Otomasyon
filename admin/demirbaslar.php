<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireAuth();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

// Yetki kontrolü (Sadece yetkili başkanlar veya süper admin)
if (!$auth->hasModulePermission('baskan_demirbas_yonetimi') && $user['role'] !== 'super_admin') {
    // Geçici olarak baskan_demirbas_talepleri yetkisi olanlara da izin verelim veya hata döndürelim
    // Şimdilik baskan_demirbas_talepleri yetkisi olanlar da görebilsin (veya yeni yetki ekleyeceğiz)
    if (!$auth->hasModulePermission('baskan_demirbas_talepleri')) {
        die('Bu sayfayı görüntüleme yetkiniz yok.');
    }
}

$pageTitle = 'Demirbaş Yönetimi';
$uploadDir = __DIR__ . '/../uploads/demirbaslar/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$success = '';
$error = '';

// İşlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $ad = $_POST['ad'] ?? '';
        $kategori = $_POST['kategori'] ?? '';
        $konum = $_POST['konum'] ?? '';
        $sorumlu_id = !empty($_POST['sorumlu_id']) ? $_POST['sorumlu_id'] : null;
        $durum = $_POST['durum'] ?? 'musait';
        $id = $_POST['id'] ?? null;
        
        // Takip alanları (kirada/bakımda için)
        $mevcut_kullanici_id = !empty($_POST['mevcut_kullanici_id']) ? $_POST['mevcut_kullanici_id'] : null;
        $verilis_tarihi = !empty($_POST['verilis_tarihi']) ? $_POST['verilis_tarihi'] : null;
        $donus_tarihi = !empty($_POST['donus_tarihi']) ? $_POST['donus_tarihi'] : null;
        $notlar = $_POST['notlar'] ?? null;
        
        // Eğer durum musait veya arizali ise takip alanlarını temizle
        if ($durum === 'musait' || $durum === 'arizali') {
            $mevcut_kullanici_id = null;
            $verilis_tarihi = null;
            $donus_tarihi = null;
            $notlar = null;
        }

        // Fotoğraf Yükleme
        $fotograf_yolu = $_POST['mevcut_fotograf'] ?? null;
        if (isset($_FILES['fotograf']) && $_FILES['fotograf']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['fotograf']['tmp_name'];
            $name = basename($_FILES['fotograf']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $newName = uniqid('img_') . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                    $fotograf_yolu = 'uploads/demirbaslar/' . $newName;
                }
            }
        }

        if ($action === 'add') {
            try {
                $db->query(
                    "INSERT INTO demirbaslar (ad, kategori, konum, sorumlu_kisi_id, durum, fotograf_yolu, mevcut_kullanici_id, verilis_tarihi, donus_tarihi, notlar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$ad, $kategori, $konum, $sorumlu_id, $durum, $fotograf_yolu, $mevcut_kullanici_id, $verilis_tarihi, $donus_tarihi, $notlar]
                );
                $success = 'Demirbaş başarıyla eklendi.';
            } catch (Exception $e) {
                $error = 'Hata: ' . $e->getMessage();
            }
        } elseif ($action === 'edit' && $id) {
            try {
                $db->query(
                    "UPDATE demirbaslar SET ad = ?, kategori = ?, konum = ?, sorumlu_kisi_id = ?, durum = ?, fotograf_yolu = ?, mevcut_kullanici_id = ?, verilis_tarihi = ?, donus_tarihi = ?, notlar = ? WHERE id = ?",
                    [$ad, $kategori, $konum, $sorumlu_id, $durum, $fotograf_yolu, $mevcut_kullanici_id, $verilis_tarihi, $donus_tarihi, $notlar, $id]
                );
                $success = 'Demirbaş güncellendi.';
            } catch (Exception $e) {
                $error = 'Hata: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        try {
            $db->query("DELETE FROM demirbaslar WHERE id = ?", [$_POST['id']]);
            $success = 'Demirbaş silindi.';
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    }
}

// Demirbaşları Listele
$demirbaslar = $db->fetchAll("
    SELECT d.*, 
           CONCAT(u.ad, ' ', u.soyad) as sorumlu_adi,
           CONCAT(mk.ad, ' ', mk.soyad) as mevcut_kullanici_adi
    FROM demirbaslar d 
    LEFT JOIN kullanicilar u ON d.sorumlu_kisi_id = u.kullanici_id 
    LEFT JOIN kullanicilar mk ON d.mevcut_kullanici_id = mk.kullanici_id
    ORDER BY d.created_at DESC
");

// Kullanıcıları Listele (Sorumlu seçimi için)
$kullanicilar = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar ORDER BY ad ASC");

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-box me-2"></i>Demirbaş Yönetimi
            </h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Yeni Demirbaş Ekle
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Fotoğraf</th>
                                <th>Ad</th>
                                <th>Kategori</th>
                                <th>Konum</th>
                                <th>Sorumlu</th>
                                <th>Durum</th>
                                <th>Detaylar</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($demirbaslar)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Kayıtlı demirbaş bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($demirbaslar as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if ($item['fotograf_yolu']): ?>
                                                <img src="/<?php echo htmlspecialchars($item['fotograf_yolu']); ?>" alt="Foto" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center border rounded" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($item['ad']); ?></td>
                                        <td><?php echo htmlspecialchars($item['kategori']); ?></td>
                                        <td><?php echo htmlspecialchars($item['konum']); ?></td>
                                        <td><?php echo htmlspecialchars($item['sorumlu_adi'] ?? '-'); ?></td>
                                        <td>
                                            <?php 
                                            $badges = [
                                                'musait' => 'success',
                                                'kirada' => 'warning',
                                                'bakimda' => 'info',
                                                'arizali' => 'danger'
                                            ];
                                            $labels = [
                                                'musait' => 'Müsait',
                                                'kirada' => 'Kirada',
                                                'bakimda' => 'Bakımda',
                                                'arizali' => 'Arızalı'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $badges[$item['durum']]; ?>">
                                                <?php echo $labels[$item['durum']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (($item['durum'] === 'kirada' || $item['durum'] === 'bakimda') && $item['mevcut_kullanici_id']): ?>
                                                <small>
                                                    <strong><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item['mevcut_kullanici_adi']); ?></strong><br>
                                                    <?php if ($item['verilis_tarihi']): ?>
                                                        <i class="fas fa-calendar-alt me-1"></i><?php echo date('d.m.Y', strtotime($item['verilis_tarihi'])); ?>
                                                    <?php endif; ?>
                                                    <?php if ($item['donus_tarihi']): ?>
                                                        → <?php echo date('d.m.Y', strtotime($item['donus_tarihi'])); ?>
                                                    <?php endif; ?>
                                                    <?php if ($item['notlar']): ?>
                                                        <br><i class="fas fa-sticky-note me-1"></i><em><?php echo htmlspecialchars($item['notlar']); ?></em>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick='editItem(<?php echo json_encode($item); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

<!-- Edit/Add Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" id="itemForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="itemId">
                <input type="hidden" name="mevcut_fotograf" id="mevcutFotograf">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Yeni Demirbaş Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Demirbaş Adı</label>
                        <input type="text" class="form-control" name="ad" id="itemAd" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" name="kategori" id="itemKategori" list="kategoriList">
                            <datalist id="kategoriList">
                                <option value="Elektronik">
                                <option value="Mobilya">
                                <option value="Kırtasiye">
                            </datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Konum</label>
                            <input type="text" class="form-control" name="konum" id="itemKonum">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sorumlu Kişi</label>
                        <select class="form-select" name="sorumlu_id" id="itemSorumlu">
                            <option value="">Seçiniz...</option>
                            <?php foreach ($kullanicilar as $k): ?>
                                <option value="<?php echo $k['kullanici_id']; ?>">
                                    <?php echo htmlspecialchars($k['ad'] . ' ' . $k['soyad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="durum" id="itemDurum" onchange="toggleTrackingFields()">
                            <option value="musait">Müsait</option>
                            <option value="kirada">Kirada</option>
                            <option value="bakimda">Bakımda</option>
                            <option value="arizali">Arızalı</option>
                        </select>
                    </div>
                    
                    <!-- Takip Alanları (Kirada/Bakımda için) -->
                    <div id="trackingFields" style="display: none;">
                        <hr>
                        <h6 class="text-muted mb-3"><i class="fas fa-info-circle me-2"></i>Takip Bilgileri</h6>
                        <div class="mb-3">
                            <label class="form-label">Kimde / Nerede</label>
                            <select class="form-select" name="mevcut_kullanici_id" id="itemMevcutKullanici">
                                <option value="">Seçiniz...</option>
                                <?php foreach ($kullanicilar as $k): ?>
                                    <option value="<?php echo $k['kullanici_id']; ?>">
                                        <?php echo htmlspecialchars($k['ad'] . ' ' . $k['soyad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Veriliş Tarihi</label>
                                <input type="date" class="form-control" name="verilis_tarihi" id="itemVerilisTarihi">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dönüş Tarihi</label>
                                <input type="date" class="form-control" name="donus_tarihi" id="itemDonusTarihi">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notlar</label>
                            <textarea class="form-control" name="notlar" id="itemNotlar" rows="2" placeholder="Ek açıklamalar..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fotoğraf</label>
                        <input type="file" class="form-control" name="fotograf" accept="image/*">
                        <div id="currentImagePreview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTrackingFields() {
    var durum = document.getElementById('itemDurum').value;
    var trackingFields = document.getElementById('trackingFields');
    
    if (durum === 'kirada' || durum === 'bakimda') {
        trackingFields.style.display = 'block';
    } else {
        trackingFields.style.display = 'none';
        // Temizle
        document.getElementById('itemMevcutKullanici').value = '';
        document.getElementById('itemVerilisTarihi').value = '';
        document.getElementById('itemDonusTarihi').value = '';
        document.getElementById('itemNotlar').value = '';
    }
}

function resetForm() {
    document.getElementById('itemForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('itemId').value = '';
    document.getElementById('modalTitle').innerText = 'Yeni Demirbaş Ekle';
    document.getElementById('currentImagePreview').innerHTML = '';
    document.getElementById('trackingFields').style.display = 'none';
}

function editItem(item) {
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    document.getElementById('formAction').value = 'edit';
    document.getElementById('itemId').value = item.id;
    document.getElementById('itemAd').value = item.ad;
    document.getElementById('itemKategori').value = item.kategori;
    document.getElementById('itemKonum').value = item.konum;
    document.getElementById('itemSorumlu').value = item.sorumlu_kisi_id || '';
    document.getElementById('itemDurum').value = item.durum;
    document.getElementById('mevcutFotograf').value = item.fotograf_yolu;
    document.getElementById('modalTitle').innerText = 'Demirbaş Düzenle';
    
    // Takip alanları
    document.getElementById('itemMevcutKullanici').value = item.mevcut_kullanici_id || '';
    document.getElementById('itemVerilisTarihi').value = item.verilis_tarihi || '';
    document.getElementById('itemDonusTarihi').value = item.donus_tarihi || '';
    document.getElementById('itemNotlar').value = item.notlar || '';
    
    // Takip alanlarını göster/gizle
    toggleTrackingFields();
    
    if (item.fotograf_yolu) {
        document.getElementById('currentImagePreview').innerHTML = 
            '<img src="/' + item.fotograf_yolu + '" class="img-thumbnail" style="height: 100px;">';
    } else {
        document.getElementById('currentImagePreview').innerHTML = '';
    }
    
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

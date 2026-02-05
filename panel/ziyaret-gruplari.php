<?php
/**
 * Ziyaret Grupları Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

$auth = new Auth();
$user = $auth->getUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$db = Database::getInstance();
require_once __DIR__ . '/../includes/ensure_sube_ziyaretleri_tables.php';

// AT Birimi veya Super Admin Kontrolü
$isAT = false;
$userByk = $db->fetch("SELECT b.byk_kodu FROM byk b WHERE b.byk_id = ?", [$user['byk_id']]);
if ($userByk && $userByk['byk_kodu'] === 'AT') {
    $isAT = true;
}

if (!$isAT && $user['role'] !== 'super_admin') {
    header('Location: /access-denied.php');
    exit;
}

$pageTitle = 'Ziyaret Grupları';

// Yetki Kontrolü
$canManage = $auth->hasModulePermission('baskan_sube_ziyaretleri');

// Grup Ekleme/Düzenleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $grup_adi = trim($_POST['grup_adi'] ?? '');
        $baskan_id = $_POST['baskan_id'] ?? null;
        $renk_kodu = $_POST['renk_kodu'] ?? '#009872';
        $uyeler = $_POST['uyeler'] ?? [];
        
        try {
            if ($action === 'add') {
                $db->query("INSERT INTO ziyaret_gruplari (grup_adi, baskan_id, renk_kodu) VALUES (?, ?, ?)", [$grup_adi, $baskan_id, $renk_kodu]);
                $grup_id = $db->lastInsertId();
            } else {
                $grup_id = $_POST['grup_id'];
                $db->query("UPDATE ziyaret_gruplari SET grup_adi = ?, baskan_id = ?, renk_kodu = ? WHERE grup_id = ?", [$grup_adi, $baskan_id, $renk_kodu, $grup_id]);
                // Mevcut üyeleri temizle
                $db->query("DELETE FROM ziyaret_grup_uyeleri WHERE grup_id = ?", [$grup_id]);
            }
            
            // Üyeleri ekle
            foreach ($uyeler as $uye_id) {
                $db->query("INSERT INTO ziyaret_grup_uyeleri (grup_id, kullanici_id) VALUES (?, ?)", [$grup_id, $uye_id]);
            }
            
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Grup başarıyla kaydedildi.'];
        } catch (Exception $e) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Hata: ' . $e->getMessage()];
        }
        header("Location: ziyaret-gruplari.php");
        exit;
    }
    
    if ($action === 'delete') {
        $grup_id = $_POST['grup_id'];
        try {
            $db->query("DELETE FROM ziyaret_gruplari WHERE grup_id = ?", [$grup_id]);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Grup başarıyla silindi.'];
        } catch (Exception $e) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Hata: ' . $e->getMessage()];
        }
        header("Location: ziyaret-gruplari.php");
        exit;
    }
}

// Grupları ve üyelerini getir
$gruplar = $db->fetchAll("
    SELECT g.*, 
    CONCAT(u.ad, ' ', u.soyad) as baskan_adi,
    (SELECT COUNT(*) FROM ziyaret_grup_uyeleri gu WHERE gu.grup_id = g.grup_id) as uye_sayisi
    FROM ziyaret_gruplari g 
    LEFT JOIN kullanicilar u ON g.baskan_id = u.kullanici_id
    ORDER BY g.grup_adi
");

// Her grubun üyelerini ayrı ayrı çekelim
foreach ($gruplar as &$grup) {
    $grup['uye_listesi'] = $db->fetchAll("
        SELECT k.kullanici_id, k.ad, k.soyad 
        FROM ziyaret_grup_uyeleri gu 
        JOIN kullanicilar k ON gu.kullanici_id = k.kullanici_id 
        WHERE gu.grup_id = ?
    ", [$grup['grup_id']]);
}

// Tüm üyeleri getir (grup ataması için)
$tum_uyeler = $db->fetchAll("SELECT kullanici_id, ad, soyad FROM kullanicilar WHERE aktif = 1 ORDER BY ad, soyad");

include __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #009872;
        --primary-light: rgba(0, 152, 114, 0.1);
        --text-dark: #1e293b;
        --card-bg: rgba(255, 255, 255, 0.9);
        --glass-border: 1px solid rgba(255, 255, 255, 0.5);
    }

    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at 0% 0%, rgba(0, 152, 114, 0.08) 0%, transparent 50%),
            radial-gradient(circle at 100% 100%, rgba(0, 152, 114, 0.05) 0%, transparent 50%),
            #f8fafc;
        color: var(--text-dark);
    }

    .card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border: var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-radius: 1rem;
    }

    .dashboard-layout { display: flex; }
    .sidebar-wrapper { width: 250px; flex-shrink: 0; }
    .main-content { flex-grow: 1; padding: 1.5rem 2rem; max-width: 1400px; margin: 0 auto; }

    @media (max-width: 991px) {
        .dashboard-layout { display: block; }
        .sidebar-wrapper { display: none; }
        .main-content { padding: 1rem; }
    }
</style>

<div class="dashboard-layout">
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><i class="fas fa-users-rectangle me-2 text-primary"></i>Ziyaret Grupları</h1>
                <p class="text-muted mb-0">Şube ziyaretlerini gerçekleştirecek ekipleri yönetin.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="sube_ziyaretleri.php" class="btn btn-outline-secondary rounded-pill">
                    <i class="fas fa-arrow-left me-2"></i>Ziyaretlere Dön
                </a>
                <?php if ($canManage): ?>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#grupModal" onclick="prepareAdd()">
                        <i class="fas fa-plus me-2"></i>Yeni Grup Ekle
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show mb-4">
                <?php echo $_SESSION['message']['text']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (empty($gruplar)): ?>
                <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="fas fa-users-slash fa-4x text-muted opacity-25 mb-4"></i>
                    <h5 class="text-muted">Henüz ziyaret grubu oluşturulmamış.</h5>
                    <p class="text-muted">Ziyaret planlayabilmek için önce bir grup oluşturmalısınız.</p>
                </div>
            <?php else: ?>
                <?php foreach ($gruplar as $grup): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 shadow-sm border-start-5" style="border-left: 5px solid <?php echo $grup['renk_kodu']; ?> !important;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($grup['grup_adi']); ?></h5>
                                    <?php if ($canManage): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick='prepareEdit(<?php echo json_encode($grup); ?>)' data-bs-toggle="modal" data-bs-target="#grupModal"><i class="fas fa-edit me-2"></i>Düzenle</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="prepareDelete(<?php echo $grup['grup_id']; ?>, '<?php echo addslashes($grup['grup_adi']); ?>')" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="fas fa-trash me-2"></i>Sil</a></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                    <div class="mb-3">
                                        <label class="small text-muted d-block mb-1">Grup Başkanı</label>
                                        <?php if ($grup['baskan_id']): ?>
                                            <div class="fw-bold text-primary">
                                                <i class="fas fa-user-tie me-1"></i> <?php echo htmlspecialchars($grup['baskan_adi']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Atanmamış</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-2">
                                        <label class="small text-muted d-block mb-2">Grup Üyeleri (<?php echo $grup['uye_sayisi']; ?>)</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if (empty($grup['uye_listesi'])): ?>
                                            <span class="text-muted small italic">Üye atanmamış</span>
                                        <?php else: ?>
                                            <?php foreach ($grup['uye_listesi'] as $uye): ?>
                                                <span class="badge bg-light text-dark border p-2 rounded-3">
                                                    <i class="fas fa-user-circle me-1 text-primary shadow-sm"></i>
                                                    <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Grup Ekle/Düzenle Modal -->
<div class="modal fade" id="grupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="grup_id" id="modalGrupId">
            
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Yeni Grup Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6 text-truncate">
                        <label class="form-label fw-semibold">Grup Adı</label>
                        <input type="text" name="grup_adi" id="modalGrupAdi" class="form-control" required placeholder="Örn: 1. Ziyaret Grubu">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Grup Başkanı</label>
                        <select name="baskan_id" id="modalBaskanId" class="form-select">
                            <option value="">Başkan Seçin...</option>
                            <?php foreach ($tum_uyeler as $uye): ?>
                                <option value="<?php echo $uye['kullanici_id']; ?>">
                                    <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Grup Rengi</label>
                        <input type="color" name="renk_kodu" id="modalRenkKodu" class="form-control form-control-color w-100" value="#009872">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Üye Seçimi</label>
                        <div class="border rounded-3 p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                <?php foreach ($tum_uyeler as $uye): ?>
                                    <div class="col">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="uyeler[]" value="<?php echo $uye['kullanici_id']; ?>" id="uye_<?php echo $uye['kullanici_id']; ?>">
                                            <label class="form-check-label small" for="uye_<?php echo $uye['kullanici_id']; ?>">
                                                <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" id="modalSubmitBtn">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<!-- Silme Onay Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="grup_id" id="deleteGrupId">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">Grubu Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>⚠️ <strong id="deleteGrupAdi"></strong> grubunu silmek istediğinize emin misiniz?</p>
                <p class="text-muted small mb-0">Bu işleme bağlı ziyaret kayıtları silinmeyebilir ancak grup referansı kaybolacaktır.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-danger rounded-pill px-4">Evet, Sil</button>
            </div>
        </form>
    </div>
</div>

<script>
    function prepareAdd() {
        document.getElementById('modalAction').value = 'add';
        document.getElementById('modalTitle').innerText = 'Yeni Grup Ekle';
        document.getElementById('modalSubmitBtn').innerText = 'Kaydet';
        document.getElementById('modalGrupId').value = '';
        document.getElementById('modalGrupAdi').value = '';
        document.getElementById('modalBaskanId').value = '';
        document.getElementById('modalRenkKodu').value = '#009872';
        
        // Checkboxları temizle
        document.querySelectorAll('.form-check-input').forEach(cb => cb.checked = false);
    }

    function prepareEdit(grup) {
        document.getElementById('modalAction').value = 'edit';
        document.getElementById('modalTitle').innerText = 'Grubu Düzenle';
        document.getElementById('modalSubmitBtn').innerText = 'Değişiklikleri Kaydet';
        document.getElementById('modalGrupId').value = grup.grup_id;
        document.getElementById('modalGrupAdi').value = grup.grup_adi;
        document.getElementById('modalBaskanId').value = grup.baskan_id || '';
        document.getElementById('modalRenkKodu').value = grup.renk_kodu;
        
        // Checkboxları temizle ve mevcut üyeleri işaretle
        document.querySelectorAll('.form-check-input').forEach(cb => cb.checked = false);
        grup.uye_listesi.forEach(uye => {
            const cb = document.getElementById('uye_' + uye.kullanici_id);
            if (cb) cb.checked = true;
        });
    }

    function prepareDelete(id, adi) {
        document.getElementById('deleteGrupId').value = id;
        document.getElementById('deleteGrupAdi').innerText = adi;
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

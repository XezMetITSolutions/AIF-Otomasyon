<?php
/**
 * Harcama Talepleri Modülü
 * Taleplerim (Herkes) + Onay İşlemleri (Yetkili)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

require_once __DIR__ . '/../classes/Mail.php';

Middleware::requireUye();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

// Yetkiler
$hasPermissionBaskan = $auth->hasModulePermission('baskan_harcama_talepleri');
$hasPermissionUye = $auth->hasModulePermission('uye_harcama_talepleri');

if (!$hasPermissionBaskan && !$hasPermissionUye) {
    header('Location: /panel/dashboard.php');
    exit;
}

$activeTab = $_GET['tab'] ?? ($hasPermissionBaskan ? 'onay' : 'talebim');

$pageTitle = 'Harcama Talepleri';
$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$errors = [];
$messages = [];

$durumBadgeMap = [
    'beklemede' => 'warning',
    'onaylandi' => 'success',
    'reddedildi' => 'danger',
    'odenmistir' => 'primary'
];

$kategoriListesi = [
    'genel' => 'Genel',
    'hediye' => 'Hediye & Ödül',
    'seyahat' => 'Seyahat',
    'etkinlik' => 'Etkinlik Organizasyonu',
    'ikram' => 'İkram / Ağırlama',
    'donanim' => 'Ekipman / Donanım',
    'egitim' => 'Eğitim / Seminer'
];

// Helper functions for Meta handling
if (!function_exists('splitHarcamaAciklamaVeMeta')) {
    function splitHarcamaAciklamaVeMeta(?string $aciklama): array {
        $marker = '---META---';
        if (!$aciklama || mb_strpos($aciklama, $marker) === false) {
            return [trim($aciklama ?? ''), null];
        }
        $pos = mb_strrpos($aciklama, $marker);
        if ($pos === false) {
            return [trim($aciklama), null];
        }
        $metaJson = trim(mb_substr($aciklama, $pos + mb_strlen($marker)));
        $metin = trim(mb_substr($aciklama, 0, $pos));
        $meta = json_decode($metaJson, true);
        if (!is_array($meta)) {
            return [trim($aciklama), null];
        }
        return [$metin, $meta];
    }

    function buildHarcamaAciklamaMetni(?string $kullaniciAciklama, array $meta): string {
        $marker = '---META---';
        $trimmed = trim($kullaniciAciklama ?? '');
        $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
        if ($trimmed !== '') {
            return $trimmed . "\n\n" . $marker . $metaJson;
        }
        return $marker . $metaJson;
    }
}

// POST HİNDLİNG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Oturum doğrulaması başarısız oldu.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Yeni Talep (Üye)
        if ($action === 'yeni_harcama' && $hasPermissionUye) {
            $baslik = trim($_POST['baslik'] ?? '');
            $tutar = $_POST['tutar'] ?? '';
            $kategori = $_POST['kategori'] ?? 'genel';
            $aciklama = trim($_POST['aciklama'] ?? '');
            
            // Seyahat alanları
            $seyahat_cikis = trim($_POST['seyahat_cikis'] ?? '');
            $seyahat_varis = trim($_POST['seyahat_varis'] ?? '');
            $seyahat_gidis = $_POST['seyahat_gidis'] ?? '';
            $seyahat_donus = $_POST['seyahat_donus'] ?? '';
            $otel_gerekli = isset($_POST['otel_gerekli']);

            if ($baslik === '') $errors[] = 'Talep başlığı zorunludur.';
            if (!is_numeric($tutar) || (float)$tutar <= 0) $errors[] = 'Geçerli bir tutar giriniz.';
            if (!array_key_exists($kategori, $kategoriListesi)) $errors[] = 'Geçersiz kategori.';
            
            if ($kategori === 'seyahat') {
                if ($seyahat_cikis === '' || $seyahat_varis === '') $errors[] = 'Güzergâh bilgisi zorunludur.';
                if (!$seyahat_gidis || !$seyahat_donus) $errors[] = 'Tarihler zorunludur.';
                elseif ($seyahat_donus < $seyahat_gidis) $errors[] = 'Dönüş tarihi hatalı.';
            }

            if (empty($errors)) {
                $meta = ['kategori' => $kategori];
                if ($kategori === 'seyahat') {
                    $meta['seyahat'] = [
                        'cikis' => $seyahat_cikis,
                        'varis' => $seyahat_varis,
                        'gidis' => $seyahat_gidis,
                        'donus' => $seyahat_donus,
                        'otel' => $otel_gerekli
                    ];
                }
                $aciklamaFinal = buildHarcamaAciklamaMetni($aciklama, $meta);

                $db->query("
                    INSERT INTO harcama_talepleri (kullanici_id, byk_id, baslik, aciklama, tutar, durum)
                    VALUES (?, ?, ?, ?, ?, 'beklemede')
                ", [
                    $user['id'],
                    $user['byk_id'],
                    $baslik,
                    $aciklamaFinal,
                    number_format((float)$tutar, 2, '.', '')
                ]);

                // Birim Muhasebe Başkanına Bildirim Gönder
                $bykInfo = $db->fetch("SELECT muhasebe_baskani_id FROM byk WHERE byk_id = ?", [$user['byk_id']]);
                if ($bykInfo && $bykInfo['muhasebe_baskani_id']) {
                    $adminUser = $db->fetch("SELECT email FROM kullanicilar WHERE kullanici_id = ?", [$bykInfo['muhasebe_baskani_id']]);
                    if ($adminUser) {
                        Mail::sendWithTemplate($adminUser['email'], 'talep_yeni', [
                            'ad_soyad' => 'Muhasebe Başkanı',
                            'talep_turu' => 'Harcama Talebi',
                            'detay' => htmlspecialchars($user['name']) . " tarafından yeni bir harcama talebi oluşturuldu: " . htmlspecialchars($baslik) . " (" . number_format($tutar, 2, ',', '.') . " €)",
                        ]);
                    }
                }

                $messages[] = 'Harcama talebiniz başarıyla oluşturuldu.';
                $activeTab = 'talebim';
            }
        
        // 2. Onay/Red (Başkan)
        } elseif (($action === 'approve' || $action === 'reject') && $hasPermissionBaskan) {
            $talepId = (int)($_POST['talep_id'] ?? 0);
            $aciklama = trim($_POST['aciklama'] ?? '');

            $talep = $db->fetch("
                SELECT * FROM harcama_talepleri
                WHERE talep_id = ? AND byk_id = ?
            ", [$talepId, $user['byk_id']]);

            if (!$talep) {
                $errors[] = 'Talep bulunamadı.';
            } elseif ($talep['durum'] !== 'beklemede') {
                $errors[] = 'Bu talep zaten işlem görmüş.';
            } else {
                $yeniDurum = ($action === 'approve') ? 'onaylandi' : 'reddedildi';
                $db->query("
                    UPDATE harcama_talepleri
                    SET durum = ?, onaylayan_id = ?, onay_tarihi = NOW(), onay_aciklama = ?
                    WHERE talep_id = ?
                ", [$yeniDurum, $user['id'], $aciklama ?: null, $talepId]);
                $messages[] = 'İşlem başarılı: ' . ucfirst($yeniDurum);
                $activeTab = 'onay';
            }

        // 3. Tekli Silme (Başkan)
        } elseif ($action === 'delete' && $hasPermissionBaskan) {
            $talepId = (int)($_POST['talep_id'] ?? 0);
            if ($talepId) {
                $db->query("DELETE FROM harcama_talepleri WHERE talep_id = ? AND byk_id = ?", [$talepId, $user['byk_id']]);
                $messages[] = 'Harcama talebi silindi.';
                $activeTab = 'onay';
            }

        // 4. Toplu Silme (Başkan)
        } elseif ($action === 'bulk_delete' && $hasPermissionBaskan) {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids) && is_array($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $params = array_merge($ids, [$user['byk_id']]);
                $db->query("DELETE FROM harcama_talepleri WHERE talep_id IN ($placeholders) AND byk_id = ?", $params);
                $messages[] = count($ids) . ' harcama talebi silindi.';
                $activeTab = 'onay';
            }

        // 5. Düzenleme (Başkan)
        } elseif ($action === 'edit' && $hasPermissionBaskan) {
            $talepId = (int)($_POST['talep_id'] ?? 0);
            $baslik = trim($_POST['baslik'] ?? '');
            $tutar = $_POST['tutar'] ?? '';
            $durum = $_POST['durum'] ?? 'beklemede';
            $aciklama = trim($_POST['aciklama'] ?? '');

            if ($talepId && $baslik && is_numeric($tutar)) {
                $db->query("
                    UPDATE harcama_talepleri
                    SET baslik = ?, tutar = ?, durum = ?, aciklama = ?
                    WHERE talep_id = ? AND byk_id = ?
                ", [$baslik, number_format((float)$tutar, 2, '.', ''), $durum, $aciklama, $talepId, $user['byk_id']]);
                $messages[] = 'Harcama talebi güncellendi.';
                $activeTab = 'onay';
            } else {
                $errors[] = 'Tüm alanları doldurunuz.';
            }
        }
    }
}

// --- VERİ ÇEKME ---

// 1. Taleplerim
$myRequests = [];
if ($hasPermissionUye) {
    $myRequests = $db->fetchAll("
        SELECT *
        FROM harcama_talepleri
        WHERE kullanici_id = ?
        ORDER BY olusturma_tarihi DESC
    ", [$user['id']]);
}

// 2. Onay Listesi (Yönetim)
$pendingRequests = [];
$durumFilter = $_GET['durum'] ?? 'beklemede';
if ($hasPermissionBaskan) {
    $filters = ['ht.byk_id = ?'];
    $params = [$user['byk_id']];
    if ($durumFilter) {
        $filters[] = 'ht.durum = ?';
        $params[] = $durumFilter;
    }
    $where = 'WHERE ' . implode(' AND ', $filters);
    
    $pendingRequests = $db->fetchAll("
        SELECT ht.*, CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, k.email, k.telefon
        FROM harcama_talepleri ht
        INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id
        $where
        ORDER BY ht.olusturma_tarihi DESC
        LIMIT 100
    ", $params);
}

include __DIR__ . '/../includes/header.php';
?>

<style>
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
</style>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
         <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-wallet me-2"></i>Harcama Talepleri
                </h1>
                <p class="text-muted mb-0">Harcama ve gider süreçlerini buradan yönetin.</p>
            </div>
            
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

        <div class="tab-content">
            <!-- TAB 1: TALEPLERİM -->
            <?php if ($hasPermissionUye && $activeTab === 'talebim'): ?>
            <div class="tab-pane fade show active">
                <div class="row g-4">
                     <!-- Form -->
                    <div class="col-lg-4">
                        <div class="card h-100">
                             <div class="card-header bg-transparent border-bottom-0 fw-bold">Yeni Harcama Talebi</div>
                             <div class="card-body pt-0">
                                <form method="post">
                                    <input type="hidden" name="action" value="yeni_harcama">
                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Başlık</label>
                                        <input type="text" name="baslik" class="form-control" required placeholder="Örn. Kırtasiye Alımı">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Kategori</label>
                                        <select name="kategori" id="kategoriSelect" class="form-select" required>
                                            <?php foreach ($kategoriListesi as $key => $label): ?>
                                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Tutar (€)</label>
                                        <input type="number" name="tutar" step="0.01" min="0" class="form-control" required>
                                    </div>

                                    <!-- Seyahat Fields -->
                                    <div id="travelFields" class="d-none border rounded p-3 mb-3 bg-light">
                                        <div class="mb-2">
                                            <label class="form-label small">Güzergâh (Çıkış -> Varış)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="seyahat_cikis" class="form-control" placeholder="Nereden">
                                                <span class="input-group-text">-></span>
                                                <input type="text" name="seyahat_varis" class="form-control" placeholder="Nereye">
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label small">Gidiş</label>
                                                <input type="date" name="seyahat_gidis" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small">Dönüş</label>
                                                <input type="date" name="seyahat_donus" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="otel_gerekli" id="otelCheck">
                                            <label class="form-check-label small" for="otelCheck">Otel Gerekli</label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-muted fw-bold">Açıklama</label>
                                        <textarea name="aciklama" class="form-control" rows="3"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-1"></i>Talep Oluştur
                                    </button>
                                </form>
                             </div>
                        </div>
                    </div>
                    
                    <!-- List -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Geçmiş Taleplerim</span>
                                <span class="badge bg-light text-dark"><?php echo count($myRequests); ?> Kayıt</span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($myRequests)): ?>
                                    <div class="text-center p-5 text-muted">
                                        <i class="fas fa-receipt fa-2x mb-3 opacity-25"></i>
                                        <p>Henüz harcama talebiniz yok.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Başlık / Kategori</th>
                                                    <th>Tutar</th>
                                                    <th>Durum</th>
                                                    <th>Tarih</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($myRequests as $req): 
                                                    $badge = $durumBadgeMap[$req['durum']] ?? 'secondary';
                                                    [, $meta] = splitHarcamaAciklamaVeMeta($req['aciklama']);
                                                    $catLabel = $kategoriListesi[$meta['kategori'] ?? 'genel'] ?? 'Genel';
                                                ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-medium text-dark"><?php echo htmlspecialchars($req['baslik']); ?></div>
                                                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.7rem;">
                                                            <?php echo htmlspecialchars($catLabel); ?>
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold text-success">
                                                        <?php echo number_format($req['tutar'], 2, ',', '.'); ?> €
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $badge; ?> bg-opacity-10 text-<?php echo $badge; ?> px-2 py-1 border border-<?php echo $badge; ?> border-opacity-10">
                                                            <?php echo ucfirst($req['durum']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="small text-muted">
                                                        <?php echo date('d.m.Y', strtotime($req['olusturma_tarihi'])); ?>
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
            
            <script>
                // Simple Toggle for Travel Fields
                const catSelect = document.getElementById('kategoriSelect');
                const travelDiv = document.getElementById('travelFields');
                if(catSelect && travelDiv) {
                    catSelect.addEventListener('change', function() {
                        if(this.value === 'seyahat') travelDiv.classList.remove('d-none');
                        else travelDiv.classList.add('d-none');
                    });
                }
            </script>
            <?php endif; ?>

            <!-- TAB 2: YÖNETİM -->
            <?php if ($hasPermissionBaskan && $activeTab === 'onay'): ?>
            <div class="tab-pane fade show active">
                 <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="btn-group">
                            <a href="?tab=onay&durum=beklemede" class="btn btn-sm <?php echo $durumFilter === 'beklemede' ? 'btn-warning' : 'btn-outline-warning'; ?>">Bekleyen</a>
                            <a href="?tab=onay&durum=onaylandi" class="btn btn-sm <?php echo $durumFilter === 'onaylandi' ? 'btn-success' : 'btn-outline-success'; ?>">Onaylanan</a>
                            <a href="?tab=onay&durum=reddedildi" class="btn btn-sm <?php echo $durumFilter === 'reddedildi' ? 'btn-danger' : 'btn-outline-danger'; ?>">Reddedilen</a>
                            <a href="?tab=onay&durum=" class="btn btn-sm <?php echo $durumFilter === '' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">Tümü</a>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display:none;" onclick="bulkDeleteHarcama()">
                                <i class="fas fa-trash me-1"></i>Seçilenleri Sil
                            </button>
                            <span class="badge bg-light text-dark border"><?php echo count($pendingRequests); ?> Kayıt</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($pendingRequests)): ?>
                            <div class="text-center p-5 text-muted">Kayıt bulunamadı.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-3" style="width: 40px;">
                                                <input type="checkbox" id="selectAllHarcama" class="form-check-input">
                                            </th>
                                            <th>Kullanıcı</th>
                                            <th>Başlık / Kategori</th>
                                            <th>Tutar</th>
                                            <th>Açıklama</th>
                                            <th>Durum</th>
                                            <th class="text-end pe-3">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingRequests as $req):
                                            $badge = $durumBadgeMap[$req['durum']] ?? 'secondary';
                                            [$desc, $meta] = splitHarcamaAciklamaVeMeta($req['aciklama']);
                                            $catLabel = $kategoriListesi[$meta['kategori'] ?? 'genel'] ?? 'Genel';
                                        ?>
                                        <tr>
                                            <td class="ps-3">
                                                <input type="checkbox" class="form-check-input row-checkbox-harcama" value="<?php echo $req['talep_id']; ?>">
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($req['kullanici_adi']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($req['email']); ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-medium"><?php echo htmlspecialchars($req['baslik']); ?></div>
                                                <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.7rem;">
                                                    <?php echo htmlspecialchars($catLabel); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-success">
                                                <?php echo number_format($req['tutar'], 2, ',', '.'); ?> €
                                            </td>
                                            <td>
                                                <small class="text-muted d-inline-block text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($desc); ?>">
                                                    <?php echo htmlspecialchars($desc); ?>
                                                </small>
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
                                                            <input type="hidden" name="talep_id" value="<?php echo $req['talep_id']; ?>">
                                                            <button class="btn btn-sm btn-success" type="submit" title="Onayla"><i class="fas fa-check"></i></button>
                                                        </form>
                                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal" data-id="<?php echo $req['talep_id']; ?>" title="Reddet">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-primary" onclick='editHarcama(<?php echo json_encode($req); ?>)' title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="post" class="d-inline-block" onsubmit="return confirm('Bu harcama talebini silmek istediğinizden emin misiniz?');">
                                                        <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="talep_id" value="<?php echo $req['talep_id']; ?>">
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

            <!-- Reddet Modal -->
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reddet</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="talep_id" id="rejectId">
                            <input type="hidden" name="action" value="reject">
                            <textarea name="aciklama" class="form-control" rows="3" placeholder="Red nedeni..." required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-danger">Reddet</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Edit Modal -->
            <div class="modal fade" id="editHarcamaModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="talep_id" id="edit_talep_id">
                        <div class="modal-header">
                            <h5 class="modal-title">Harcama Talebini Düzenle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Başlık</label>
                                <input type="text" name="baslik" id="edit_baslik" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tutar (€)</label>
                                <input type="number" name="tutar" id="edit_tutar" step="0.01" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Durum</label>
                                <select name="durum" id="edit_durum" class="form-select">
                                    <option value="beklemede">Beklemede</option>
                                    <option value="onaylandi">Onaylandı</option>
                                    <option value="reddedildi">Reddedildi</option>
                                    <option value="odenmistir">Ödenmişti</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Açıklama</label>
                                <textarea name="aciklama" id="edit_aciklama_text" class="form-control" rows="3"></textarea>
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
                            const id = button.getAttribute('data-id');
                            rejectModal.querySelector('#rejectId').value = id;
                        });
                    }

                    // Bulk operations for harcama
                    const selectAll = document.getElementById('selectAllHarcama');
                    const rowCheckboxes = document.querySelectorAll('.row-checkbox-harcama');
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
                        const checked = document.querySelectorAll('.row-checkbox-harcama:checked');
                        if (bulkDeleteBtn) {
                            bulkDeleteBtn.style.display = checked.length > 0 ? 'block' : 'none';
                        }
                    }
                });

                function bulkDeleteHarcama() {
                    const checked = document.querySelectorAll('.row-checkbox-harcama:checked');
                    if (checked.length === 0) {
                        alert('Lütfen en az bir kayıt seçin.');
                        return;
                    }
                    
                    if (!confirm(checked.length + ' harcama talebini silmek istediğinizden emin misiniz?')) {
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

                function editHarcama(req) {
                    document.getElementById('edit_talep_id').value = req.talep_id;
                    document.getElementById('edit_baslik').value = req.baslik;
                    document.getElementById('edit_tutar').value = req.tutar;
                    document.getElementById('edit_durum').value = req.durum;
                    
                    // Extract description from meta
                    const aciklama = req.aciklama || '';
                    const marker = '---META---';
                    let desc = aciklama;
                    if (aciklama.includes(marker)) {
                        const pos = aciklama.lastIndexOf(marker);
                        desc = aciklama.substring(0, pos).trim();
                    }
                    document.getElementById('edit_aciklama_text').value = desc;
                    
                    const modal = new bootstrap.Modal(document.getElementById('editHarcamaModal'));
                    modal.show();
                }
            </script>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

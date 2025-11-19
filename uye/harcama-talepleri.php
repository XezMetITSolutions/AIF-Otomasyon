<?php
/**
 * Üye - Harcama Taleplerim
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

$pageTitle = 'Harcama Taleplerim';
$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$errors = [];
$messages = [];
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : null;
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

$formData = [
    'baslik' => trim($_POST['baslik'] ?? ''),
    'tutar' => $_POST['tutar'] ?? '',
    'aciklama' => trim($_POST['aciklama'] ?? ''),
    'kategori' => $_POST['kategori'] ?? 'genel',
    'seyahat_cikis' => trim($_POST['seyahat_cikis'] ?? ''),
    'seyahat_varis' => trim($_POST['seyahat_varis'] ?? ''),
    'seyahat_gidis' => $_POST['seyahat_gidis'] ?? '',
    'seyahat_donus' => $_POST['seyahat_donus'] ?? '',
    'otel_gerekli' => isset($_POST['otel_gerekli'])
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $errors[] = 'Oturum doğrulaması başarısız oldu. Lütfen sayfayı yenileyin.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'yeni_harcama') {
            if ($formData['baslik'] === '') {
                $errors[] = 'Talep başlığı zorunludur.';
            }
            if (!is_numeric($formData['tutar']) || (float)$formData['tutar'] <= 0) {
                $errors[] = 'Geçerli bir tutar giriniz.';
            }
            if (!array_key_exists($formData['kategori'], $kategoriListesi)) {
                $errors[] = 'Geçersiz harcama kategorisi seçildi.';
            }
            if ($formData['kategori'] === 'seyahat') {
                if ($formData['seyahat_cikis'] === '' || $formData['seyahat_varis'] === '') {
                    $errors[] = 'Seyahat başlangıç ve varış noktaları zorunludur.';
                }
                if (!$formData['seyahat_gidis'] || !$formData['seyahat_donus']) {
                    $errors[] = 'Gidiş ve dönüş tarihleri zorunludur.';
                } else {
                    $gidis = DateTime::createFromFormat('Y-m-d', $formData['seyahat_gidis']);
                    $donus = DateTime::createFromFormat('Y-m-d', $formData['seyahat_donus']);
                    if (!$gidis || !$donus) {
                        $errors[] = 'Geçerli seyahat tarihleri giriniz.';
                    } elseif ($donus < $gidis) {
                        $errors[] = 'Dönüş tarihi gidiş tarihinden önce olamaz.';
                    }
                }
            }
            
            if (empty($errors)) {
                $meta = [
                    'kategori' => $formData['kategori']
                ];
                if ($formData['kategori'] === 'seyahat') {
                    $meta['seyahat'] = [
                        'cikis' => $formData['seyahat_cikis'],
                        'varis' => $formData['seyahat_varis'],
                        'gidis' => $formData['seyahat_gidis'],
                        'donus' => $formData['seyahat_donus'],
                        'otel' => $formData['otel_gerekli']
                    ];
                }
                $aciklamaFinal = buildHarcamaAciklamaMetni($formData['aciklama'], $meta);
                
                $db->query("
                    INSERT INTO harcama_talepleri (kullanici_id, byk_id, baslik, aciklama, tutar, durum)
                    VALUES (?, ?, ?, ?, ?, 'beklemede')
                ", [
                    $user['id'],
                    $user['byk_id'],
                    $formData['baslik'],
                    $aciklamaFinal,
                    number_format((float)$formData['tutar'], 2, '.', '')
                ]);
                
                $messages[] = 'Harcama talebiniz başarıyla oluşturuldu.';
                $formData = [
                    'baslik' => '',
                    'tutar' => '',
                    'aciklama' => '',
                    'kategori' => 'genel',
                    'seyahat_cikis' => '',
                    'seyahat_varis' => '',
                    'seyahat_gidis' => '',
                    'seyahat_donus' => '',
                    'otel_gerekli' => false
                ];
            }
        }
    }
}

$talepler = $db->fetchAll("
    SELECT *
    FROM harcama_talepleri
    WHERE kullanici_id = ?
    ORDER BY olusturma_tarihi DESC
", [$user['id']]);

$seciliTalep = null;
if ($selectedId) {
    $seciliTalep = $db->fetch("
        SELECT *
        FROM harcama_talepleri
        WHERE talep_id = ? AND kullanici_id = ?
    ", [$selectedId, $user['id']]);
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
                    <i class="fas fa-wallet me-2"></i>Harcama Taleplerim
                </h1>
                <small class="text-muted">Gider taleplerini oluşturun ve süreci takip edin.</small>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <?php foreach ($messages as $message): ?>
                    <div><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
<div class="row g-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Yeni Harcama Talebi</strong>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="yeni_harcama">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Talep Başlığı</label>
                                <input type="text" name="baslik" class="form-control" required placeholder="Örn. Eğitim Malzemesi Alımı" value="<?php echo htmlspecialchars($formData['baslik']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harcama Kategorisi</label>
                                <select name="kategori" id="kategoriSelect" class="form-select" required>
                                    <?php foreach ($kategoriListesi as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $formData['kategori'] === $key ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    Seyahat seçtiğinizde güzergâh, tarih ve otel seçenekleri açılır.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tutar (EUR)</label>
                                <input type="number" name="tutar" step="0.01" min="0" class="form-control" required value="<?php echo htmlspecialchars($formData['tutar']); ?>">
                            </div>
                            <div class="travel-fields border rounded p-3 mb-3 <?php echo $formData['kategori'] === 'seyahat' ? '' : 'd-none'; ?>" id="travelFields">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Seyahat Güzergâhı</label>
                                        <div class="input-group">
                                            <input type="text" name="seyahat_cikis" class="form-control" placeholder="Nereden" value="<?php echo htmlspecialchars($formData['seyahat_cikis']); ?>">
                                            <span class="input-group-text"><i class="fas fa-arrow-right"></i></span>
                                            <input type="text" name="seyahat_varis" class="form-control" placeholder="Nereye" value="<?php echo htmlspecialchars($formData['seyahat_varis']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Gidiş Tarihi</label>
                                        <input type="date" name="seyahat_gidis" class="form-control" value="<?php echo htmlspecialchars($formData['seyahat_gidis']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Dönüş Tarihi</label>
                                        <input type="date" name="seyahat_donus" class="form-control" value="<?php echo htmlspecialchars($formData['seyahat_donus']); ?>">
                                    </div>
                                    <div class="col-12 form-check">
                                        <input class="form-check-input" type="checkbox" name="otel_gerekli" id="otelGerekli" <?php echo $formData['otel_gerekli'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="otelGerekli">
                                            Otel / konaklama desteği gerekiyor
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="4" placeholder="Detaylı açıklama (opsiyonel)"><?php echo htmlspecialchars($formData['aciklama']); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i>Talep Gönder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <?php if ($seciliTalep): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong><?php echo htmlspecialchars($seciliTalep['baslik']); ?></strong>
                            <span class="badge bg-secondary"><?php echo date('d.m.Y H:i', strtotime($seciliTalep['olusturma_tarihi'])); ?></span>
                        </div>
                        <div class="card-body">
                            <?php [$detayAciklama, $detayMeta] = splitHarcamaAciklamaVeMeta($seciliTalep['aciklama']); ?>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Kategori</small>
                                    <?php $seciliKategori = $kategoriListesi[$detayMeta['kategori'] ?? 'genel'] ?? 'Genel'; ?>
                                    <span class="badge bg-<?php echo ($detayMeta['kategori'] ?? 'genel') === 'seyahat' ? 'info' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($seciliKategori); ?>
                                    </span>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Tutar</small>
                                    <strong><?php echo number_format($seciliTalep['tutar'], 2, ',', '.'); ?> €</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Durum</small>
                                    <?php $badgeColor = $durumBadgeMap[$seciliTalep['durum']] ?? 'secondary'; ?>
                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                        <?php echo ucfirst($seciliTalep['durum']); ?>
                                    </span>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Onay Açıklaması</small>
                                    <strong><?php echo htmlspecialchars($seciliTalep['onay_aciklama'] ?? '-'); ?></strong>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Açıklama</small>
                                    <p class="mb-0"><?php echo $detayAciklama !== '' ? nl2br(htmlspecialchars($detayAciklama)) : '-'; ?></p>
                                </div>
                                <?php if (($detayMeta['kategori'] ?? '') === 'seyahat'): ?>
                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded">
                                            <div class="fw-semibold mb-2"><i class="fas fa-plane me-2"></i>Seyahat Detayları</div>
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <small class="text-muted d-block">Güzergâh</small>
                                                    <strong><?php echo htmlspecialchars($detayMeta['seyahat']['cikis'] ?? '-'); ?> → <?php echo htmlspecialchars($detayMeta['seyahat']['varis'] ?? '-'); ?></strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">Gidiş</small>
                                                    <strong><?php echo isset($detayMeta['seyahat']['gidis']) ? date('d.m.Y', strtotime($detayMeta['seyahat']['gidis'])) : '-'; ?></strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">Dönüş</small>
                                                    <strong><?php echo isset($detayMeta['seyahat']['donus']) ? date('d.m.Y', strtotime($detayMeta['seyahat']['donus'])) : '-'; ?></strong>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted d-block">Otel Gereksinimi</small>
                                                    <span class="badge bg-<?php echo !empty($detayMeta['seyahat']['otel']) ? 'primary' : 'secondary'; ?>">
                                                        <?php echo !empty($detayMeta['seyahat']['otel']) ? 'Evet' : 'Hayır'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($selectedId): ?>
                    <div class="alert alert-warning">Talep bulunamadı veya yetkiniz yok.</div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Toplam: <strong><?php echo count($talepler); ?></strong> talep</span>
                        <?php if ($selectedId): ?>
                            <a href="/uye/harcama-talepleri.php" class="btn btn-sm btn-outline-secondary">Seçimi Temizle</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($talepler)): ?>
                            <p class="text-center text-muted mb-0">Henüz harcama talebi oluşturmadınız.</p>
                        <?php else: ?>
                    <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Başlık</th>
                                            <th>Kategori</th>
                                            <th>Tutar</th>
                                            <th>Durum</th>
                                            <th>Oluşturulma</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($talepler as $talep): ?>
                                            <?php
                                                $badgeColor = $durumBadgeMap[$talep['durum']] ?? 'secondary';
                                                $rowSelected = $selectedId === (int) $talep['talep_id'] ? 'table-primary' : '';
                                                [$rowAciklama, $rowMeta] = splitHarcamaAciklamaVeMeta($talep['aciklama']);
                                                $kategoriTag = $kategoriListesi[$rowMeta['kategori'] ?? 'genel'] ?? 'Genel';
                                            ?>
                                            <tr class="<?php echo $rowSelected; ?>">
                                                <td><?php echo htmlspecialchars($talep['baslik']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($rowMeta['kategori'] ?? 'genel') === 'seyahat' ? 'info' : 'secondary'; ?>">
                                                        <?php echo htmlspecialchars($kategoriTag); ?>
                                                    </span>
                                                    <?php if (($rowMeta['kategori'] ?? '') === 'seyahat' && !empty($rowMeta['seyahat'])): ?>
                                                        <div class="small text-muted">
                                                            <?php echo htmlspecialchars($rowMeta['seyahat']['cikis'] ?? '-'); ?> → <?php echo htmlspecialchars($rowMeta['seyahat']['varis'] ?? '-'); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo number_format($talep['tutar'], 2, ',', '.'); ?> €</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                                        <?php echo ucfirst($talep['durum']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($talep['olusturma_tarihi'])); ?></td>
                                                <td class="text-end">
                                                    <a href="/uye/harcama-talepleri.php?id=<?php echo $talep['talep_id']; ?>" class="btn btn-sm btn-outline-primary">
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
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kategoriSelect = document.getElementById('kategoriSelect');
    const travelFields = document.getElementById('travelFields');
    if (!kategoriSelect || !travelFields) return;
    
    const toggleTravel = () => {
        travelFields.classList.toggle('d-none', kategoriSelect.value !== 'seyahat');
    };
    
    kategoriSelect.addEventListener('change', toggleTravel);
    toggleTravel();
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>



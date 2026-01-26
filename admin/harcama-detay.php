<?php
/**
 * Ana Yönetici - Harcama Talebi Detayı ve Onay İşlemleri
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Sadece admin değil, muhasebe yetkisi olanlar da girebilsin diye 
// normalde Middleware::requireSuperAdmin() kullanılır ama 
// burada "baskan" rolü de girebilmeli. Şimdilik SuperAdmin bırakıyorum, 
// ama mantıken ilgili muhasebe başkanı da erişebilmeli.
// Bu örnekte auth kontrolü manual yapılacak.
$auth = new Auth();
$user = $auth->getUser();

if (!$user) {
    header('Location: /login.php');
    exit;
}

$db = Database::getInstance();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    header('Location: /admin/harcama-talepleri.php');
    exit;
}

// Talebi çek
$talep = $db->fetch("
    SELECT ht.*, 
           CONCAT(k.ad, ' ', k.soyad) as kullanici_adi, 
           k.email,
           b.byk_adi, b.byk_kodu,
           b.muhasebe_baskani_id as birim_muhasebe_id
    FROM harcama_talepleri ht
    INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id
    INNER JOIN byk b ON ht.byk_id = b.byk_id
    WHERE ht.talep_id = ?
", [$id]);

if (!$talep) {
    die('Talep bulunamadı.');
}

// AT Muhasebe Başkanını bul
$atBirim = $db->fetch("SELECT muhasebe_baskani_id FROM byk WHERE byk_kodu = 'AT' LIMIT 1");
$atMuhasebeId = $atBirim ? $atBirim['muhasebe_baskani_id'] : 0;

// Yetki Kontrolü
$isSuperAdmin = $user['role'] === 'super_admin'; 
$isBirimMuhasebe = ($user['kullanici_id'] == $talep['birim_muhasebe_id']);
$isAtMuhasebe = ($user['kullanici_id'] == $atMuhasebeId);

// İşlem
$message = '';
$error = '';

require_once __DIR__ . '/../classes/Mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'birim_onay' && ($isBirimMuhasebe || $isSuperAdmin)) {
        // Birim onayı ver -> AT onayına düşür
        // Eğer birim zaten AT ise, direkt tam onay sayılır mı? 
        // Logic: AT birimi için birim onayı = AT onayı olabilir, ama 
        // yapıya göre adım adım yapalım. Eğer AT ise next stage 3 (bitti) olabilir.

        if ($talep['byk_kodu'] === 'AT') {
            // Eğer AT birimi ise, direkt tam onay (Aşama 3)
            $nextStage = 3;
            $newStatus = 'onaylandi';
            $message = 'AT Birim onayı verildi (Talebiniz AT biriminden olduğu için süreç tamamlandı).';

            $db->query("
                UPDATE harcama_talepleri 
                SET onay_asamasi = ?, 
                    birim_onay_tarihi = NOW(), 
                    birim_onaylayan_id = ?,
                    durum = ?,
                    guncelleme_tarihi = NOW()
                WHERE talep_id = ?
            ", [$nextStage, $user['kullanici_id'], $newStatus, $id]);

            $talep['durum'] = 'onaylandi';
        } else {
            // Normal birim -> AT onayı bekle (Aşama 2)
            $nextStage = 2;
            $newStatus = 'beklemede';
            $message = 'Birim onayı verildi. Talep AT Muhasebe onayına gönderildi.';

            $db->query("
                UPDATE harcama_talepleri 
                SET onay_asamasi = ?, 
                    birim_onay_tarihi = NOW(), 
                    birim_onaylayan_id = ?,
                    durum = ?
                WHERE talep_id = ?
            ", [$nextStage, $user['kullanici_id'], $newStatus, $id]);

            // AT Muhasebe Başkanına Bildirim Gönder
            $atAdmin = $db->fetch("SELECT email FROM kullanicilar WHERE kullanici_id = ?", [$atMuhasebeId]);
            if ($atAdmin) {
                Mail::sendWithTemplate($atAdmin['email'], 'talep_yeni', [
                    'ad_soyad' => 'AT Muhasebe Yetkilisi',
                    'talep_turu' => 'Harcama Talebi (AT Onayı Bekliyor)',
                    'detay' => "{$talep['kullanici_adi']} tarafından oluşturulan " . number_format($talep['tutar'], 2, ',', '.') . " TL tutarındaki harcama talebi birim onayından geçti."
                ]);
            }
        }

        // Refresh vars
        $talep['onay_asamasi'] = $nextStage;
        $talep['birim_onay_tarihi'] = date('Y-m-d H:i:s');

    } elseif ($action === 'at_onay' && ($isAtMuhasebe || $isSuperAdmin)) {
        // AT Onayı (Final)
        $db->query("
            UPDATE harcama_talepleri 
            SET onay_asamasi = 3, 
                durum = 'onaylandi',
                guncelleme_tarihi = NOW()
            WHERE talep_id = ?
        ", [$id]);

        $message = 'Talep tamamen onaylandı.';
        $talep['onay_asamasi'] = 3;
        $talep['durum'] = 'onaylandi';

        // Kullanıcıya Bildirim Gönder
        Mail::sendWithTemplate($talep['email'], 'talep_sonuc', [
            'ad_soyad' => $talep['kullanici_adi'],
            'talep_turu' => 'Harcama Talebi',
            'durum' => 'Onaylandı',
            'aciklama' => 'Talebiniz AT Muhasebe tarafından onaylanmıştır.'
        ]);

    } elseif ($action === 'red' && ($isBirimMuhasebe || $isAtMuhasebe || $isSuperAdmin)) {
        // Reddet
        $db->query("
            UPDATE harcama_talepleri 
            SET durum = 'reddedildi',
                guncelleme_tarihi = NOW()
            WHERE talep_id = ?
        ", [$id]);

        $message = 'Talep reddedildi.';
        $talep['durum'] = 'reddedildi';

        // Kullanıcıya Bildirim Gönder
        Mail::sendWithTemplate($talep['email'], 'talep_sonuc', [
            'ad_soyad' => $talep['kullanici_adi'],
            'talep_turu' => 'Harcama Talebi',
            'durum' => 'Reddedildi',
            'aciklama' => 'Talebiniz yönetici tarafından reddedilmiştir.'
        ]);
    } else {
        $error = 'Bu işlemi yapmaya yetkiniz yok.';
    }
}

$pageTitle = 'Talep Detayı #' . $id;
include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-file-invoice-dollar me-2"></i>Harcama Talebi Detayı
            </h1>
            <a href="/admin/harcama-talepleri.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Geri Dön
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Talep Bilgileri</h5>
                        <span class="badge bg-<?php
                        echo $talep['durum'] == 'onaylandi' ? 'success' :
                            ($talep['durum'] == 'reddedildi' ? 'danger' : 'warning');
                        ?> fs-6">
                            <?php echo strtoupper($talep['durum']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Başlık</div>
                            <div class="col-md-8 fw-bold"><?php echo htmlspecialchars($talep['baslik']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Tutar</div>
                            <div class="col-md-8 fs-5 text-success">
                                <?php echo number_format($talep['tutar'], 2, ',', '.'); ?> TL</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Açıklama</div>
                            <div class="col-md-8"><?php echo nl2br(htmlspecialchars($talep['aciklama'] ?? '-')); ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Tarih</div>
                            <div class="col-md-8">
                                <?php echo date('d.m.Y H:i', strtotime($talep['olusturma_tarihi'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Onay Süreci Durumu -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Onay Süreci</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $currentStage = (int) ($talep['onay_asamasi'] ?? 1);
                        // Eğer eski kayıt ise NULL gelir, 1 kabul et
                        if ($currentStage == 0)
                            $currentStage = 1;
                        ?>

                        <div class="position-relative m-4">
                            <div class="progress" style="height: 1px;">
                                <div class="progress-bar" role="progressbar"
                                    style="width: <?php echo ($currentStage - 1) * 50; ?>%;" aria-valuenow="50"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="position-absolute top-0 start-0 translate-middle btn btn-sm btn-<?php echo ($currentStage > 1 || $talep['durum'] == 'onaylandi') ? 'success' : 'primary'; ?> rounded-pill"
                                style="width: 2rem; height:2rem;">1</div>
                            <div class="position-absolute top-0 start-50 translate-middle btn btn-sm btn-<?php echo ($currentStage > 2 || $talep['durum'] == 'onaylandi') ? 'success' : ($currentStage == 2 ? 'primary' : 'secondary'); ?> rounded-pill"
                                style="width: 2rem; height:2rem;">2</div>
                            <div class="position-absolute top-0 start-100 translate-middle btn btn-sm btn-<?php echo ($currentStage == 3) ? 'success' : 'secondary'; ?> rounded-pill"
                                style="width: 2rem; height:2rem;">3</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="text-center">
                                <strong>Birim Onayı</strong><br>
                                <small class="text-muted"><?php echo $talep['byk_adi']; ?> Muhasebe</small>
                                <?php if ($talep['birim_onay_tarihi']): ?>
                                    <br><span class="text-success small"><i class="fas fa-check"></i> Onaylandı</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-center">
                                <strong>AT Onayı</strong><br>
                                <small class="text-muted">Ana Teşkilat Muhasebe</small>
                            </div>
                            <div class="text-center">
                                <strong>Tamamlandı</strong><br>
                                <small class="text-muted">Sonuç</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Talep Sahibi</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white me-3"
                                style="width: 50px; height: 50px; font-size: 1.2rem;">
                                <?php echo strtoupper(substr($talep['kullanici_adi'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($talep['kullanici_adi']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($talep['byk_adi']); ?>
                                    (<?php echo $talep['byk_kodu']; ?>)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aksiyonlar -->
                <?php if ($talep['durum'] == 'beklemede'): ?>
                    <div class="card mt-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">İşlemler</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                Şu anki Aşama:
                                <strong><?php echo ($currentStage == 1) ? 'Birim Onayı Bekleniyor' : 'AT Onayı Bekleniyor'; ?></strong>
                            </p>

                            <form method="POST">
                                <?php if ($currentStage == 1 && ($isBirimMuhasebe || $isSuperAdmin)): ?>
                                    <div class="d-grid gap-2 mb-2">
                                        <button type="submit" name="action" value="birim_onay" class="btn btn-success">
                                            <i class="fas fa-check me-2"></i>Birim Onayı Ver
                                        </button>
                                        <div class="text-muted small text-center">
                                            (Sonraki adım: AT Onayı)
                                        </div>
                                    </div>
                                <?php elseif ($currentStage == 2 && ($isAtMuhasebe || $isSuperAdmin)): ?>
                                    <div class="d-grid gap-2 mb-2">
                                        <button type="submit" name="action" value="at_onay" class="btn btn-success">
                                            <i class="fas fa-check-double me-2"></i>Tam Onay Ver
                                        </button>
                                        <div class="text-muted small text-center">
                                            (Son işlem)
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning small">
                                        <?php if ($currentStage == 1): ?>
                                            Bu talebi sadece <strong><?php echo htmlspecialchars($talep['byk_adi']); ?></strong>
                                            muhasebe başkanı onaylayabilir.
                                        <?php elseif ($currentStage == 2): ?>
                                            Bu talebi sadece <strong>AT</strong> muhasebe başkanı onaylayabilir.
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($isBirimMuhasebe || $isAtMuhasebe || $isSuperAdmin): ?>
                                    <div class="d-grid mt-3">
                                        <button type="submit" name="action" value="red" class="btn btn-outline-danger">
                                            <i class="fas fa-times me-2"></i>Reddet
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
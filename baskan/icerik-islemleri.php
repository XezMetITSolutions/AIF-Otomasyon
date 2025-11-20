<?php
/**
 * Başkan - İçerik İşlemleri
 * Üyelerden veya dış kaynaklardan gelen içerik taleplerini yönetir.
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireBaskan();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$appConfig = require __DIR__ . '/../config/app.php';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();
$pageTitle = 'İçerik İşlemleri';

$message = null;
$messageType = 'success';

$durumFilter = $_GET['durum'] ?? '';
$oncelikFilter = $_GET['oncelik'] ?? '';
$kanalFilter = $_GET['kanal'] ?? '';
$arama = trim($_GET['q'] ?? '');

$allowedDurumlar = ['beklemede', 'calisiliyor', 'yanitlandi', 'tamamlandi', 'iptal'];
$allowedOncelikler = ['acil', 'yuksek', 'normal', 'dusuk'];
$allowedKanallar = ['portal', 'mail', 'telefon', 'whatsapp', 'sosyal', 'diger'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyin.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $baslik = trim($_POST['baslik'] ?? '');
            $talepTuru = trim($_POST['talep_turu'] ?? '');
            $aciklama = trim($_POST['aciklama'] ?? '');
            $oncelik = $_POST['oncelik'] ?? 'normal';
            $kanal = $_POST['kanal'] ?? 'portal';
            $hedefTarih = $_POST['hedef_tarih'] ?: null;
            $etiketler = trim($_POST['etiketler'] ?? '');
            $talepSahibiManuel = trim($_POST['talep_sahibi'] ?? '');
            $kullaniciId = (int)($_POST['kullanici_id'] ?? 0);
            if ($kullaniciId <= 0) {
                $kullaniciId = null;
            }

            if (!$baslik || !$talepTuru) {
                $message = 'Başlık ve talep türü zorunludur.';
                $messageType = 'danger';
            } elseif (!in_array($oncelik, $allowedOncelikler, true) || !in_array($kanal, $allowedKanallar, true)) {
                $message = 'Geçersiz öncelik veya kanal seçimi yapıldı.';
                $messageType = 'danger';
            } else {
                $talepSahibi = $talepSahibiManuel ?: null;
                if ($kullaniciId) {
                    $uyebilgisi = $db->fetch("
                        SELECT CONCAT(ad, ' ', soyad) AS adsoyad 
                        FROM kullanicilar 
                        WHERE kullanici_id = ? AND byk_id = ?
                    ", [$kullaniciId, $user['byk_id']]);
                    if ($uyebilgisi) {
                        $talepSahibi = $uyebilgisi['adsoyad'];
                    } else {
                        $kullaniciId = null;
                    }
                }

                $db->query("
                    INSERT INTO icerik_talepleri 
                    (byk_id, kullanici_id, talep_sahibi, kanal, talep_turu, baslik, aciklama, oncelik, etiketler, hedef_tarih)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $user['byk_id'],
                    $kullaniciId,
                    $talepSahibi,
                    $kanal,
                    $talepTuru,
                    $baslik,
                    $aciklama ?: null,
                    $oncelik,
                    $etiketler ?: null,
                    $hedefTarih ?: null
                ]);

                $message = 'Talep başarıyla kaydedildi.';
            }
        } elseif ($action === 'respond') {
            $talepId = (int)($_POST['talep_id'] ?? 0);
            $yanitBaslik = trim($_POST['yanit_baslik'] ?? '');
            $yanitMetni = trim($_POST['yanit_metni'] ?? '');
            $yeniDurum = $_POST['durum'] ?? 'yanitlandi';

            if (!$talepId || !$yanitMetni) {
                $message = 'Yanıt içeriği doldurulmalıdır.';
                $messageType = 'danger';
            } elseif (!in_array($yeniDurum, $allowedDurumlar, true)) {
                $message = 'Geçersiz durum seçildi.';
                $messageType = 'danger';
            } else {
                $talep = $db->fetch("
                    SELECT talep_id 
                    FROM icerik_talepleri 
                    WHERE talep_id = ? AND byk_id = ?
                ", [$talepId, $user['byk_id']]);

                if (!$talep) {
                    $message = 'Talep bulunamadı.';
                    $messageType = 'danger';
                } else {
                    $db->query("
                        UPDATE icerik_talepleri
                        SET durum = ?, 
                            yanitlanma_tarihi = NOW(),
                            yanitlayan_id = ?,
                            yanit_ozeti = ?,
                            yanit_sayisi = yanit_sayisi + 1,
                            son_aksiyon_tarihi = NOW(),
                            son_aksiyon_tipi = 'yanit'
                        WHERE talep_id = ?
                    ", [$yeniDurum, $user['id'], $yanitMetni, $talepId]);

                    $db->query("
                        INSERT INTO icerik_talep_notlari (talep_id, kullanici_id, tip, baslik, icerik)
                        VALUES (?, ?, 'yanit', ?, ?)
                    ", [$talepId, $user['id'], $yanitBaslik ?: null, $yanitMetni]);

                    $message = 'Yanıt kaydedildi.';
                }
            }
        } elseif ($action === 'status') {
            $talepId = (int)($_POST['talep_id'] ?? 0);
            $hedefDurum = $_POST['hedef_durum'] ?? '';
            $durumNotu = trim($_POST['durum_notu'] ?? '');

            if (!$talepId || !in_array($hedefDurum, $allowedDurumlar, true)) {
                $message = 'Geçersiz durum değişikliği.';
                $messageType = 'danger';
            } else {
                $talep = $db->fetch("
                    SELECT talep_id 
                    FROM icerik_talepleri 
                    WHERE talep_id = ? AND byk_id = ?
                ", [$talepId, $user['byk_id']]);

                if (!$talep) {
                    $message = 'Talep bulunamadı.';
                    $messageType = 'danger';
                } else {
                    $db->query("
                        UPDATE icerik_talepleri
                        SET durum = ?, 
                            son_aksiyon_tarihi = NOW(),
                            son_aksiyon_tipi = 'durum'
                        WHERE talep_id = ?
                    ", [$hedefDurum, $talepId]);

                    if ($durumNotu) {
                        $db->query("
                            INSERT INTO icerik_talep_notlari (talep_id, kullanici_id, tip, baslik, icerik)
                            VALUES (?, ?, 'guncelleme', ?, ?)
                        ", [$talepId, $user['id'], 'Durum güncellendi', $durumNotu]);
                    }

                    $message = 'Durum güncellendi.';
                }
            }
        } elseif ($action === 'note') {
            $talepId = (int)($_POST['talep_id'] ?? 0);
            $notBaslik = trim($_POST['not_baslik'] ?? '');
            $notMetni = trim($_POST['not_metni'] ?? '');

            if (!$talepId || !$notMetni) {
                $message = 'Not içeriği boş bırakılamaz.';
                $messageType = 'danger';
            } else {
                $talep = $db->fetch("
                    SELECT talep_id 
                    FROM icerik_talepleri 
                    WHERE talep_id = ? AND byk_id = ?
                ", [$talepId, $user['byk_id']]);

                if (!$talep) {
                    $message = 'Talep bulunamadı.';
                    $messageType = 'danger';
                } else {
                    $db->query("
                        INSERT INTO icerik_talep_notlari (talep_id, kullanici_id, tip, baslik, icerik)
                        VALUES (?, ?, 'not', ?, ?)
                    ", [$talepId, $user['id'], $notBaslik ?: null, $notMetni]);

                    $db->query("
                        UPDATE icerik_talepleri
                        SET son_aksiyon_tarihi = NOW(),
                            son_aksiyon_tipi = 'not'
                        WHERE talep_id = ?
                    ", [$talepId]);

                    $message = 'Not kaydedildi.';
                }
            }
        }
    }
}

$uyeList = $db->fetchAll("
    SELECT k.kullanici_id, CONCAT(k.ad, ' ', k.soyad) AS adsoyad
    FROM kullanicilar k
    INNER JOIN roller r ON k.rol_id = r.rol_id
    WHERE k.byk_id = ? AND r.rol_adi = 'uye' AND k.aktif = 1
    ORDER BY k.ad ASC, k.soyad ASC
    LIMIT 200
", [$user['byk_id']]);

$stats = $db->fetch("
    SELECT 
        SUM(CASE WHEN durum = 'beklemede' THEN 1 ELSE 0 END) AS bekleyen,
        SUM(CASE WHEN oncelik = 'acil' AND durum <> 'tamamlandi' THEN 1 ELSE 0 END) AS acil,
        SUM(CASE WHEN durum IN ('yanitlandi','calisiliyor') THEN 1 ELSE 0 END) AS yanitlanan,
        SUM(CASE WHEN durum IN ('tamamlandi','iptal') THEN 1 ELSE 0 END) AS kapanan
    FROM icerik_talepleri
    WHERE byk_id = ?
", [$user['byk_id']]) ?: [];

$filters = ['it.byk_id = ?'];
$params = [$user['byk_id']];

if ($durumFilter && in_array($durumFilter, $allowedDurumlar, true)) {
    $filters[] = 'it.durum = ?';
    $params[] = $durumFilter;
}

if ($oncelikFilter && in_array($oncelikFilter, $allowedOncelikler, true)) {
    $filters[] = 'it.oncelik = ?';
    $params[] = $oncelikFilter;
}

if ($kanalFilter && in_array($kanalFilter, $allowedKanallar, true)) {
    $filters[] = 'it.kanal = ?';
    $params[] = $kanalFilter;
}

if ($arama) {
    $filters[] = "(it.baslik LIKE ? OR it.aciklama LIKE ? OR it.talep_turu LIKE ? OR it.talep_sahibi LIKE ?)";
    $like = '%' . $arama . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereClause = 'WHERE ' . implode(' AND ', $filters);

$icerikTalepleri = $db->fetchAll("
    SELECT it.*, 
           CONCAT(u.ad, ' ', u.soyad) AS uye_adi,
           CONCAT(y.ad, ' ', y.soyad) AS yanitlayan_adi
    FROM icerik_talepleri it
    LEFT JOIN kullanicilar u ON it.kullanici_id = u.kullanici_id
    LEFT JOIN kullanicilar y ON it.yanitlayan_id = y.kullanici_id
    $whereClause
    ORDER BY 
        CASE it.durum WHEN 'beklemede' THEN 0 WHEN 'calisiliyor' THEN 1 WHEN 'yanitlandi' THEN 2 ELSE 3 END,
        CASE it.oncelik WHEN 'acil' THEN 0 WHEN 'yuksek' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END,
        it.olusturma_tarihi DESC
    LIMIT 150
", $params);

$talepNotlari = [];
if (!empty($icerikTalepleri)) {
    $talepIds = array_column($icerikTalepleri, 'talep_id');
    $placeholders = implode(',', array_fill(0, count($talepIds), '?'));
    $notKayitlari = $db->fetchAll("
        SELECT n.*, CONCAT(k.ad, ' ', k.soyad) AS not_sahibi
        FROM icerik_talep_notlari n
        LEFT JOIN kullanicilar k ON n.kullanici_id = k.kullanici_id
        WHERE n.talep_id IN ($placeholders)
        ORDER BY n.olusturma_tarihi DESC
    ", $talepIds);

    foreach ($notKayitlari as $not) {
        $talepNotlari[$not['talep_id']][] = $not;
    }
}

$durumEtiketleri = [
    'beklemede' => ['label' => 'Beklemede', 'badge' => 'warning'],
    'calisiliyor' => ['label' => 'Çalışılıyor', 'badge' => 'info'],
    'yanitlandi' => ['label' => 'Yanıtlandı', 'badge' => 'primary'],
    'tamamlandi' => ['label' => 'Tamamlandı', 'badge' => 'success'],
    'iptal' => ['label' => 'İptal', 'badge' => 'secondary'],
];

$oncelikEtiketleri = [
    'acil' => ['label' => 'Acil', 'badge' => 'danger'],
    'yuksek' => ['label' => 'Yüksek', 'badge' => 'warning'],
    'normal' => ['label' => 'Normal', 'badge' => 'secondary'],
    'dusuk' => ['label' => 'Düşük', 'badge' => 'light'],
];

$kanalEtiketleri = [
    'portal' => ['label' => 'Portal', 'icon' => 'fa-globe'],
    'mail' => ['label' => 'E-posta', 'icon' => 'fa-envelope'],
    'telefon' => ['label' => 'Telefon', 'icon' => 'fa-phone'],
    'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'fa-mobile-screen-button'],
    'sosyal' => ['label' => 'Sosyal Medya', 'icon' => 'fa-hashtag'],
    'diger' => ['label' => 'Diğer', 'icon' => 'fa-layer-group'],
];

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-pen-ruler me-2"></i>İçerik İşlemleri
                </h1>
                <p class="text-muted mb-0">Üyelerden gelen içerik taleplerini kaydedin, takip edin ve yanıtlayın.</p>
            </div>
            <div class="text-muted small">
                Son güncelleme: <?php echo date('d.m.Y H:i'); ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-label">Bekleyen Talepler</div>
                    <div class="stat-value"><?php echo (int)($stats['bekleyen'] ?? 0); ?></div>
                    <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card warning">
                    <div class="stat-label">Acil Talepler</div>
                    <div class="stat-value"><?php echo (int)($stats['acil'] ?? 0); ?></div>
                    <div class="stat-icon"><i class="fas fa-bolt"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card info">
                    <div class="stat-label">Aktif Çalışmalar</div>
                    <div class="stat-value"><?php echo (int)($stats['yanitlanan'] ?? 0); ?></div>
                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card success">
                    <div class="stat-label">Kapanan Talepler</div>
                    <div class="stat-value"><?php echo (int)($stats['kapanan'] ?? 0); ?></div>
                    <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-3 align-items-end" method="get">
                    <div class="col-md-3">
                        <label class="form-label">Durum</label>
                        <select name="durum" class="form-select">
                            <option value="">Hepsi</option>
                            <?php foreach ($durumEtiketleri as $key => $info): ?>
                                <option value="<?php echo $key; ?>" <?php echo $durumFilter === $key ? 'selected' : ''; ?>>
                                    <?php echo $info['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Öncelik</label>
                        <select name="oncelik" class="form-select">
                            <option value="">Hepsi</option>
                            <?php foreach ($oncelikEtiketleri as $key => $info): ?>
                                <option value="<?php echo $key; ?>" <?php echo $oncelikFilter === $key ? 'selected' : ''; ?>>
                                    <?php echo $info['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kanal</label>
                        <select name="kanal" class="form-select">
                            <option value="">Hepsi</option>
                            <?php foreach ($kanalEtiketleri as $key => $info): ?>
                                <option value="<?php echo $key; ?>" <?php echo $kanalFilter === $key ? 'selected' : ''; ?>>
                                    <?php echo $info['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kelimelerde Ara</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="Başlık, talep türü vb." value="<?php echo htmlspecialchars($arama); ?>">
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="/baskan/icerik-islemleri.php" class="btn btn-outline-secondary">
                            Temizle
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Filtrele
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list-check me-2"></i>Talep Kuyruğu</span>
                        <span class="badge bg-light text-dark"><?php echo count($icerikTalepleri); ?> kayıt</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($icerikTalepleri)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                                <p class="mb-0">Henüz kayıtlı içerik talebi bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($icerikTalepleri as $talep): 
                                    $etiketler = array_filter(array_map('trim', explode(',', $talep['etiketler'] ?? '')));
                                    $notlar = $talepNotlari[$talep['talep_id']] ?? [];
                                    $detailId = 'talepDetay' . $talep['talep_id'];
                                    ?>
                                    <div class="list-group-item mb-3 border rounded-3 shadow-sm">
                                        <div class="d-flex justify-content-between flex-wrap gap-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-semibold fs-5"><?php echo htmlspecialchars($talep['baslik']); ?></span>
                                                    <?php if ($talep['talep_turu']): ?>
                                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($talep['talep_turu']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($talep['hedef_tarih']): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                                                            <i class="fas fa-flag me-1"></i><?php echo date('d.m.Y', strtotime($talep['hedef_tarih'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($talep['talep_sahibi'] ?: ($talep['uye_adi'] ?? 'Belirtilmedi')); ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-clock me-1"></i><?php echo date('d.m.Y H:i', strtotime($talep['olusturma_tarihi'])); ?>
                                                </div>
                                                <div class="mt-2 text-muted">
                                                    <?php echo nl2br(htmlspecialchars($talep['aciklama'] ?? '')); ?>
                                                </div>
                                                <?php if (!empty($etiketler)): ?>
                                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                                        <?php foreach ($etiketler as $tag): ?>
                                                            <span class="badge bg-light border border-primary text-primary">
                                                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($tag); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                            <span class="badge bg-<?php echo $oncelikEtiketleri[$talep['oncelik']]['badge'] ?? 'secondary'; ?>">
                                <?php echo $oncelikEtiketleri[$talep['oncelik']]['label'] ?? 'Öncelik'; ?>
                            </span>
                                                <span class="badge bg-<?php echo $durumEtiketleri[$talep['durum']]['badge'] ?? 'secondary'; ?> ms-1">
                                <?php echo $durumEtiketleri[$talep['durum']]['label'] ?? 'Durum'; ?>
                            </span>
                                                <div class="mt-2">
                                                    <?php 
                                                        $kanalKey = $talep['kanal'] ?? 'portal';
                                                        $kanal = $kanalEtiketleri[$kanalKey] ?? $kanalEtiketleri['diger'];
                                                    ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="fas <?php echo $kanal['icon']; ?> me-1"></i><?php echo $kanal['label']; ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($talep['yanitlayan_adi'])): ?>
                                                    <div class="small text-success mt-2">
                                                        <i class="fas fa-reply me-1"></i><?php echo htmlspecialchars($talep['yanitlayan_adi']); ?>
                                                        <?php if (!empty($talep['yanitlanma_tarihi'])): ?>
                                                            <span class="text-muted">· <?php echo date('d.m.Y H:i', strtotime($talep['yanitlanma_tarihi'])); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#respondModal"
                                                    data-talep="<?php echo $talep['talep_id']; ?>"
                                                    data-baslik="<?php echo htmlspecialchars($talep['baslik'], ENT_QUOTES); ?>">
                                                    <i class="fas fa-reply me-1"></i>Yanıt Yaz
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#noteModal"
                                                    data-talep="<?php echo $talep['talep_id']; ?>"
                                                    data-baslik="<?php echo htmlspecialchars($talep['baslik'], ENT_QUOTES); ?>">
                                                    <i class="fas fa-sticky-note me-1"></i>Not Ekle
                                                </button>
                                                <button class="btn btn-sm btn-outline-dark" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#<?php echo $detailId; ?>">
                                                    <i class="fas fa-angle-down me-1"></i>Detayları Gör
                                                </button>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="talep_id" value="<?php echo $talep['talep_id']; ?>">
                                                    <input type="hidden" name="hedef_durum" value="calisiliyor">
                                                    <button class="btn btn-sm btn-outline-info" type="submit">
                                                        <i class="fas fa-hourglass-half me-1"></i>Çalışılıyor
                                                    </button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="talep_id" value="<?php echo $talep['talep_id']; ?>">
                                                    <input type="hidden" name="hedef_durum" value="tamamlandi">
                                                    <button class="btn btn-sm btn-outline-success" type="submit">
                                                        <i class="fas fa-check me-1"></i>Tamamla
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="collapse mt-3" id="<?php echo $detailId; ?>">
                                            <div class="border-top pt-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong>Hareket Geçmişi</strong>
                                                    <span class="badge bg-light text-dark"><?php echo count($notlar); ?> kayıt</span>
                                                </div>
                                                <?php if (empty($notlar)): ?>
                                                    <p class="text-muted mb-0">Henüz not veya yanıt eklenmemiş.</p>
                                                <?php else: ?>
                                                    <div class="timeline">
                                                        <?php foreach (array_slice($notlar, 0, 4) as $not): ?>
                                                            <div class="timeline-item mb-3">
                                                                <div class="d-flex justify-content-between">
                                                                    <div>
                                                                        <span class="badge bg-<?php echo $not['tip'] === 'yanit' ? 'primary' : ($not['tip'] === 'guncelleme' ? 'info' : 'secondary'); ?>">
                                                                            <?php 
                                                                                if ($not['tip'] === 'yanit') {
                                                                                    echo 'Yanıt';
                                                                                } elseif ($not['tip'] === 'guncelleme') {
                                                                                    echo 'Güncelleme';
                                                                                } else {
                                                                                    echo 'Not';
                                                                                }
                                                                            ?>
                                                                        </span>
                                                                        <?php if (!empty($not['baslik'])): ?>
                                                                            <span class="fw-semibold ms-2"><?php echo htmlspecialchars($not['baslik']); ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <small class="text-muted">
                                                                        <?php echo date('d.m.Y H:i', strtotime($not['olusturma_tarihi'])); ?>
                                                                    </small>
                                                                </div>
                                                                <div class="small text-muted mt-1">
                                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($not['not_sahibi'] ?? 'Sistem'); ?>
                                                                </div>
                                                                <div class="mt-1">
                                                                    <?php echo nl2br(htmlspecialchars($not['icerik'])); ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php if (count($notlar) > 4): ?>
                                                            <div class="text-muted small fst-italic">Daha fazla kayıt için detay sayfası hazırlanacaktır.</div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-plus me-2"></i>Hızlı Talep Kaydı
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="create">
                            <div class="col-12">
                                <label class="form-label">Üye</label>
                                <select name="kullanici_id" class="form-select">
                                    <option value="">Üye seçin (opsiyonel)</option>
                                    <?php foreach ($uyeList as $uye): ?>
                                        <option value="<?php echo $uye['kullanici_id']; ?>"><?php echo htmlspecialchars($uye['adsoyad']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Talep Sahibi (Manuel)</label>
                                <input type="text" name="talep_sahibi" class="form-control" placeholder="Ad Soyad (zorunlu değil)">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="baslik" class="form-control" required placeholder="Örn: Sosyal medya duyurusu">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Talep Türü</label>
                                <input type="text" name="talep_turu" class="form-control" required placeholder="Örn: Sosyal medya">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Kanal</label>
                                <select name="kanal" class="form-select">
                                    <?php foreach ($kanalEtiketleri as $key => $info): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $info['label']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Öncelik</label>
                                <select name="oncelik" class="form-select">
                                    <?php foreach ($oncelikEtiketleri as $key => $info): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $info['label']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Hedef Tarih</label>
                                <input type="date" name="hedef_tarih" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Etiketler</label>
                                <input type="text" name="etiketler" class="form-control" placeholder="Örn: instagram,video,acil">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Açıklama</label>
                                <textarea name="aciklama" class="form-control" rows="3" placeholder="İstenen içerik hakkında kısa notlar."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-1"></i>Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2"></i>Hızlı Yanıt Şablonları
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <button class="list-group-item list-group-item-action template-btn" data-template="Merhaba, talebiniz için içerik tasarım ekibimiz çalışmaya başladı. Güncel durumu en kısa sürede paylaşacağız.">
                                <div class="fw-semibold">Çalışma Başladı</div>
                                <div class="small text-muted">Talebi aldığınızı bilgilendirin.</div>
                            </button>
                            <button class="list-group-item list-group-item-action template-btn" data-template="Merhaba, içerik taslağınız hazırlandı. Geri bildirimlerinizi paylaşırsanız son halini planlayalım.">
                                <div class="fw-semibold">Taslak Hazır</div>
                                <div class="small text-muted">Taslak teslim bilgilendirmesi.</div>
                            </button>
                            <button class="list-group-item list-group-item-action template-btn" data-template="Merhaba, içerik talebiniz tamamlandı ve ilgili kanala iletildi. Başka bir ihtiyaç olursa her zaman ulaşabilirsiniz.">
                                <div class="fw-semibold">Tamamlandı</div>
                                <div class="small text-muted">Süreç kapanış mesajı.</div>
                            </button>
                        </div>
                        <p class="text-muted small mt-3 mb-0">
                            Şablonlara tıklayarak yanıt modaline içerik taşıyabilirsiniz.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Yanıt Modal -->
<div class="modal fade" id="respondModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-reply me-2 text-primary"></i>Talebe Yanıt Yaz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="respond">
                <input type="hidden" name="talep_id" id="respondTalepId">
                <p class="text-muted small mb-3" id="respondTalepBaslik"></p>
                <div class="mb-3">
                    <label class="form-label">Yanıt Başlığı</label>
                    <input type="text" name="yanit_baslik" class="form-control" placeholder="İsteğe bağlı kısa başlık">
                </div>
                <div class="mb-3">
                    <label class="form-label">Yanıt Metni</label>
                    <textarea name="yanit_metni" class="form-control" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Durum</label>
                    <select name="durum" class="form-select">
                        <?php foreach ($durumEtiketleri as $key => $info): ?>
                            <option value="<?php echo $key; ?>" <?php echo $key === 'yanitlandi' ? 'selected' : ''; ?>>
                                <?php echo $info['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Yanıtı Gönder</button>
            </div>
        </form>
    </div>
</div>

<!-- Not Modal -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-sticky-note me-2 text-secondary"></i>Talebe Not Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="note">
                <input type="hidden" name="talep_id" id="noteTalepId">
                <p class="text-muted small mb-3" id="noteTalepBaslik"></p>
                <div class="mb-3">
                    <label class="form-label">Not Başlığı</label>
                    <input type="text" name="not_baslik" class="form-control" placeholder="İsteğe bağlı">
                </div>
                <div class="mb-3">
                    <label class="form-label">Not İçeriği</label>
                    <textarea name="not_metni" class="form-control" rows="4" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-primary">Notu Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const respondModal = document.getElementById('respondModal');
    if (respondModal) {
        respondModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const talepId = button.getAttribute('data-talep');
            const baslik = button.getAttribute('data-baslik');
            respondModal.querySelector('#respondTalepId').value = talepId;
            respondModal.querySelector('#respondTalepBaslik').textContent = baslik;
        });
    }

    const noteModal = document.getElementById('noteModal');
    if (noteModal) {
        noteModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const talepId = button.getAttribute('data-talep');
            const baslik = button.getAttribute('data-baslik');
            noteModal.querySelector('#noteTalepId').value = talepId;
            noteModal.querySelector('#noteTalepBaslik').textContent = baslik;
        });
    }

    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const template = btn.getAttribute('data-template');
            const respondModalInstance = bootstrap.Modal.getOrCreateInstance(respondModal);
            respondModal.querySelector('textarea[name="yanit_metni"]').value = template;
            respondModalInstance.show();
        });
    });
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>


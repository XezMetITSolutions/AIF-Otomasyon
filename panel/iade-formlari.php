<?php
/**
 * Harcama / İade Formları Modülü
 * 
 * - Üye: Yeni harcama talebi / gider formu oluşturur (uye_iade-formu.php)
 * - Yönetici: Gelen talepleri listeler, filtreler, onaylar/reddeder veya ödeme durumunu günceller (baskan_iade-formlari.php)
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
$hasPermissionBaskan = $auth->hasModulePermission('baskan_iade_formlari');
$hasPermissionUye = $auth->hasModulePermission('uye_iade_formu');

// Ekstra Yetki Kontrolü: AT Başkanı veya Muhasebe Sorumlusu mu?
// Ancak bu mantık aşağıda view tarafında tekrar kontrol edilecek.
// Genel giriş izni için modül izni yeterli.

if (!$hasPermissionBaskan && !$hasPermissionUye) {
    header('Location: /panel/dashboard.php');
    exit;
}

// Hangi sekme aktif?
$activeTab = $_GET['tab'] ?? ($hasPermissionUye ? 'form' : 'yonetim');

// Üye bilgileri (Form için)
$uyeDetay = $db->fetch("
    SELECT k.ad, k.soyad, b.byk_kodu, b.byk_adi
    FROM kullanicilar k
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    WHERE k.kullanici_id = ?
", [$user['id']]);

$uyeAd = $uyeDetay['ad'] ?? explode(' ', $user['name'])[0];
$uyeSoyad = $uyeDetay['soyad'] ?? (explode(' ', $user['name'])[1] ?? '');
$uyeBykKodu = $uyeDetay['byk_kodu'] ?? '';
$formBasePath = '/Hesaplama';

// Kayıtlı IBAN'ı çek
$savedIbanData = $db->fetch("SELECT ayar_degeri FROM kullanici_ayarlari WHERE kullanici_id = ? AND ayar_adi = 'saved_iban'", [$user['id']]);
$savedIban = $savedIbanData ? $savedIbanData['ayar_degeri'] : '';

$csrfTokenName = $appConfig['security']['csrf_token_name'] ?? 'csrf_token';
$csrfToken = Middleware::generateCSRF();
$message = null;
$messageType = 'success';

// YÖNETİM İŞLEMLERİ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasPermissionBaskan) {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız.';
        $messageType = 'danger';
    } else {
        $talepId = (int) ($_POST['talep_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $note = trim($_POST['aciklama'] ?? '');

        // Yetki kontrolü (Yönetici kendi BYK'sını görebilir)
        $talep = $db->fetch("
            SELECT * FROM harcama_talepleri
            WHERE talep_id = ? AND byk_id = ?
        ", [$talepId, $user['byk_id']]);

        // AT veya Super Admin her şeyi görebilir mi? 
        // Mevcut kodda: baskan_iade-formlari.php:53 -> AND byk_id = ?
        // Demek ki herkes kendi BYK'sına bakıyor. 
        // Fakat eğer kullanıcı AT başkanıysa, onun BYK_ID'si AT'dir.

        if (!$talep) {
            $message = 'Talep bulunamadı veya yetkiniz yok.';
            $messageType = 'danger';
        } else {
            // Kimin ne yetkisi var?
            // AT başkanı (veya SuperAdmin): Ödeme yapabilir (mark_paid).
            // Diğer başkanlar: Sadece Onay/Red.

            // Kullanıcının BYK Kodunu bul
            $userBykCode = '';
            try {
                $bykInfo = $db->fetch("SELECT byk_kodu FROM byk WHERE byk_id = ?", [$user['byk_id']]);
                $userBykCode = $bykInfo['byk_kodu'] ?? '';
            } catch (Exception $e) {
            }

            $canPay = ($userBykCode === 'AT' || $user['role'] === 'super_admin');

            if (($action === 'mark_paid' || $action === 'mark_unpaid') && !$canPay) {
                $message = 'Ödeme durumunu değiştirme yetkiniz yok.';
                $messageType = 'danger';
            } else {
                if ($action === 'mark_paid') {
                    $db->query("UPDATE harcama_talepleri SET durum = 'odenmistir', onaylayan_id = ?, onay_tarihi = NOW(), onay_aciklama = ? WHERE talep_id = ?", [$user['id'], $note, $talepId]);
                    $message = 'Talep ödendi olarak işaretlendi.';
                } elseif ($action === 'mark_unpaid') {
                    $db->query("UPDATE harcama_talepleri SET durum = 'beklemede', onaylayan_id = ?, onay_tarihi = NOW(), onay_aciklama = ? WHERE talep_id = ?", [$user['id'], $note, $talepId]);
                    $message = 'Talep ödenmedi olarak işaretlendi.';
                } elseif ($action === 'approve') {
                    $db->query("UPDATE harcama_talepleri SET durum = 'onaylandi', onaylayan_id = ?, onay_tarihi = NOW(), onay_aciklama = ? WHERE talep_id = ?", [$user['id'], $note ?: 'Onaylandı', $talepId]);
                    $message = 'Talep onaylandı.';
                } elseif ($action === 'reject') {
                    $db->query("UPDATE harcama_talepleri SET durum = 'reddedildi', onaylayan_id = ?, onay_tarihi = NOW(), onay_aciklama = ? WHERE talep_id = ?", [$user['id'], $note ?: 'Reddedildi', $talepId]);
                    $message = 'Talep reddedildi.';
                } else {
                    $message = 'Geçersiz işlem.';
                    $messageType = 'danger';
                }

                if (isset($message) && $messageType === 'success') {
                    // Kullanıcı e-postasını bul
                    $uyeInfo = $db->fetch("SELECT k.email, CONCAT(k.ad, ' ', k.soyad) as ad_soyad FROM kullanicilar k INNER JOIN harcama_talepleri ht ON k.kullanici_id = ht.kullanici_id WHERE ht.talep_id = ?", [$talepId]);

                    if ($uyeInfo) {
                        $statusMap = [
                            'approve' => 'Onaylandı',
                            'reject' => 'Reddedildi',
                            'mark_paid' => 'Ödendi',
                            'mark_unpaid' => 'Beklemede'
                        ];

                        Mail::sendWithTemplate($uyeInfo['email'], 'talep_sonuc', [
                            'ad_soyad' => $uyeInfo['ad_soyad'],
                            'talep_turu' => 'İade/Harcama Talebi',
                            'durum' => $statusMap[$action] ?? 'Güncellendi',
                            'aciklama' => $note ?: 'Talebiniz yönetici tarafından güncellenmiştir.'
                        ]);
                    }
                }

                $activeTab = 'yonetim';
            }
        }
    }
}

// VERİ ÇEKME - YÖNETİM LİSTESİ
$talepList = [];
$stats = [];
$years = [];
$uyeOptions = [];
$yearFilter = (int) ($_GET['year'] ?? 0);
$monthFilter = (int) ($_GET['month'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$uyeFilter = trim($_GET['uye'] ?? '');

if ($hasPermissionBaskan && $activeTab === 'yonetim') {

    // Kullanıcının BYK Kodunu bul (AT mi değil mi?)
    $userBykCode = '';
    try {
        $bykInfo = $db->fetch("SELECT byk_kodu FROM byk WHERE byk_id = ?", [$user['byk_id']]);
        $userBykCode = $bykInfo['byk_kodu'] ?? '';
    } catch (Exception $e) {
    }

    $isAT = ($userBykCode === 'AT' || $user['role'] === 'super_admin');

    // Temel Filtreleme Mantığı
    if ($isAT) {
        // AT (Genel Merkez):
        // 1. Kendi birimine (AT) ait tüm talepler
        // 2. VEYA Diğer birimlerden gelip 'onaylandi' veya 'odenmistir' statüsünde olanlar
        // (Bekleyenler yerel onayı beklediği için AT görmez)
        $filters = ["(ht.byk_id = ? OR ht.durum IN ('onaylandi', 'odenmistir'))"];
    } else {
        // Yerel Birimler (KT, GT, KGT): Sadece kendi birimi
        $filters = ["ht.byk_id = ?"];
    }

    $params = [$user['byk_id']];

    if ($statusFilter === 'paid')
        $filters[] = "ht.durum = 'odenmistir'";
    elseif ($statusFilter === 'unpaid')
        $filters[] = "ht.durum <> 'odenmistir'";

    if ($yearFilter > 0) {
        $filters[] = "YEAR(ht.olusturma_tarihi) = ?";
        $params[] = $yearFilter;
    }
    if ($monthFilter > 0 && $monthFilter <= 12) {
        $filters[] = "MONTH(ht.olusturma_tarihi) = ?";
        $params[] = $monthFilter;
    }
    if ($uyeFilter !== '') {
        $filters[] = "CONCAT(k.ad, ' ', k.soyad) = ?";
        $params[] = $uyeFilter;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $filters);

    $talepList = $db->fetchAll("
        SELECT ht.*, CONCAT(k.ad, ' ', k.soyad) AS uye_adi, k.email, k.telefon, b.byk_adi, b.byk_kodu
        FROM harcama_talepleri ht
        INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id
        LEFT JOIN byk b ON ht.byk_id = b.byk_id
        $whereSql
        ORDER BY 
            CASE WHEN ht.durum = 'onaylandi' THEN 0 ELSE 1 END, -- Onaylanmışlar (Ödeme Bekleyenler) en üstte
            ht.olusturma_tarihi DESC
        LIMIT 200
    ", $params);

    // İstatistikler (Filtre mantığına uygun olmalı)
    if ($isAT) {
        $stats = $db->fetch("
            SELECT 
                COUNT(*) AS toplam,
                SUM(CASE WHEN durum = 'odenmistir' THEN 1 ELSE 0 END) AS odendi_adet,
                SUM(CASE WHEN durum = 'odenmistir' THEN tutar ELSE 0 END) AS odendi_tutar,
                SUM(CASE WHEN durum <> 'odenmistir' THEN 1 ELSE 0 END) AS odenmedi_adet,
                SUM(CASE WHEN durum <> 'odenmistir' THEN tutar ELSE 0 END) AS odenmedi_tutar
            FROM harcama_talepleri
            WHERE (byk_id = ? OR durum IN ('onaylandi', 'odenmistir'))
        ", [$user['byk_id']]);
    } else {
        $stats = $db->fetch("
            SELECT 
                COUNT(*) AS toplam,
                SUM(CASE WHEN durum = 'odenmistir' THEN 1 ELSE 0 END) AS odendi_adet,
                SUM(CASE WHEN durum = 'odenmistir' THEN tutar ELSE 0 END) AS odendi_tutar,
                SUM(CASE WHEN durum <> 'odenmistir' THEN 1 ELSE 0 END) AS odenmedi_adet,
                SUM(CASE WHEN durum <> 'odenmistir' THEN tutar ELSE 0 END) AS odenmedi_tutar
            FROM harcama_talepleri
            WHERE byk_id = ?
        ", [$user['byk_id']]);
    }

    // Filtre seçenekleri için veriler
    if ($isAT) {
        $years = $db->fetchAll("SELECT DISTINCT YEAR(olusturma_tarihi) AS yil FROM harcama_talepleri WHERE (byk_id = ? OR durum IN ('onaylandi', 'odenmistir')) ORDER BY yil DESC", [$user['byk_id']]);
        $uyeOptions = $db->fetchAll("SELECT DISTINCT CONCAT(k.ad, ' ', k.soyad) AS adsoyad FROM harcama_talepleri ht INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id WHERE (ht.byk_id = ? OR ht.durum IN ('onaylandi', 'odenmistir')) ORDER BY adsoyad ASC", [$user['byk_id']]);
    } else {
        $years = $db->fetchAll("SELECT DISTINCT YEAR(olusturma_tarihi) AS yil FROM harcama_talepleri WHERE byk_id = ? ORDER BY yil DESC", [$user['byk_id']]);
        $uyeOptions = $db->fetchAll("SELECT DISTINCT CONCAT(k.ad, ' ', k.soyad) AS adsoyad FROM harcama_talepleri ht INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id WHERE ht.byk_id = ? ORDER BY adsoyad ASC", [$user['byk_id']]);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #009872;
        --primary-600: #007a5e;
        --border: #e5e7eb;
    }

    /* Layout Fixes */
    .dashboard-layout {
        display: block;
    }

    .sidebar-wrapper {
        display: none;
    }

    .main-content {
        width: 100%;
    }

    @media (min-width: 992px) {
        .dashboard-layout {
            display: flex;
            flex-direction: row;
        }

        .sidebar-wrapper {
            display: block;
            width: 250px;
            flex-shrink: 0;
            z-index: 1000;
        }

        .main-content {
            flex-grow: 1;
            width: auto;
            min-width: 0;
            /* Prevent flex overflow */
        }

        .content-wrapper {
            padding: 1.5rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
    }

    /* Autocomplete Suggestions */
    .suggestions {
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        z-index: 1050;
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 0 0 10px 10px;
        display: none;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: 0 4px 6px rgba(0, 0, 0, .1);
    }

    .suggestion-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }

    .suggestion-item:last-child {
        border-bottom: 0;
    }

    .suggestion-item:hover {
        background: rgba(0, 152, 114, .08);
        color: var(--primary);
    }

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

    .iade-dashboard .stat-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px 18px;
        background: #fff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .6);
    }

    .iade-dashboard .stat-card .label {
        font-size: 13px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .iade-dashboard .stat-card .value {
        font-size: 26px;
        font-weight: 700;
        margin-top: 6px;
        color: #0f172a;
    }

    .badge.paid {
        background: rgba(16, 185, 129, .15);
        color: #0f766e;
    }

    .badge.unpaid {
        background: rgba(239, 68, 68, .15);
        color: #be123c;
    }
</style>

<div class="dashboard-layout">
    <div class="sidebar-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?></div>
    <main class="main-content">
        <div class="content-wrapper">

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="h3 mb-1"><i class="fas fa-hand-holding-usd me-2"></i>İade Formları</h1>
                    <p class="text-muted mb-0">Harcama iade taleplerini oluşturun ve yönetin.</p>
                </div>
                <ul class="nav nav-pills bg-white p-1 rounded-4 border shadow-sm">
                    <?php if ($hasPermissionUye): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab === 'form') ? 'active' : ''; ?>" href="?tab=form">
                                <i class="fas fa-file-invoice-dollar me-2"></i>İade Talebi (Form)
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($hasPermissionBaskan): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab === 'yonetim') ? 'active' : ''; ?>"
                                href="?tab=yonetim">
                                <i class="fas fa-layer-group me-2"></i>Yönetim (Onay)
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="tab-content">

                <!-- TAB 1: FORM OLUŞTURMA (ÜYE) -->
                <?php if ($hasPermissionUye && $activeTab === 'form'): ?>
                    <div class="tab-pane fade show active">
                        <div class="card shadow-sm border-0">
                            <div class="card-body p-4">
                                <form id="expenseForm" onsubmit="window.handleSubmit(event)">
                                    <h5 class="card-title mb-4 pb-2 border-bottom text-primary fw-bold">
                                        <i class="fas fa-edit me-2"></i>Gider Formu Oluştur
                                    </h5>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">İsim</label>
                                            <input type="text" class="form-control bg-light" id="name" name="name"
                                                value="<?php echo htmlspecialchars($uyeAd); ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted">Soyisim</label>
                                            <input type="text" class="form-control bg-light" id="surname" name="surname"
                                                value="<?php echo htmlspecialchars($uyeSoyad); ?>" readonly>
                                        </div>
                                    </div>

                                    <div id="itemsContainer"></div>

                                    <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="addItemBtn">
                                        <i class="fas fa-plus me-1"></i>Yeni Kalem Ekle
                                    </button>

                                    <div class="row g-3 align-items-end border-top pt-3 bg-light p-3 rounded">
                                        <div class="col-md-8">
                                            <label class="form-label small fw-bold">IBAN (TR/AT)</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control font-monospace" id="iban" name="iban"
                                                    placeholder="AT.. (4’lü bloklarla)" required
                                                    value="<?php echo htmlspecialchars($savedIban); ?>"
                                                    oninput="window.formatIban(this)">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="window.saveIban()">
                                                    <i class="fas fa-save me-1"></i>Kaydet
                                                </button>
                                            </div>
                                            <div class="form-text text-danger mt-1 small">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                <strong>Önemli:</strong> Yeni bankacılık kuralları gereği, IBAN sahibi ile
                                                yukarıdaki İsim/Soyisim birebir uyuşmalıdır. Aksi takdirde ödeme yapılamaz.
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-end w-100">Toplam Tutar (€)</label>
                                            <input type="text"
                                                class="form-control form-control-lg text-end fw-bold text-success border-0 bg-transparent"
                                                id="total" name="total" readonly value="0.00">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg px-5">
                                            <i class="fas fa-paper-plane me-2"></i>Gideri Bildir
                                        </button>
                                    </div>
                                </form>

                                <!-- Spinner & Error -->
                                <div id="spinner" class="text-center mt-3" style="display:none;">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted">PDF oluşturuluyor ve gönderiliyor...</p>
                                </div>
                                <div id="errorMessage" class="alert alert-danger mt-3" style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- JS Dependencies -->
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

                    <!-- Form Script Logic (Inline for consolidation) -->
                    <!-- Script moved to bottom for better loading -->
                <?php endif; ?>

                <!-- TAB 2: YÖNETİM PANELİ (BAŞKAN) -->
                <?php if ($hasPermissionBaskan && $activeTab === 'yonetim'): ?>
                    <div class="tab-pane fade show active">
                        <div class="iade-dashboard p-0 border-0 shadow-none">

                            <!-- İstatistikler -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="stat-card h-100">
                                        <div class="label">Bekleyen</div>
                                        <div class="value text-warning"><?php echo (int) ($stats['odenmedi_adet'] ?? 0); ?>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?php echo number_format((float) ($stats['odenmedi_tutar'] ?? 0), 2, ',', '.'); ?>
                                            €</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card h-100">
                                        <div class="label">Ödenmiş</div>
                                        <div class="value text-success"><?php echo (int) ($stats['odendi_adet'] ?? 0); ?>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?php echo number_format((float) ($stats['odendi_tutar'] ?? 0), 2, ',', '.'); ?>
                                            €</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtreler -->
                            <div class="card mb-4 border-0 shadow-sm bg-light">
                                <div class="card-body py-3">
                                    <form class="row g-3 align-items-end" method="get">
                                        <input type="hidden" name="tab" value="yonetim">
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Yıl</label>
                                            <select name="year" class="form-select form-select-sm">
                                                <option value="">Tümü</option>
                                                <?php foreach ($years as $y)
                                                    echo "<option value='{$y['yil']}' " . ($yearFilter == $y['yil'] ? 'selected' : '') . ">{$y['yil']}</option>"; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold">Durum</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="">Tümü</option>
                                                <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>
                                                    Ödendi</option>
                                                <option value="unpaid" <?php echo $statusFilter === 'unpaid' ? 'selected' : ''; ?>>Ödenmedi</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary btn-sm w-100">Filtrele</button>
                                        </div>
                                        <div class="col-md-2">
                                            <a href="?tab=yonetim"
                                                class="btn btn-outline-secondary btn-sm w-100">Temizle</a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Tablo -->
                            <div class="card border-0 shadow-sm">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Kullanıcı</th>
                                                <th>Başlık</th>
                                                <th>Tutar</th>
                                                <th>Durum</th>
                                                <th class="text-end">İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($talepList)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-muted">Kayıt bulunamadı.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($talepList as $talep): ?>
                                                    <?php
                                                    $isPaid = $talep['durum'] === 'odenmistir';
                                                    // Yetki hesabı:
                                                    // NOT: Bu logic yukarıdaki post handler ile aynı olmalı.
                                                    // Basitlik için burada tekrar sorgu yapmıyoruz, genel bir check yapıyoruz.
                                                    // Gerçek prod ortamında user yetkisi cache'lenmeli.
                                                    ?>
                                                    <tr>
                                                        <td><small><?php echo date('d.m.Y', strtotime($talep['olusturma_tarihi'])); ?></small>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($talep['uye_adi']); ?></td>
                                                        <td><?php echo htmlspecialchars($talep['baslik']); ?></td>
                                                        <td class="fw-bold text-success">
                                                            <?php echo number_format($talep['tutar'], 2, ',', '.'); ?> €</td>
                                                        <td>
                                                            <span class="badge <?php echo $isPaid ? 'paid' : 'unpaid'; ?>">
                                                                <?php echo $isPaid ? 'Ödendi' : 'Ödenmedi/Bekliyor'; ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <!-- Butonlar Modal ile tetiklenebilir -->
                                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                                data-bs-target="#actionModal"
                                                                data-id="<?php echo $talep['talep_id']; ?>"
                                                                data-paid="<?php echo $isPaid ? '1' : '0'; ?>">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
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

                    <!-- Action Modal -->
                    <div class="modal fade" id="actionModal" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="post" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">İşlem Yap</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>"
                                        value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="talep_id" id="modalTalepId">

                                    <div class="mb-3">
                                        <label class="form-label">İşlem Seçin</label>
                                        <select name="action" class="form-select" id="modalActionSelect">
                                            <option value="approve">Onayla (Üst Birime İlet)</option>
                                            <option value="reject">Reddet (İade Et)</option>
                                            <option value="mark_paid">Ödendi Olarak İşaretle (Muhasebe)</option>
                                            <option value="mark_unpaid">Ödenmedi Olarak İşaretle</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Not / Açıklama</label>
                                        <textarea name="aciklama" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        const actionModal = document.getElementById('actionModal');
                        if (actionModal) {
                            actionModal.addEventListener('show.bs.modal', function (event) {
                                const button = event.relatedTarget;
                                const id = button.getAttribute('data-id');
                                const isPaid = button.getAttribute('data-paid') === '1';
                                this.querySelector('#modalTalepId').value = id;

                                // Ön seçim mantığı, isterseniz geliştirebilirsiniz
                                this.querySelector('#modalActionSelect').value = isPaid ? 'mark_unpaid' : 'approve';
                            });
                        }
                    </script>
                <?php endif; ?>

            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php if ($hasPermissionUye): ?>
    <script>
        // Sabitler ve Ayarlar
        var HESAPLAMA_BASE = '<?php echo $formBasePath; ?>';
        var DEFAULT_BYK = '<?php echo htmlspecialchars($uyeBykKodu); ?>';
        if (typeof ORS_API_KEY === 'undefined') {
            var ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjdiYWRhNGRlODEwNjQ1ZjY4NmI0MmMzZDgwOTExODJlIiwiaCI6Im11cm11cjY0In0=';
        }
        var itemCounter = 0;
        var AUTOCOMPLETE_DEBOUNCE_MS = 250;

        if (window['pdfjsLib']) pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // Yardımcı Fonksiyonlar
        function debounce(fn, delay) {
            let t;
            return (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), delay);
            };
        }

        async function fetchAddressSuggestions(query) {
            if (!query || query.length < 2) return [];
            const url = `https://api.openrouteservice.org/geocode/autocomplete?api_key=${encodeURIComponent(ORS_API_KEY)}&text=${encodeURIComponent(query)}&size=5&boundary.country=AT,DE,CH&lang=tr`;
            try {
                const res = await fetch(url);
                const data = await res.json();
                if (!data.features) return [];
                return data.features.map(f => ({ label: f.properties.label || '', lon: f.geometry.coordinates[0], lat: f.geometry.coordinates[1] }));
            } catch (e) { console.error("Geocoding error", e); return []; }
        }

        function attachAutocomplete(inputEl, suggestionsEl) {
            if (!inputEl || !suggestionsEl) return;

            const render = (items) => {
                suggestionsEl.innerHTML = '';
                if (!items.length) { suggestionsEl.style.display = 'none'; return; }
                suggestionsEl.style.display = 'block';
                items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.textContent = item.label;
                    div.onclick = () => {
                        inputEl.value = item.label;
                        suggestionsEl.style.display = 'none';
                    };
                    suggestionsEl.appendChild(div);
                });
            };
            const debounced = debounce(async () => {
                const q = inputEl.value.trim();
                const items = await fetchAddressSuggestions(q).catch(() => []);
                render(items);
            }, AUTOCOMPLETE_DEBOUNCE_MS);

            inputEl.addEventListener('input', debounced);
            inputEl.addEventListener('blur', () => setTimeout(() => suggestionsEl.style.display = 'none', 150));
            inputEl.addEventListener('focus', () => { if (inputEl.value.trim().length >= 2) debounced(); });
        }

        // -- Item Ekleme ve Yönetimi --

        window.addItem = function () {
            var container = document.getElementById('itemsContainer');
            if (!container) return;

            var newItem = document.createElement('div');
            newItem.className = 'item card mb-3 border bg-white shadow-sm';
            var itemId = itemCounter++;

            newItem.innerHTML = getItemHtml(itemId);
            container.appendChild(newItem);

            // Autocomplete Bağla (Simple Mode)
            const startInput = newItem.querySelector('.route-start');
            const endInput = newItem.querySelector('.route-end');
            const startSug = newItem.querySelector('.suggestions-start');
            const endSug = newItem.querySelector('.suggestions-end');

            if (startInput && startSug) attachAutocomplete(startInput, startSug);
            if (endInput && endSug) attachAutocomplete(endInput, endSug);

            // Autocomplete Bağla (Multi Mode - İlk 2 durak)
            const multiStops = newItem.querySelectorAll('.route-stop-input');
            const multiSuggs = newItem.querySelectorAll('.multi-route-mode .suggestions');
            multiStops.forEach((input, idx) => {
                if (multiSuggs[idx]) attachAutocomplete(input, multiSuggs[idx]);
            });
        };

        function getItemHtml(itemId) {
            var selectedAT = (DEFAULT_BYK === 'AT') ? 'selected' : '';
            var selectedKT = (DEFAULT_BYK === 'KT') ? 'selected' : '';
            var selectedGT = (DEFAULT_BYK === 'GT') ? 'selected' : '';
            var selectedKGT = (DEFAULT_BYK === 'KGT') ? 'selected' : '';

            // Not: PHP içinde JS string literal oluşturuyoruz, backslash kaçışlarına dikkat.
            // HTML içinde data-item="${itemId}" gibi kullanılmalı.

            return `
            <div class="card-body position-relative">
                <button type="button" class="btn btn-close position-absolute top-0 end-0 m-2" onclick="this.closest('.item').remove(); window.calculateTotal();"></button>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small text-muted">Tarih</label><input type="date" class="form-control" name="position-datum[]" required></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Birim/BYK</label>
                        <select class="form-select" name="region[]" required>
                            <option value="AT" ${selectedAT}>AT</option>
                            <option value="KT" ${selectedKT}>KT</option>
                            <option value="GT" ${selectedGT}>GT</option>
                            <option value="KGT" ${selectedKGT}>KGT</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label small text-muted">Birim</label>
                        <select class="form-select" name="birim[]" required>
                            <option value="baskan">Başkan</option>
                            <option value="byk">BYK Üyesi</option>
                            <option value="egitim">Eğitim</option>
                            <option value="fuar">Fuar</option>
                            <option value="gob">Spor/Gezi (GOB)</option>
                            <option value="hacumre">Hac/Umre</option>
                            <option value="idair">İdari İşler</option>
                            <option value="irsad">İrşad</option>
                            <option value="kurumsal">Kurumsal İletişim</option>
                            <option value="muhasebe">Muhasebe</option>
                            <option value="ortaogretim">Orta Öğretim</option>
                            <option value="raggal">Raggal</option>
                            <option value="sosyal">Sosyal Hizmetler</option>
                            <option value="tanitma">Tanıtma</option>
                            <option value="teftis">Teftiş</option>
                            <option value="teskilatlanma">Teşkilatlanma</option>
                            <option value="universiteler">Üniversiteler</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label small text-muted">Tür</label>
                        <select class="form-select" name="gider-turu[]" required onchange="window.handleGiderTuruChange(this)">
                            <option value="genel">Genel</option>
                            <option value="ulasim_km">Ulaşım - Kilometre Hesaplama</option>
                            <option value="ulasim_fatura">Ulaşım - Faturalı/Kasabon</option>
                            <option value="yemek">Yemek/İkram</option>
                            <option value="konaklama">Konaklama</option>
                            <option value="malzeme">Malzeme</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label small text-muted">Ödeme Şekli</label>
                        <select class="form-select" name="odeme-sekli[]" required onchange="window.handleOdemeSekliChange(this)">
                            <option value="faturasiz">Faturasız</option>
                            <option value="faturali">Faturalı</option>
                        </select>
                    </div>
                </div>
                
                <div class="yakit-fields mt-3 p-3 bg-light border rounded" style="display:none;">
                    <label class="small fw-bold text-primary mb-2 d-block">Mesafe Hesapla</label>
                    
                    <!-- Basit Mod: Başlangıç - Bitiş -->
                    <div class="simple-route-mode">
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-5 position-relative">
                                <input type="text" class="form-control form-control-sm route-start" placeholder="Başlangıç (Şehir/Adres)" data-item="${itemId}">
                                <div class="suggestions suggestions-start" style="position: absolute; width:100%;"></div>
                            </div>
                            <div class="col-12 col-md-1 text-center align-self-center">
                                <i class="fas fa-arrow-right text-muted"></i>
                            </div>
                            <div class="col-12 col-md-5 position-relative">
                                <input type="text" class="form-control form-control-sm route-end" placeholder="Bitiş (Şehir/Adres)">
                                <div class="suggestions suggestions-end" style="position: absolute; width:100%;"></div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="window.calculateDistance(${itemId}, false)">
                                <i class="fas fa-exchange-alt me-1"></i>Gidiş-Dönüş
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.calculateDistance(${itemId}, true)">
                                <i class="fas fa-arrow-right me-1"></i>Tek Gidiş
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.showMultiRoute(${itemId})">
                                <i class="fas fa-route me-1"></i>Rota Oluştur
                            </button>
                        </div>
                    </div>
                    
                    <!-- Çoklu Rota Modu -->
                    <div class="multi-route-mode" style="display:none;">
                        <div class="mb-2">
                            <label class="small text-muted mb-1">Çoklu Durak Rotası (sırasıyla)</label>
                            <div class="route-stops-container">
                                <div class="route-stop d-flex gap-2 mb-2 align-items-center">
                                    <span class="badge bg-primary">1</span>
                                    <div class="flex-grow-1 position-relative">
                                        <input type="text" class="form-control form-control-sm route-stop-input" placeholder="1. Durak (Başlangıç)">
                                        <div class="suggestions" style="position: absolute; width:100%;"></div>
                                    </div>
                                </div>
                                <div class="route-stop d-flex gap-2 mb-2 align-items-center">
                                    <span class="badge bg-secondary">2</span>
                                    <div class="flex-grow-1 position-relative">
                                        <input type="text" class="form-control form-control-sm route-stop-input" placeholder="2. Durak">
                                        <div class="suggestions" style="position: absolute; width:100%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="window.addRouteStop(${itemId})">
                                    <i class="fas fa-plus me-1"></i>Durak Ekle
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="window.calculateMultiRoute(${itemId})">
                                    <i class="fas fa-calculator me-1"></i>Rotayı Hesapla
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.showSimpleRoute(${itemId})">
                                    <i class="fas fa-times me-1"></i>Basit Mod
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="kilometer" class="form-control kilometer-field" placeholder="0.00" onchange="window.calcFuelCost(this)" step="0.01">
                                    <span class="input-group-text">km</span>
                                </div>
                                <div class="form-text mt-1" style="font-size: 0.75rem;">
                                    <i class="fas fa-info-circle me-1 text-primary"></i>Mesafe km başı 0,25 € olarak hesaplanmaktadır.
                                </div>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="route" class="form-control form-control-sm route-full" placeholder="Rota detayı" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1 align-items-end">
                    <div class="col-md-2 amount-simple">
                        <label class="form-label fw-bold small">Gider Miktari (€)</label>
                        <input type="text" class="form-control" name="gider-miktari[]" required onchange="window.calculateTotal()" placeholder="0.00">
                    </div>

                    <div class="col-md-2 amount-detailed" style="display:none;">
                        <label class="form-label fw-bold small" style="font-size:0.75rem;">Gider Miktari Netto</label>
                        <input type="text" class="form-control" name="netto[]" onchange="window.calculateBrutto(this)" placeholder="Net">
                    </div>
                    <div class="col-md-2 amount-detailed" style="display:none;">
                        <label class="form-label fw-bold small" style="font-size:0.75rem;">MwSt/KDV Miktari</label>
                        <input type="text" class="form-control" name="mwst[]" onchange="window.calculateBrutto(this)" placeholder="KDV">
                    </div>
                    <div class="col-md-2 amount-detailed" style="display:none;">
                        <label class="form-label fw-bold small" style="font-size:0.75rem;">Gider Miktari Brutto</label>
                        <input type="text" class="form-control bg-light" name="brutto[]" readonly placeholder="Brüt">
                    </div>

                    <div class="col-md-4"><label class="form-label small text-muted">Açıklama</label><input type="text" class="form-control" name="beschreibung[]" placeholder="Detay..."></div>
                    <div class="col-md-12"><label class="form-label small text-muted">Fiş/Fatura</label><input type="file" class="form-control item-documents" accept="image/*,.pdf" multiple></div>
                </div>
            </div>`;
        }

        window.handleGiderTuruChange = function (sel) {
            var item = sel.closest('.item');
            var yakit = item.querySelector('.yakit-fields');
            var amount = item.querySelector('.amount-simple input');
            var odemeSelect = item.querySelector('select[name="odeme-sekli[]"]');

            if (sel.value === 'ulasim_km') {
                yakit.style.display = 'block';
                if (amount) amount.readOnly = true;
                odemeSelect.value = 'faturasiz';
                window.handleOdemeSekliChange(odemeSelect, true);
            } else {
                yakit.style.display = 'none';
                if (amount) amount.readOnly = false;
            }
        };

        window.handleOdemeSekliChange = function (sel, forceSimple) {
            forceSimple = forceSimple || false;
            var item = sel.closest('.item');
            var isFaturali = (sel.value === 'faturali') && !forceSimple;

            var simpleDiv = item.querySelector('.amount-simple');
            var detailedDivs = item.querySelectorAll('.amount-detailed');
            var simpleInput = simpleDiv.querySelector('input');

            if (isFaturali) {
                simpleDiv.style.display = 'none';
                detailedDivs.forEach(function (d) { d.style.display = 'block'; });
                simpleInput.removeAttribute('required');
            } else {
                simpleDiv.style.display = 'block';
                detailedDivs.forEach(function (d) { d.style.display = 'none'; });
                simpleInput.setAttribute('required', 'required');
            }
            window.calculateTotal();
        };

        window.calculateBrutto = function (input) {
            var item = input.closest('.item');
            var netEl = item.querySelector('input[name="netto[]"]');
            var vatEl = item.querySelector('input[name="mwst[]"]');
            var bruttoInput = item.querySelector('input[name="brutto[]"]');

            var net = parseFloat(netEl.value.replace(',', '.')) || 0;
            var vat = parseFloat(vatEl.value.replace(',', '.')) || 0;

            var total = net + vat;
            bruttoInput.value = total.toFixed(2);

            var simpleInput = item.querySelector('.amount-simple input');
            if (simpleInput) simpleInput.value = total.toFixed(2);

            window.calculateTotal();
        };

        window.calcFuelCost = function (input) {
            var km = parseFloat(input.value) || 0;
            var cost = km * 0.25;
            var amountInput = input.closest('.item').querySelector('.amount-simple input');
            if (amountInput) amountInput.value = cost.toFixed(2);
            window.calculateTotal();
        };

        window.calculateTotal = function () {
            var total = 0;
            var inputs = document.querySelectorAll('.amount-simple input');
            inputs.forEach(function (i) {
                total += parseFloat(i.value) || 0;
            });
            var totalField = document.getElementById('total');
            if (totalField) totalField.value = total.toFixed(2);
        };

        // Geocoding: Adresi koordinata çevir (OpenRouteService)
        window.geocodeCity = async function (cityName) {
            const url = `https://api.openrouteservice.org/geocode/search?api_key=${encodeURIComponent(ORS_API_KEY)}&text=${encodeURIComponent(cityName)}&size=1&boundary.country=AT,DE,CH&lang=tr`;
            const response = await fetch(url);
            const data = await response.json();
            if (!data.features || data.features.length === 0) return null;
            const coords = data.features[0].geometry.coordinates; // [lon, lat]
            return { lat: parseFloat(coords[1]), lon: parseFloat(coords[0]) };
        };

        window.calculateDistance = async function (id, isOneWay) {
            try {
                var item = document.querySelector('.route-start[data-item="' + id + '"]').closest('.item');
                var startInput = item.querySelector('.route-start');
                var endInput = item.querySelector('.route-end');

                var start = startInput.value.trim();
                var end = endInput.value.trim();

                if (!start || !end) {
                    alert('Lütfen başlangıç ve bitiş adreslerini girin.');
                    return;
                }

                updateRouteLoadingState(item, true, 'Hesaplanıyor...');

                // Geocoding (ORS) -> Rota (OSRM)
                const startCoords = await window.geocodeCity(start);
                const endCoords = await window.geocodeCity(end);

                if (!startCoords || !endCoords) {
                    throw new Error('Adres bulunamadı. Lütfen geçerli bir adres girin.');
                }

                const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${startCoords.lon},${startCoords.lat};${endCoords.lon},${endCoords.lat}?overview=false`;
                const osrmRes = await fetch(osrmUrl);
                const osrmData = await osrmRes.json();

                if (osrmData.code !== 'Ok') { throw new Error('OSRM rota bulamadı'); }

                const oneWayKm = osrmData.routes[0].distance / 1000;
                // Tek yön mü, Gidiş-Dönüş mü?
                const totalKm = isOneWay ? oneWayKm : (oneWayKm * 2);

                updateRouteResult(item, totalKm, `${start} → ${end} (${isOneWay ? 'Tek Yön' : 'Gidiş-Dönüş'})`);

            } catch (e) {
                console.error(e);
                alert('Hata: ' + e.message);
            } finally {
                updateRouteLoadingState(item, false);
            }
        };

        // Çoklu Rota - Arayüz Fonksiyonları
        window.showMultiRoute = function (itemId) {
            var item = document.querySelector('.route-start[data-item="' + itemId + '"]').closest('.item');
            item.querySelector('.simple-route-mode').style.display = 'none';
            item.querySelector('.multi-route-mode').style.display = 'block';

            // Mevcut değerleri taşı
            var startVal = item.querySelector('.route-start').value;
            var endVal = item.querySelector('.route-end').value;

            var stops = item.querySelectorAll('.route-stop-input');
            if (stops.length >= 2) {
                if (startVal) stops[0].value = startVal;
                if (endVal) stops[1].value = endVal;
            }
        };

        window.showSimpleRoute = function (itemId) {
            var item = document.querySelector('.route-start[data-item="' + itemId + '"]').closest('.item');
            item.querySelector('.multi-route-mode').style.display = 'none';
            item.querySelector('.simple-route-mode').style.display = 'block';
        };

        window.addRouteStop = function (itemId) {
            var item = document.querySelector('.route-start[data-item="' + itemId + '"]').closest('.item');
            var container = item.querySelector('.route-stops-container');
            var count = container.querySelectorAll('.route-stop').length + 1;

            var newStopHtml = `
            <div class="route-stop d-flex gap-2 mb-2 align-items-center">
                <span class="badge bg-secondary">${count}</span>
                <div class="flex-grow-1 position-relative">
                    <input type="text" class="form-control form-control-sm route-stop-input" placeholder="${count}. Durak">
                    <button type="button" class="btn btn-sm text-danger position-absolute end-0 top-0 mt-1 me-1" onclick="this.closest('.route-stop').remove();" style="z-index:10; padding:2px 6px;">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="suggestions" style="position: absolute; width:100%;"></div>
                </div>
            </div>
        `;

            // HTML string'i elemente çevir ekle (Autocomplete bağlayabilmek için)
            var div = document.createElement('div');
            div.innerHTML = newStopHtml;
            var newEl = div.firstElementChild;
            container.appendChild(newEl);

            var input = newEl.querySelector('input');
            var sug = newEl.querySelector('.suggestions');
            attachAutocomplete(input, sug);
        };

        // Çoklu Rota Hesaplama
        window.calculateMultiRoute = async function (itemId) {
            var item = document.querySelector('.route-start[data-item="' + itemId + '"]').closest('.item');
            updateRouteLoadingState(item, true, 'Rota Hesaplanıyor...');

            try {
                var inputs = item.querySelectorAll('.route-stop-input');
                var points = [];
                var routeDesc = [];

                // Adresleri topla
                for (var i = 0; i < inputs.length; i++) {
                    var val = inputs[i].value.trim();
                    if (val) {
                        points.push(val);
                        routeDesc.push(val);
                    }
                }

                if (points.length < 2) {
                    throw new Error('En az 2 durak girmelisiniz.');
                }

                // Koordinatları bul
                var coords = [];
                for (var place of points) {
                    // Burst limitleri için çok kısa bir bekleme
                    if (coords.length > 0) await new Promise(r => setTimeout(r, 150));

                    var c = await window.geocodeCity(place);
                    if (!c) {
                        throw new Error(`Durak "${place}" için adres bulunamadı. Lütfen listeden bir öneri seçerek veya adresi değiştirerek tekrar deneyin.`);
                    }
                    coords.push(c);
                }

                // OSRM URL oluştur (noktaları ; ile birleştir)
                var coordString = coords.map(c => `${c.lon},${c.lat}`).join(';');
                // OSRM trip servisi yerine route servisi kullanalım (sıralı gitmesi için)
                var osrmUrl = `https://router.project-osrm.org/route/v1/driving/${coordString}?overview=false`;

                const osrmRes = await fetch(osrmUrl);
                const osrmData = await osrmRes.json();

                if (osrmData.code !== 'Ok') { throw new Error('Rota hesaplanamadı.'); }

                const totalKm = osrmData.routes[0].distance / 1000;

                updateRouteResult(item, totalKm, routeDesc.join(' → '));

            } catch (e) {
                console.error(e);
                alert('Hata: ' + e.message);
            } finally {
                updateRouteLoadingState(item, false);
            }
        };

        function updateRouteLoadingState(item, isLoading, text) {
            var mode = item.querySelector('.multi-route-mode').style.display !== 'none' ? 'multi' : 'simple';
            // İlgili butonları bul ve disable et
            var btns = item.querySelectorAll('button[onclick*="calculate"]');
            btns.forEach(b => {
                b.disabled = isLoading;
                if (isLoading) {
                    b.dataset.originalText = b.innerHTML;
                    b.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>${text || '...'}`;
                } else {
                    b.innerHTML = b.dataset.originalText || b.innerHTML;
                }
            });
        }

        function updateRouteResult(item, km, desc) {
            var kmInput = item.querySelector('.kilometer-field');
            var routeField = item.querySelector('.route-full');

            kmInput.value = km.toFixed(2);
            if (routeField) routeField.value = desc;

            window.calcFuelCost(kmInput);
        }

        // Form Gönderim
        window.handleSubmit = async function (e) {
            e.preventDefault();
            var spinner = document.getElementById("spinner");
            if (spinner) spinner.style.display = "block";
            try {
                // TODO: Buraya sunucuya gönderim veya PDF oluşturma kodu eklenecek.
                // Şimdilik simülasyon.
                await new Promise(function (r) { setTimeout(r, 1000); });
                alert("Form başarıyla gönderildi (Simülasyon)");
            } catch (err) {
                var errMsg = document.getElementById('errorMessage');
                if (errMsg) errMsg.textContent = err.message;
            } finally {
                if (spinner) spinner.style.display = "none";
            }
        };

        window.saveIban = function () {
            var iban = document.getElementById('iban').value;
            if (!iban) {
                alert("Lütfen IBAN giriniz.");
                return;
            }

            // AJAX call to save IBAN
            fetch('/api/save_iban.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ iban: iban })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                    } else {
                        alert("Hata: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("IBAN kaydetme servisi henüz aktif değil (simülasyon).");
                });
        };

        window.formatIban = function (input) {
            let value = input.value.replace(/\s+/g, '').toUpperCase();
            let formatted = '';
            if (value.length > 0) {
                formatted = value.match(/.{1,4}/g).join(' ');
            }
            input.value = formatted;
        };

        // Sayfa başlatma fonksiyonu
        function initExpenseForm() {
            var container = document.getElementById('itemsContainer');
            if (!container) return;

            // Container temizle (çift yüklenmesini önlemek için)
            container.innerHTML = '';

            // İlk kalemi ekle
            window.addItem();

            // Ekleme butonu dinleyicisi
            var btn = document.getElementById('addItemBtn');
            if (btn) {
                btn.onclick = function () { window.addItem(); };
            }

            // Format initial IBAN if exists
            var ibanInput = document.getElementById('iban');
            if (ibanInput && ibanInput.value) {
                window.formatIban(ibanInput);
            }
        }

        // Yükleme kontrolü
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initExpenseForm);
        } else {
            initExpenseForm();
        }

        // AJAX navigation desteği (jQuery varsa)
        if (typeof jQuery !== 'undefined') {
            $(document).on('page:loaded ajax:success', function () {
                setTimeout(initExpenseForm, 200);
            });
        }
    </script>
<?php endif; ?>
<?php
/**
 * Başkan - İade Formları Panosu
 * Üyelerin harcama/iade taleplerini modern bir arayüzle listeler ve ödeme durumunu güncelleme imkânı verir.
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Custom authorization: Allow Baskan, SuperAdmin OR Accounting Head
$auth = new Auth();
$user = $auth->getUser();
if (!$user) { header('Location: /login.php'); exit; }

$isAuthorized = ($user['role'] === 'super_admin' || $user['role'] === 'baskan');
if (!$isAuthorized) {
    // Check if accounting head
    if ($auth->isAccountingHead($user['id'])) {
        $isAuthorized = true;
    }
}

if (!$isAuthorized) {
    header('Location: /access-denied.php');
    exit;
}

Middleware::requireModulePermission('baskan_iade_formlari');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();
$appConfig = require __DIR__ . '/../config/app.php';

$pageTitle = 'İade Formları';
$csrfTokenName = $appConfig['security']['csrf_token_name'];
$csrfToken = Middleware::generateCSRF();
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Middleware::verifyCSRF()) {
        $message = 'Güvenlik doğrulaması başarısız.';
        $messageType = 'danger';
    } else {
        $talepId = (int)($_POST['talep_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $note = trim($_POST['aciklama'] ?? '');

        $talep = $db->fetch("
            SELECT * FROM harcama_talepleri
            WHERE talep_id = ? AND byk_id = ?
        ", [$talepId, $user['byk_id']]);

        if (!$talep) {
            $message = 'Talep bulunamadı veya yetkiniz yok.';
            $messageType = 'danger';
        } else {
            if ($action === 'mark_paid') {
                $db->query("
                    UPDATE harcama_talepleri
                    SET durum = 'odenmistir',
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE talep_id = ?
                ", [$user['id'], $note ?: null, $talepId]);
                $message = 'Talep ödendi olarak işaretlendi.';
            } elseif ($action === 'mark_unpaid') {
                $db->query("
                    UPDATE harcama_talepleri
                    SET durum = 'beklemede',
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE talep_id = ?
                ", [$user['id'], $note ?: null, $talepId]);
                $message = 'Talep ödenmedi olarak işaretlendi.';
            } elseif ($action === 'approve') {
                 $db->query("
                    UPDATE harcama_talepleri
                    SET durum = 'onaylandi',
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE talep_id = ?
                ", [$user['id'], $note ?: 'Birim Onayı Verildi', $talepId]);
                $message = 'Talep onaylandı ve AT muhasebesine iletildi.';
            } elseif ($action === 'reject') {
                 $db->query("
                    UPDATE harcama_talepleri
                    SET durum = 'reddedildi',
                        onaylayan_id = ?,
                        onay_tarihi = NOW(),
                        onay_aciklama = ?
                    WHERE talep_id = ?
                ", [$user['id'], $note ?: 'Reddedildi', $talepId]);
                $message = 'Talep reddedildi.';
            } else {
                $message = 'Geçersiz işlem.';
                $messageType = 'danger';
            }
        }
    }
}

$yearFilter = (int)($_GET['year'] ?? 0);
$monthFilter = (int)($_GET['month'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$uyeFilter = trim($_GET['uye'] ?? '');

$filters = ["ht.byk_id = ?"];
$params = [$user['byk_id']];

if ($statusFilter === 'paid') {
    $filters[] = "ht.durum = 'odenmistir'";
} elseif ($statusFilter === 'unpaid') {
    $filters[] = "ht.durum <> 'odenmistir'";
}

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
    SELECT ht.*, CONCAT(k.ad, ' ', k.soyad) AS uye_adi, k.email, k.telefon, b.byk_adi
    FROM harcama_talepleri ht
    INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id
    LEFT JOIN byk b ON ht.byk_id = b.byk_id
    $whereSql
    ORDER BY ht.olusturma_tarihi DESC
    LIMIT 200
", $params);

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

$years = $db->fetchAll("
    SELECT DISTINCT YEAR(olusturma_tarihi) AS yil
    FROM harcama_talepleri
    WHERE byk_id = ?
    ORDER BY yil DESC
", [$user['byk_id']]);

$uyeOptions = $db->fetchAll("
    SELECT DISTINCT CONCAT(k.ad, ' ', k.soyad) AS adsoyad
    FROM harcama_talepleri ht
    INNER JOIN kullanicilar k ON ht.kullanici_id = k.kullanici_id
    WHERE ht.byk_id = ?
    ORDER BY adsoyad ASC
", [$user['byk_id']]);

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <style>
            .iade-dashboard * { box-sizing: border-box; }
            .iade-dashboard {
                font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial;
                color: #0f172a;
            }
            .iade-shell {
                background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9));
                border: 2px solid #009872;
                border-radius: 18px;
                padding: 24px;
                box-shadow: 0 20px 45px rgba(15,23,42,.08);
            }
            .iade-shell h1 {
                font-weight: 700;
                color: #009872;
                margin-bottom: 4px;
            }
            .iade-shell .subtitle {
                color: #475569;
                margin-bottom: 24px;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }
            .stat-card {
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 16px 18px;
                background: #fff;
                box-shadow: inset 0 1px 0 rgba(255,255,255,.6);
            }
            .stat-card .label {
                font-size: 13px;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: .5px;
            }
            .stat-card .value {
                font-size: 26px;
                font-weight: 700;
                margin-top: 6px;
                color: #0f172a;
            }
            .stat-card .sub { font-size: 13px; color: #94a3b8; }
            .filters {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 16px;
                margin-bottom: 20px;
            }
            .filters label {
                font-size: 12px;
                color: #475569;
                margin-bottom: 6px;
                font-weight: 600;
            }
            .filters select {
                width: 100%;
                border: 1px solid #cbd5f5;
                border-radius: 10px;
                padding: 10px 12px;
                background: #fff;
                font-size: 14px;
            }
            .filter-actions {
                grid-column: 1 / -1;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                justify-content: flex-end;
            }
            .table-wrapper {
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                overflow: auto;
                box-shadow: 0 12px 30px rgba(15,23,42,.05);
            }
            .table-wrapper table {
                width: 100%;
                border-collapse: collapse;
                min-width: 960px;
                background: #fff;
            }
            .table-wrapper thead th {
                background: linear-gradient(180deg,#22c55e,#16a34a);
                color: #fff;
                font-size: 12px;
                letter-spacing: .3px;
                text-transform: uppercase;
                padding: 12px 10px;
                position: sticky;
                top: 0;
            }
            .table-wrapper tbody td {
                padding: 14px 12px;
                border-bottom: 1px solid #f1f5f9;
                font-size: 14px;
                vertical-align: top;
            }
            .table-wrapper tbody tr:hover {
                background: rgba(34,197,94,.06);
            }
            .badge {
                display: inline-flex;
                align-items: center;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 600;
            }
            .badge.paid { background: rgba(16,185,129,.15); color: #0f766e; }
            .badge.unpaid { background: rgba(239,68,68,.15); color: #be123c; }
            .action-button {
                padding: 8px 12px;
                border-radius: 10px;
                border: none;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
            }
            .action-button.pay { background: #16a34a; color: #fff; }
            .action-button.unpay { background: #f97316; color: #fff; }
            .action-input {
                width: 100%;
                border: 1px solid #cbd5f5;
                border-radius: 8px;
                padding: 6px 8px;
                font-size: 13px;
                margin-top: 6px;
            }
            .actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
                min-width: 190px;
            }
            .file-link {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 10px;
                border-radius: 8px;
                border: 1px solid #cbd5f5;
                text-decoration: none;
                color: #0369a1;
                font-weight: 600;
            }
            .file-link:hover { background: rgba(3,105,161,.08); }
            @media (max-width: 768px) {
                .actions { min-width: unset; }
                .action-button { font-size: 13px; }
            }
        </style>

        <div class="iade-dashboard">
            <div class="iade-shell">
                <h1><i class="fas fa-hand-holding-usd me-2"></i>İade Talepleri Panosu</h1>
                <p class="subtitle">Üyelerinizin gider iadelerini hızlıca görüntüleyin, filtreleyin ve ödenme durumunu güncelleyin.</p>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="label">Toplam Talep</div>
                        <div class="value"><?php echo (int)($stats['toplam'] ?? 0); ?></div>
                        <div class="sub">Son 200 kayıt listelenir</div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Ödenenler</div>
                        <div class="value text-success"><?php echo (int)($stats['odendi_adet'] ?? 0); ?></div>
                        <div class="sub"><?php echo number_format((float)($stats['odendi_tutar'] ?? 0), 2, ',', '.'); ?> €</div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Ödenmeyenler</div>
                        <div class="value text-danger"><?php echo (int)($stats['odenmedi_adet'] ?? 0); ?></div>
                        <div class="sub"><?php echo number_format((float)($stats['odenmedi_tutar'] ?? 0), 2, ',', '.'); ?> €</div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Son Güncelleme</div>
                        <div class="value" style="font-size:18px;"><?php echo date('d.m.Y H:i'); ?></div>
                        <div class="sub">Sayfa yenileme zamanı</div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> mb-3">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="filters">
                    <div>
                        <label>Yıl</label>
                        <select id="yearFilter">
                            <option value="">Hepsi</option>
                            <?php foreach ($years as $yil): ?>
                                <?php $yValue = (int)$yil['yil']; ?>
                                <option value="<?php echo $yValue; ?>" <?php echo $yearFilter === $yValue ? 'selected' : ''; ?>>
                                    <?php echo $yValue; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Ay</label>
                        <select id="monthFilter">
                            <option value="">Hepsi</option>
                            <?php
                                $aylar = [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
                                foreach ($aylar as $num => $isim):
                            ?>
                                <option value="<?php echo $num; ?>" <?php echo $monthFilter === $num ? 'selected' : ''; ?>>
                                    <?php echo $isim; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Üye</label>
                        <select id="uyeFilter">
                            <option value="">Hepsi</option>
                            <?php foreach ($uyeOptions as $uyeAd): ?>
                                <?php $fullName = $uyeAd['adsoyad']; ?>
                                <option value="<?php echo htmlspecialchars($fullName); ?>" <?php echo $uyeFilter === $fullName ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($fullName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Durum</label>
                        <select id="statusFilter">
                            <option value="">Hepsi</option>
                            <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Ödendi</option>
                            <option value="unpaid" <?php echo $statusFilter === 'unpaid' ? 'selected' : ''; ?>>Ödenmedi</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button class="action-button pay" type="button" onclick="applyIadeFilters()">Filtrele</button>
                        <button class="action-button unpay" type="button" onclick="resetIadeFilters()">Temizle</button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Üye</th>
                                <th>Başlık / Açıklama</th>
                                <th>Tutar</th>
                                <th>Dosya</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($talepList)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Gösterilecek talep bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($talepList as $talep): ?>
                                    <?php $isPaid = $talep['durum'] === 'odenmistir'; ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($talep['talep_id'], 4, '0', STR_PAD_LEFT); ?></strong><br>
                                            <span class="text-muted small"><?php echo date('d.m.Y H:i', strtotime($talep['olusturma_tarihi'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($talep['uye_adi']); ?></div>
                                            <div class="small text-muted">
                                                <?php if ($talep['email']): ?>
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($talep['email']); ?>
                                                <?php endif; ?>
                                                <?php if ($talep['telefon']): ?>
                                                    <span class="ms-2"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($talep['telefon']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($talep['baslik']); ?></strong>
                                            <?php if (!empty($talep['aciklama'])): ?>
                                                <div class="small text-muted mt-1">
                                                    <?php echo nl2br(htmlspecialchars($talep['aciklama'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-success">
                                            <?php echo number_format((float)$talep['tutar'], 2, ',', '.'); ?> €
                                        </td>
                                        <td>
                                            <?php if ($talep['dosya_yolu']): ?>
                                                <a href="<?php echo htmlspecialchars($talep['dosya_yolu']); ?>" target="_blank" class="file-link">
                                                    <i class="fas fa-paperclip"></i> Ek Aç
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Ek yok</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $isPaid ? 'paid' : 'unpaid'; ?>">
                                                <?php echo $isPaid ? 'Ödendi' : 'Ödenmedi'; ?>
                                            </span>
                                            <?php if ($talep['onay_tarihi']): ?>
                                                <div class="small text-muted mt-1">
                                                    <?php echo date('d.m.Y H:i', strtotime($talep['onay_tarihi'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($talep['onay_aciklama']): ?>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($talep['onay_aciklama']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <?php 
                                                // Yetki Kontrolü: 
                                                // Eğer AT birimi başkanı ise veya Super Admin ise -> ÖDEME butonlarını gör
                                                // Diğer birim başkanları -> SADECE ONAY/RED butonlarını gör
                                                
                                                // Bu sayfaya sadece yetkililer giriyor zaten.
                                                // Kullanıcının birim kodunu bulalım (Session veya DB'den)
                                                // Basitlik için: Session'da yoksa sorgulayalım.
                                                // Not: Bu sayfa zaten BYK filtreli çalışıyor. $user['byk_id'] kullanıcının kendi birimi.
                                                
                                                // Kullanıcının birim kodunu al
                                                $userBykCode = '';
                                                try {
                                                    $bykInfo = $db->fetch("SELECT byk_kodu FROM byk WHERE byk_id = ?", [$user['byk_id']]);
                                                    $userBykCode = $bykInfo['byk_kodu'] ?? '';
                                                } catch (Exception $e) {}

                                                $isAtUnit = ($userBykCode === 'AT');
                                                $canPay = ($isAtUnit || $user['role'] === 'super_admin');
                                                ?>

                                                <form method="post">
                                                    <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="talep_id" value="<?php echo $talep['talep_id']; ?>">
                                                    
                                                    <?php if ($canPay): ?>
                                                        <!-- AT veya Super Admin: ÖDEME İŞLEMLERİ -->
                                                        <input type="hidden" name="action" value="<?php echo $isPaid ? 'mark_unpaid' : 'mark_paid'; ?>">
                                                        <button type="submit" class="action-button <?php echo $isPaid ? 'unpay' : 'pay'; ?>">
                                                            <i class="fas <?php echo $isPaid ? 'fa-undo' : 'fa-check'; ?> me-1"></i>
                                                            <?php echo $isPaid ? 'Ödenmedi Yap' : 'Ödendi Olarak İşaretle'; ?>
                                                        </button>
                                                    <?php else: ?>
                                                        <!-- Diğer Muhasebe Başkanları: ONAY İŞLEMLERİ -->
                                                        <?php if ($talep['durum'] === 'beklemede'): ?>
                                                            <button type="button" class="action-button pay mb-2" onclick="openApprovalModal(<?php echo $talep['talep_id']; ?>, 'approve')">
                                                                <i class="fas fa-check me-1"></i>Onayla (AT'ye Gönder)
                                                            </button>
                                                            <button type="button" class="action-button unpay" onclick="openApprovalModal(<?php echo $talep['talep_id']; ?>, 'reject')">
                                                                <i class="fas fa-times me-1"></i>Reddet (Düzeltme İste)
                                                            </button>
                                                        <?php else: ?>
                                                            <div class="text-muted text-center small border p-2 rounded">
                                                                İşlem Yapıldı<br>
                                                                (<?php echo ucfirst($talep['durum']); ?>)
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($canPay): ?>
                                                        <input type="text" name="aciklama" class="action-input" placeholder="Not (opsiyonel)">
                                                    <?php endif; ?>
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
    </div>
</main>

<!-- Action Modal for Non-AT Units -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalLabel">İşlem Onayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="<?php echo $csrfTokenName; ?>" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="talep_id" id="modalTalepId">
                <input type="hidden" name="action" id="modalAction">
                
                <p id="modalMessage"></p>
                
                <div class="mb-3">
                    <label class="form-label">Açıklama / Not</label>
                    <textarea name="aciklama" class="form-control" rows="3" placeholder="İsterseniz bir not ekleyebilirsiniz..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Onayla</button>
            </div>
        </form>
    </div>
</div>

<script>
    function applyIadeFilters() {
        const params = new URLSearchParams(window.location.search);
        const year = document.getElementById('yearFilter').value;
        const month = document.getElementById('monthFilter').value;
        const uye = document.getElementById('uyeFilter').value;
        const status = document.getElementById('statusFilter').value;

        if (year) params.set('year', year); else params.delete('year');
        if (month) params.set('month', month); else params.delete('month');
        if (uye) params.set('uye', uye); else params.delete('uye');
        if (status) params.set('status', status); else params.delete('status');

        window.location.search = params.toString();
    }

    function resetIadeFilters() {
        window.location.href = window.location.pathname;
    }

    function openApprovalModal(talepId, action) {
        const modal = new bootstrap.Modal(document.getElementById('actionModal'));
        document.getElementById('modalTalepId').value = talepId;
        document.getElementById('modalAction').value = action;
        
        const label = document.getElementById('actionModalLabel');
        const message = document.getElementById('modalMessage');
        const btn = document.getElementById('modalSubmitBtn');
        
        if (action === 'approve') {
            label.innerText = 'Talebi Onayla';
            label.className = 'modal-title text-success';
            message.innerText = 'Bu talebi onaylamak ve AT Muhasebesine iletmek üzeresiniz. Onaylıyor musunuz?';
            btn.className = 'btn btn-success';
            btn.innerText = 'Onayla ve Gönder';
        } else {
            label.innerText = 'Talebi Reddet';
            label.className = 'modal-title text-danger';
            message.innerText = 'Bu talebi reddederek düzeltme isteyeceksiniz. Emin misiniz?';
            btn.className = 'btn btn-danger';
            btn.innerText = 'Reddet';
        }
        
        modal.show();
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


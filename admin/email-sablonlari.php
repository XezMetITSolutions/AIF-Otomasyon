<?php
/**
 * Ana Yönetici - E-posta Şablonları
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'E-posta Şablonları';
$success = '';
$error = '';

// Tabloyu otomatik oluştur/güncelle
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS `email_sablonlari` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `kod` VARCHAR(50) UNIQUE NOT NULL,
            `baslik` VARCHAR(100) NOT NULL,
            `konu` VARCHAR(255) NOT NULL,
            `icerik` TEXT NOT NULL,
            `degiskenler` TEXT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Varsayılan şablonları tanımla
    $emailLayoutStart = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>@import url(\'https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&display=swap\');</style></head><body style="margin:0;padding:0;background-color:#f0f4f8;font-family:\'Outfit\',\'Segoe UI\',sans-serif;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td align="center" style="padding:40px 10px;"><table role="presentation" width="100%" max-width="600" style="width:100%;max-width:600px;background-color:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.08);">';
    $emailLayoutEnd = '<tr><td align="center" style="background-color:#ffffff;padding:40px;border-top:1px solid #f1f5f9;"><div style="margin-bottom:20px;"><img src="{{app_url}}/assets/img/logo.png" alt="Logo" style="height:30px;opacity:0.6;"></div><p style="color:#94a3b8;font-size:13px;margin:0;">Bu e-posta <strong>{{app_name}}</strong> tarafından otomatik olarak gönderilmiştir.<br>© {{year}} Tüm Hakları Saklıdır.</p></td></tr></table></td></tr></table></body></html>';

    $headerGreen = '<tr><td align="center" style="background:linear-gradient(135deg, #00b894 0%, #00936F 100%);padding:60px 40px;"><div style="background:rgba(255,255,255,0.2);width:60px;height:60px;border-radius:20px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;color:white;font-size:30px;">✓</div><h1 style="color:#ffffff;margin:0;font-size:28px;font-weight:600;letter-spacing:-0.5px;">{{title}}</h1></td></tr>';
    $headerBlue = '<tr><td align="center" style="background:linear-gradient(135deg, #6c5ce7 0%, #0d6efd 100%);padding:60px 40px;"><div style="background:rgba(255,255,255,0.2);width:60px;height:60px;border-radius:20px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;color:white;font-size:30px;">ℹ</div><h1 style="color:#ffffff;margin:0;font-size:28px;font-weight:600;letter-spacing:-0.5px;">{{title}}</h1></td></tr>';
    $headerRed = '<tr><td align="center" style="background:linear-gradient(135deg, #ff7675 0%, #dc3545 100%);padding:60px 40px;"><div style="background:rgba(255,255,255,0.2);width:60px;height:60px;border-radius:20px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;color:white;font-size:30px;">!</div><h1 style="color:#ffffff;margin:0;font-size:28px;font-weight:600;letter-spacing:-0.5px;">{{title}}</h1></td></tr>';

    $invitationBody = $emailLayoutStart . str_replace('{{title}}', 'Toplantı Daveti', $headerGreen) . '<tr><td style="padding:50px 40px;"><p style="font-size:16px;color:#475569;line-height:1.6;">Sayın <strong>{{ad_soyad}}</strong>,<br><br>Sizin katılımınız bizim için değerlidir. Aşağıdaki toplantıya davetlisiniz:</p><div style="background:#f8fafc;padding:30px;border-radius:20px;margin:30px 0;border:1px solid #f1f5f9;"><table width="100%"><tr><td width="30" valign="top" style="padding-top:2px;">📅</td><td><strong style="color:#1e293b;">Konu:</strong><br><span style="color:#64748b;">{{baslik}}</span></td></tr><tr><td height="15"></td></tr><tr><td>⏰</td><td><strong style="color:#1e293b;">Tarih:</strong><br><span style="color:#64748b;">{{tarih}}</span></td></tr><tr><td height="15"></td></tr><tr><td>📍</td><td><strong style="color:#1e293b;">Konum:</strong><br><span style="color:#64748b;">{{konum}}</span></td></tr></table></div>{{gundem_html}}<div style="text-align:center;margin-top:40px;"><a href="{{accept_url}}" style="background:#00b894;color:#fff;padding:16px 35px;text-decoration:none;border-radius:14px;display:inline-block;font-weight:600;box-shadow:0 10px 20px rgba(0,184,148,0.2);">Toplantıya Katıl</a><br><br><a href="{{reject_url}}" style="color:#94a3b8;text-decoration:none;font-size:14px;">Katılamayacağım</a></div></td></tr>' . $emailLayoutEnd;
    $cancellationBody = $emailLayoutStart . str_replace('{{title}}', 'Toplantı İptali', $headerRed) . '<tr><td style="padding:50px 40px;"><p style="font-size:16px;color:#475569;line-height:1.6;">Sayın <strong>{{ad_soyad}}</strong>,<br><br>Planlanan aşağıdaki toplantı maalesef iptal edilmiştir:</p><div style="background:#f8fafc;padding:30px;border-radius:20px;margin:30px 0;border:1px solid #f1f5f9;"><strong>Konu:</strong> {{baslik}}<br><strong>Tarih:</strong> {{tarih}}</div><div style="background:#fff1f1;padding:20px;border-radius:16px;color:#e11d48;border:1px solid #fee2e2;"><strong>İptal Nedeni:</strong><br>{{iptal_nedeni}}</div></td></tr>' . $emailLayoutEnd;
    $newRequestToAdmin = $emailLayoutStart . str_replace('{{title}}', 'Onay Bekleyen Talep', $headerBlue) . '<tr><td style="padding:50px 40px;"><p style="font-size:16px;color:#475569;line-height:1.6;">Sayın Yetkili,<br><br>Sistemde onayınızı bekleyen yeni bir <strong>{{talep_turu}}</strong> kaydı bulunmaktadır.</p><div style="background:#f8fafc;padding:30px;border-radius:20px;margin:30px 0;border:1px solid #f1f5f9;"><p style="margin-top:0;"><strong>Talep Sahibi:</strong> {{ad_soyad}}</p><p style="margin-bottom:0;color:#64748b;">{{detay}}</p></div><div style="text-align:center;"><a href="{{panel_url}}" style="background:#6c5ce7;color:#fff;padding:16px 35px;text-decoration:none;border-radius:14px;display:inline-block;font-weight:600;box-shadow:0 10px 20px rgba(108,92,231,0.2);">İşlemleri Görüntüle</a></div></td></tr>' . $emailLayoutEnd;
    $requestResultToUser = $emailLayoutStart . str_replace('{{title}}', 'Talep Sonucu', $headerGreen) . '<tr><td style="padding:50px 40px;"><p style="font-size:16px;color:#475569;line-height:1.6;">Sayın <strong>{{ad_soyad}}</strong>,<br><br>Yapmış olduğunuz <strong>{{talep_turu}}</strong> talebi sonuçlanmıştır:</p><div style="background:#f8fafc;padding:30px;border-radius:20px;margin:30px 0;border:1px solid #f1f5f9;"><p style="margin-top:0;"><strong>Durum:</strong> <span style="color:#00b894;font-weight:600;">{{durum}}</span></p><p style="margin-bottom:0;color:#64748b;">{{aciklama}}</p></div></td></tr>' . $emailLayoutEnd;
    $passwordReset = $emailLayoutStart . str_replace('{{title}}', 'Şifre Yenileme', $headerBlue) . '<tr><td style="padding:50px 40px;"><p style="font-size:16px;color:#475569;line-height:1.6;">Merhaba,<br><br>Hesabınız için şifre sıfırlama talebinde bulundunuz. Yeni şifrenizi belirlemek için aşağıya tıklayın:</p><div style="text-align:center;margin:40px 0;"><a href="{{reset_url}}" style="background:#6c5ce7;color:#fff;padding:16px 35px;text-decoration:none;border-radius:14px;display:inline-block;font-weight:600;">Şifremi Sıfırla</a></div><p style="color:#94a3b8;font-size:12px;text-align:center;">Bu talebi siz yapmadıysanız bu e-postayı dikkate almayınız. Güvenliğiniz için bu bağlantı 2 saat geçerlidir.</p></td></tr>' . $emailLayoutEnd;
    $welcomeEmail = $emailLayoutStart . str_replace('{{title}}', 'Hoş Geldiniz', $headerGreen) . '<tr><td style="padding:50px 40px;"><p style="font-size:16px;color:#475569;line-height:1.6;">Sayın <strong>{{ad_soyad}}</strong>,<br><br>AİF Otomasyon Ailesine hoş geldiniz! Hesabınız başarıyla oluşturuldu.</p><div style="background:#f8fafc;padding:30px;border-radius:20px;margin:30px 0;border:1px solid #f1f5f9;"><p style="margin-top:0;"><strong>Kullanıcı Adınız:</strong><br>{{email}}</p><p style="margin-bottom:0;"><strong>Erişim Paneli:</strong><br><a href="{{panel_url}}" style="color:#00b894;">{{panel_url}}</a></p></div><p style="color:#64748b;font-size:14px;">Güvenliğiniz için ilk girişten sonra şifrenizi değiştirmenizi öneririz.</p></td></tr>' . $emailLayoutEnd;
    $announcementBody = $emailLayoutStart . str_replace('{{title}}', 'Duyuru', $headerRed) . '<tr><td style="padding:50px 40px;"><h2 style="margin:0 0 20px 0;color:#1e293b;font-size:22px;">{{baslik}}</h2><div style="color:#475569;line-height:1.8;font-size:15px;">{{icerik}}</div><div style="text-align:center;margin-top:40px;"><a href="{{duyuru_url}}" style="background:#dc3545;color:#fff;padding:16px 35px;text-decoration:none;border-radius:14px;display:inline-block;font-weight:600;">Hemen İncele</a></div></td></tr>' . $emailLayoutEnd;

    $varsayilanSablonlar = [
        ['toplanti_daveti', 'Toplantı Davetiyesi', 'Toplantı Daveti: {{baslik}}', $invitationBody, '{{ad_soyad}}, {{baslik}}, {{tarih}}, {{konum}}, {{gundem_html}}, {{accept_url}}, {{reject_url}}, {{app_name}}, {{app_url}}, {{year}}'],
        ['toplanti_iptali', 'Toplantı İptal Bildirimi', 'Toplantı İptal Edildi: {{baslik}}', $cancellationBody, '{{ad_soyad}}, {{baslik}}, {{tarih}}, {{konum}}, {{iptal_nedeni}}, {{app_name}}, {{app_url}}, {{year}}'],
        ['talep_yeni', 'Yeni Onay Bekleyen Talep (Admin)', 'Yeni Talep: {{talep_turu}} - {{ad_soyad}}', $newRequestToAdmin, '{{ad_soyad}}, {{talep_turu}}, {{detay}}, {{panel_url}}, {{app_name}}, {{year}}'],
        ['talep_sonuc', 'Talep Sonucu (Üye)', 'Talebiniz Sonuçlandı: {{talep_turu}}', $requestResultToUser, '{{ad_soyad}}, {{talep_turu}}, {{durum}}, {{aciklama}}, {{app_name}}, {{year}}'],
        ['sifre_sifirlama', 'Şifre Sıfırlama', 'Şifre Sıfırlama Talebi', $passwordReset, '{{reset_url}}, {{app_name}}, {{year}}'],
        ['yeni_kullanici', 'Yeni Üye Hoş Geldiniz', 'AİF Otomasyon Hesabınız Oluşturuldu', $welcomeEmail, '{{ad_soyad}}, {{email}}, {{panel_url}}, {{app_name}}, {{year}}'],
        ['duyuru_yeni', 'Yeni Duyuru Bildirimi', 'Önemli Duyuru: {{baslik}}', $announcementBody, '{{baslik}}, {{icerik}}, {{duyuru_url}}, {{app_name}}, {{year}}']
    ];

    // Varsayılan şablonları kontrol et ve ekle
    foreach ($varsayilanSablonlar as $s) {
        $db->query(
            "INSERT IGNORE INTO email_sablonlari (kod, baslik, konu, icerik, degiskenler) VALUES (?, ?, ?, ?, ?)",
            [$s[0], $s[1], $s[2], $s[3], $s[4]]
        );
    }

    // Ekstra kontrol: Eğer tablo boş olmasa bile eksik şablonları ekle
    foreach ($varsayilanSablonlar as $s) {
        $exists = $db->fetch("SELECT id FROM email_sablonlari WHERE kod = ?", [$s[0]]);
        if (!$exists) {
            $db->query(
                "INSERT INTO email_sablonlari (kod, baslik, konu, icerik, degiskenler) VALUES (?, ?, ?, ?, ?)",
                [$s[0], $s[1], $s[2], $s[3], $s[4]]
            );
        }
    }
} catch (Exception $e) {
}

// Şablon Kaydetme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    try {
        $db->query(
            "UPDATE email_sablonlari SET konu = ?, icerik = ? WHERE kod = ?",
            [$_POST['konu'], $_POST['icerik'], $_POST['kod']]
        );
        $success = 'Şablon başarıyla güncellendi.';
    } catch (Exception $e) {
        $error = 'Hata: ' . $e->getMessage();
    }
}

$sablonlar = $db->fetchAll("SELECT * FROM email_sablonlari");
$kullanicilar = $db->fetchAll("SELECT kullanici_id, ad, soyad, email FROM kullanicilar WHERE aktif = 1 ORDER BY ad, soyad");

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-envelope-open-text me-2"></i>E-posta Şablonları
            </h1>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Şablon Listesi</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($sablonlar as $s): ?>
                            <a href="?kod=<?php echo $s['kod']; ?>"
                                class="list-group-item list-group-item-action <?php echo (isset($_GET['kod']) && $_GET['kod'] == $s['kod']) ? 'active' : ''; ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($s['baslik']); ?>
                                    </h6>
                                    <small>
                                        <?php echo date('d.m.H:i', strtotime($s['updated_at'])); ?>
                                    </small>
                                </div>
                                <small class="text-muted d-block">
                                    <?php echo htmlspecialchars($s['konu']); ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <?php
                $seciliSablon = null;
                if (isset($_GET['kod'])) {
                    foreach ($sablonlar as $s) {
                        if ($s['kod'] == $_GET['kod']) {
                            $seciliSablon = $s;
                            break;
                        }
                    }
                }

                if ($seciliSablon):
                    ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <?php echo htmlspecialchars($seciliSablon['baslik']); ?> Düzenle
                            </h6>
                            <span class="badge bg-light text-dark border">Kod:
                                <?php echo $seciliSablon['kod']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="kod" value="<?php echo $seciliSablon['kod']; ?>">

                                <div class="mb-3">
                                    <label class="form-label">E-posta Konusu (Subject)</label>
                                    <input type="text" class="form-control" name="konu"
                                        value="<?php echo htmlspecialchars($seciliSablon['konu']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">E-posta İçeriği (HTML)</label>
                                    <textarea name="icerik" class="form-control" rows="15"
                                        id="templateEditor"><?php echo htmlspecialchars($seciliSablon['icerik']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label d-block">Kullanılabilir Değişkenler</label>
                                    <div class="p-2 bg-light border rounded small">
                                        <?php
                                        $tags = explode(',', $seciliSablon['degiskenler']);
                                        foreach ($tags as $tag) {
                                            echo '<code class="me-2 cursor-pointer copy-tag" title="Tıkla kopyala" style="cursor:pointer">' . trim($tag) . '</code>';
                                        }
                                        ?>
                                    </div>
                                    <small class="text-muted">Bu etiketler gönderim sırasında gerçek verilerle yer
                                        değiştirecektir.</small>
                                </div>

                                <div class="card bg-light border-0 mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold mb-3"><i class="fas fa-vial me-2"></i>Test Gönderimi
                                        </h6>
                                        <div class="row align-items-end g-2">
                                            <div class="col-sm-8">
                                                <label class="form-label small text-muted">Test Alıcısı Seçin</label>
                                                <select id="testUserSelect" class="form-select">
                                                    <option value="">Lütfen kullanıcı seçin...</option>
                                                    <?php foreach ($kullanicilar as $ku): ?>
                                                        <option value="<?php echo $ku['kullanici_id']; ?>"
                                                            data-email="<?php echo $ku['email']; ?>"
                                                            data-name="<?php echo $ku['ad'] . ' ' . $ku['soyad']; ?>">
                                                            <?php echo htmlspecialchars($ku['ad'] . ' ' . $ku['soyad'] . ' (' . $ku['email'] . ')'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-sm-4">
                                                <button type="button" id="btnSendTest" class="btn btn-dark w-100">
                                                    <i class="fas fa-paper-plane me-2"></i>Test Gönder
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end border-top pt-3">
                                    <button type="button" id="btnPreview" class="btn btn-outline-info px-4 me-2">
                                        <i class="fas fa-eye me-2"></i>Önizleme
                                    </button>
                                    <button type="submit" name="save_template" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-2"></i>Şablonu Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Preview Modal -->
                    <div class="modal fade" id="previewModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">E-posta Önizleme</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <div class="bg-light p-3 border-bottom">
                                        <strong>Konu:</strong> <span id="previewSubjectText"></span>
                                    </div>
                                    <iframe id="previewFrame" style="width: 100%; height: 600px; border: 0;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm text-center p-5">
                        <div class="card-body">
                            <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                            <h5>Şablon Seçiniz</h5>
                            <p class="text-muted">Düzenlemek istediğiniz e-posta şablonunu sol taraftaki listeden seçiniz.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    function initTemplatePage() {
        if (typeof $ === 'undefined') {
            console.log('jQuery not yet loaded, retrying...');
            setTimeout(initTemplatePage, 100);
            return;
        }

        $(document).off('click', '.copy-tag').on('click', '.copy-tag', function () {
            const text = $(this).text();
            navigator.clipboard.writeText(text);
            alert('Kopyalandı: ' + text);
        });

        $(document).off('click', '#btnPreview').on('click', '#btnPreview', function () {
            const content = $('#templateEditor').val();
            const subject = $('input[name="konu"]').val();
            const btn = $(this);

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Hazırlanıyor...');

            $.ajax({
                url: '/admin/ajax_email_preview.php',
                method: 'POST',
                data: { content: content, subject: subject },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#previewSubjectText').text(response.subject);
                        const iframe = document.getElementById('previewFrame');
                        const iframeDoc = iframe.contentWindow.document;
                        iframeDoc.open();
                        iframeDoc.write(response.html);
                        iframeDoc.close();

                        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
                        modal.show();
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function () {
                    alert('Önizleme oluşturulurken bir sistem hatası oluştu.');
                },
                complete: function () {
                    btn.prop('disabled', false).html('<i class="fas fa-eye me-2"></i>Önizleme');
                }
            });
        });

        $(document).off('click', '#btnSendTest').on('click', '#btnSendTest', function () {
            const userId = $('#testUserSelect').val();
            const kod = $('input[name="kod"]').val();
            const content = $('#templateEditor').val();
            const subject = $('input[name="konu"]').val();
            const btn = $(this);

            if (!userId) {
                alert('Lütfen bir test alıcısı seçin.');
                return;
            }

            if (!confirm('Bu şablonu seçili kullanıcıya test e-postası olarak göndermek istiyor musunuz?')) {
                return;
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Gönderiliyor...');

            $.ajax({
                url: '/admin/ajax_send_test_email.php',
                method: 'POST',
                data: {
                    user_id: userId,
                    kod: kod,
                    content: content,
                    subject: subject
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert('Test e-postası başarıyla gönderildi.');
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function () {
                    alert('E-posta gönderilirken bir sistem hatası oluştu.');
                },
                complete: function () {
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Test Gönder');
                }
            });
        });
    }

    // Hem normal yükleme hem SPA yüklemesi için çalıştır
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTemplatePage);
    } else {
        initTemplatePage();
    }
    document.addEventListener('page:loaded', initTemplatePage);
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>
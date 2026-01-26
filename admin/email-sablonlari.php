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

    // Varsayılan şablonları kontrol et ve ekle
    $check = $db->fetch("SELECT count(*) as cnt FROM email_sablonlari");
    if ($check['cnt'] == 0) {
        $invitationBody = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Toplantı Daveti</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <tr>
                        <td align="center" style="background-color: #00936F; padding: 35px 20px;">
                            <img src="{{app_url}}/assets/img/AIF.png" alt="Logo" style="height: 48px; filter: brightness(0) invert(1);">
                            <h1 style="color: #ffffff; margin: 20px 0 0 0; font-size: 24px;">Toplantı Daveti</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #495057; font-size: 16px;">Sayın <strong>{{ad_soyad}}</strong>,<br><br>Aşağıda detayları yer alan toplantıya katılımınız beklenmektedir.</p>
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <strong>Konu:</strong> {{baslik}}<br>
                                        <strong>Tarih:</strong> {{tarih}}<br>
                                        <strong>Konum:</strong> {{konum}}
                                    </td>
                                </tr>
                            </table>
                            {{gundem_html}}
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="{{accept_url}}" style="background-color: #198754; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">✅ Katılıyorum</a>
                                <br><br>
                                <a href="{{reject_url}}" style="color: #dc3545; text-decoration: none; font-size: 14px;">Katılamayacağım (Reddet)</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background-color: #f8f9fa; padding: 25px; border-top: 1px solid #e9ecef;">
                            <p style="color: #adb5bd; font-size: 12px;">© {{year}} {{app_name}}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        $cancellationBody = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Toplantı İptali</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <tr>
                        <td align="center" style="background-color: #DC3545; padding: 35px 20px;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Toplantı İptal Edildi</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="color: #495057; font-size: 16px;">Sayın <strong>{{ad_soyad}}</strong>,<br><br>Daha önce planlanan aşağıdaki toplantı ne yazık ki <strong>iptal edilmiştir</strong>.</p>
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid #DC3545; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <strong>Konu:</strong> {{baslik}}<br>
                                        <strong>Tarih:</strong> {{tarih}}<br>
                                        <strong>Konum:</strong> {{konum}}
                                    </td>
                                </tr>
                            </table>
                            <div style="background-color: #fff3cd; border: 1px solid #ffeeba; padding: 20px; border-radius: 8px;">
                                <strong>İptal Nedeni:</strong><br>{{iptal_nedeni}}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background-color: #f8f9fa; padding: 25px; border-top: 1px solid #e9ecef;">
                            <p style="color: #adb5bd; font-size: 12px;">© {{year}} {{app_name}}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        $varsayilanSablonlar = [
            [
                'kod' => 'toplanti_daveti',
                'baslik' => 'Toplantı Davetiyesi',
                'konu' => 'Toplantı Daveti: {{baslik}}',
                'icerik' => $invitationBody,
                'degiskenler' => '{{ad_soyad}}, {{baslik}}, {{tarih}}, {{konum}}, {{gundem_html}}, {{accept_url}}, {{reject_url}}, {{app_name}}, {{app_url}}, {{year}}'
            ],
            [
                'kod' => 'toplanti_iptali',
                'baslik' => 'Toplantı İptal Bildirimi',
                'konu' => 'Toplantı İptal Edildi: {{baslik}}',
                'icerik' => $cancellationBody,
                'degiskenler' => '{{ad_soyad}}, {{baslik}}, {{tarih}}, {{konum}}, {{iptal_nedeni}}, {{app_name}}, {{app_url}}, {{year}}'
            ]
        ];

        foreach ($varsayilanSablonlar as $s) {
            $db->query(
                "INSERT INTO email_sablonlari (kod, baslik, konu, icerik, degiskenler) VALUES (?, ?, ?, ?, ?)",
                [$s['kod'], $s['baslik'], $s['konu'], $s['icerik'], $s['degiskenler']]
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

                                <div class="text-end">
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
    $(document).on('click', '.copy-tag', function () {
        const text = $(this).text();
        navigator.clipboard.writeText(text);
        alert('Kopyalandı: ' + text);
    });

    $(document).on('click', '#btnPreview', function () {
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
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>
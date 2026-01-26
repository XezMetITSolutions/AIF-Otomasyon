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
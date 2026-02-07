<?php
/**
 * Uygulama Tanıtım ve Bilgilendirme Sayfası
 * Kullanıcıların sistem özelliklerini görebileceği ve tanıtım mailini inceleyebileceği sayfa.
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Mail.php';

Middleware::requireAuth();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$message = '';
$messageType = '';

// E-posta İçeriği Hazırlığı
$appName = Config::get('app_name', 'AİF Otomasyon');
$appUrl = rtrim(Config::get('app_url', 'https://aifnet.islamfederasyonu.at'), '/');
$userName = $user['name'];

// HTML Email Şablonu
$emailSubject = "🚀 AİF Otomasyon Sistemi: Dijital Dönüşüm Başladı!";
$emailContent = <<<html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>AİF Otomasyon Tanıtım</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f6f9; }
        .email-container { max-width: 650px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #00936F 0%, #007a5e 100%); padding: 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px 30px; }
        .feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 25px; }
        .feature-item { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #00936F; }
        .feature-title { font-weight: bold; color: #00936F; margin-bottom: 5px; display: block; }
        .btn-action { display: inline-block; background-color: #00936F; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; text-align: center; }
        .footer { background-color: #f1f3f5; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e9ecef; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>{$appName}</h1>
            <p style="margin: 10px 0 0; opacity: 0.9;">Kurumsal Süreçleriniz Artık Daha Hızlı ve Kolay</p>
        </div>
        <div class="content">
            <p>Sayın <strong>{$userName}</strong>,</p>
            
            <p>Kurum içi iletişimimizi güçlendirmek, iş süreçlerimizi hızlandırmak ve verimliliğimizi artırmak amacıyla geliştirdiğimiz yeni <strong>Otomasyon Sistemimiz</strong> yayında!</p>
            
            <p>Artık tüm işlemlerinizi tek bir platform üzerinden, hem bilgisayarınızdan hem de mobil cihazlarınızdan kolayca yönetebilirsiniz.</p>

            <h3 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 30px;">🌟 Neler Yapabilirsiniz?</h3>
            
            <div class="feature-grid">
                <div class="feature-item">
                    <span class="feature-title">📋 Görev & Proje Takibi</span>
                    Projelerinizi yönetin, görev atayın, ilerleme durumlarını anlık takip edin.
                </div>
                <div class="feature-item">
                    <span class="feature-title">📅 Akıllı Takvim</span>
                    Toplantıları planlayın, etkinlikleri görün ve otomatik hatırlatmalar alın.
                </div>
                <div class="feature-item">
                    <span class="feature-title">💰 Harcama Yönetimi</span>
                    Masraf fişlerinizi yükleyin, onay süreçlerini dijitalden takip edin.
                </div>
                <div class="feature-item">
                    <span class="feature-title">📝 İzin İşlemleri</span>
                    İzin taleplerinizi saniyeler içinde oluşturun ve onay durumunu görün.
                </div>
                <div class="feature-item">
                    <span class="feature-title">📦 Demirbaş Takibi</span>
                    Zimmetinizdeki eşyaları görüntüleyin veya yeni demirbaş talep edin.
                </div>
                <div class="feature-item">
                    <span class="feature-title">📢 Duyurular</span>
                    Kurum içi önemli gelişmelerden anında haberdar olun.
                </div>
            </div>

            <div style="text-align: center; margin-top: 40px;">
                <p>Sistemi hemen keşfetmeye başlamak için:</p>
                <a href="{$appUrl}" class="btn-action">Sisteme Giriş Yap</a>
            </div>
        </div>
        <div class="footer">
            <p>© 2024 {$appName}. Tüm hakları saklıdır.</p>
            <p>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.</p>
        </div>
    </div>
</body>
</html>
html;

// Test Maili Gönderme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    if (class_exists('Mail')) {
        $result = Mail::send($user['email'], $emailSubject, $emailContent);
        if ($result) {
            $message = "Tanıtım e-postası başarıyla <strong>{$user['email']}</strong> adresine gönderildi.";
            $messageType = 'success';
        } else {
            $message = "E-posta gönderilemedi: " . Mail::$lastError;
            $messageType = 'danger';
        }
    } else {
        $message = "Mail sınıfı bulunamadı.";
        $messageType = 'danger';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <div class="sidebar-wrapper"><?php include __DIR__ . '/../includes/sidebar.php'; ?></div>
    
    <main class="main-content">
        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><i class="fas fa-envelope-open-text me-2"></i>Uygulama Tanıtım Metni</h1>
                    <p class="text-muted mb-0">Sistem tanıtım e-postası önizlemesi ve gönderimi.</p>
                </div>
                <div>
                    <form method="POST">
                        <input type="hidden" name="send_test" value="1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Kendime Test Gönder
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow border-0">
                        <div class="card-header bg-light py-3">
                            <div class="row align-items-center">
                                <div class="col-2 text-muted text-end fw-bold">Konu:</div>
                                <div class="col-10"><?php echo $emailSubject; ?></div>
                            </div>
                            <div class="row align-items-center mt-2">
                                <div class="col-2 text-muted text-end fw-bold">Alıcı:</div>
                                <div class="col-10"><?php echo $user['name']; ?> &lt;<?php echo $user['email']; ?>&gt;</div>
                            </div>
                        </div>
                        <div class="card-body p-0 bg-secondary bg-opacity-10 d-flex justify-content-center">
                            <!-- Email Preview Wrapper -->
                            <div class="shadow-sm m-4" style="max-width: 650px; width: 100%;">
                                <?php echo $emailContent; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

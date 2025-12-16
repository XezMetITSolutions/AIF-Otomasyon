<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Mail.php';

Middleware::requireSuperAdmin();

$pageTitle = 'Mail Test';
$auth = new Auth();
$user = $auth->getUser();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['email'] ?? '';
    
    if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
        try {
            $subject = 'AIF Otomasyon - SMTP Test';
            $body = '<h1>SMTP Test</h1><p>Bu bir test e-postasıdır.</p><p>Zaman: ' . date('d.m.Y H:i:s') . '</p>';
            
            if (Mail::send($to, $subject, $body)) {
                $message = 'Test e-postası başarıyla gönderildi!';
            } else {
                $error = 'E-posta gönderilemedi. Logları kontrol edin.';
            }
        } catch (Exception $e) {
            $error = 'Hata: ' . $e->getMessage();
        }
    } else {
        $error = 'Geçersiz e-posta adresi.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-paper-plane me-2"></i>SMTP Test Paneli</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Test Edilecek E-posta Adresi</label>
                                <input type="email" name="email" class="form-control" placeholder="ornek@domain.com" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Test Gönder
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-muted small">
                        SMTP Ayarları: <?php 
                        $config = require __DIR__ . '/../config/mail.php';
                        echo $config['host'] . ':' . $config['port'] . ' (' . $config['secure'] . ')'; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

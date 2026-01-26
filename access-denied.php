<?php
/**
 * Erişim Reddedildi Sayfası
 */
require_once __DIR__ . '/includes/init.php';

$auth = new Auth();
$user = $auth->getUser();

$pageTitle = 'Erişim Reddedildi';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - AIF Otomasyon Sistemi</title>
    
    <!-- Bootstrap 5.3.0 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .access-denied-card {
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card access-denied-card text-center">
                    <div class="card-body p-5">
                        <i class="fas fa-ban fa-5x text-danger mb-4"></i>
                        <h1 class="display-4 fw-bold text-danger mb-3">Erişim Reddedildi</h1>
                        <p class="lead text-muted mb-4">
                            Bu sayfaya erişim yetkiniz bulunmamaktadır.
                        </p>
                        <p class="text-muted mb-4">
                            Gerekli yetkiye sahip değilsiniz veya bu içeriğe erişim izniniz yok.
                            Eğer bu sayfaya erişebilmeniz gerektiğini düşünüyorsanız, lütfen sistem yöneticinizle iletişime geçin.
                        </p>
                        
                        <div class="d-flex justify-content-center gap-3">
                            <?php if ($user): ?>
                                <?php
                                $dashboardUrl = '/';
                                if ($user['role'] === 'super_admin') {
                                    $dashboardUrl = '/admin/dashboard.php';
                                } elseif ($user['role'] === 'uye') {
                                    $dashboardUrl = '/panel/baskan_dashboard.php';
                                } elseif ($user['role'] === 'uye') {
                                    $dashboardUrl = '/panel/uye_dashboard.php';
                                }
                                ?>
                                <a href="<?php echo $dashboardUrl; ?>" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Kontrol Paneline Dön
                                </a>
                            <?php else: ?>
                                <a href="/index.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Sayfasına Dön
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5.3.0 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


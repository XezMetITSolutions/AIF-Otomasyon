<?php
/**
 * İlk Giriş Şifre Değiştirme Sayfası
 */
require_once __DIR__ . '/includes/init.php';

$auth = new Auth();
$error = '';
$success = '';

// Şifre değiştirme zorunluluğu kontrolü
if (!isset($_SESSION['requires_password_change']) || $_SESSION['requires_password_change'] !== true) {
    if ($auth->checkAuth()) {
        header('Location: /' . ($auth->getUser()['role'] === 'super_admin' ? 'admin' : ($auth->getUser()['role'] === 'uye' ? 'uye' : 'uye')) . '/dashboard.php');
        exit;
    } else {
        header('Location: /index.php');
        exit;
    }
}

$userId = $_SESSION['temp_user_id'] ?? null;
if (!$userId) {
    header('Location: /index.php');
    exit;
}

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Şifre alanları boş bırakılamaz.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Şifre en az 8 karakter olmalıdır.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        if ($auth->changePasswordFirstLogin($userId, $newPassword)) {
            $success = 'Şifreniz başarıyla değiştirildi. Yönlendiriliyorsunuz...';
            // 2 saniye sonra dashboard'a yönlendir
            header('refresh:2;url=/' . ($auth->getUser()['role'] === 'super_admin' ? 'admin' : ($auth->getUser()['role'] === 'uye' ? 'uye' : 'uye')) . '/dashboard.php');
        } else {
            $error = 'Şifre değiştirme sırasında bir hata oluştu.';
        }
    }
}

$pageTitle = 'Şifre Değiştir';
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
        .change-password-card {
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card change-password-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-key fa-3x text-warning mb-3"></i>
                            <h2 class="fw-bold">İlk Giriş Şifre Değiştirme</h2>
                            <p class="text-muted">Güvenliğiniz için lütfen şifrenizi değiştirin</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="changePasswordForm">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Şifre en az 8 karakter olmalıdır.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Şifreyi Değiştir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5.3.0 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Şifre göster/gizle
        $('#toggleNewPassword').click(function() {
            const password = $('#new_password');
            const icon = $(this).find('i');
            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                password.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        $('#toggleConfirmPassword').click(function() {
            const password = $('#confirm_password');
            const icon = $(this).find('i');
            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                password.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Form doğrulama
        $('#changePasswordForm').on('submit', function(e) {
            const newPassword = $('#new_password').val();
            const confirmPassword = $('#confirm_password').val();
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Şifre en az 8 karakter olmalıdır.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor.');
                return false;
            }
        });
    </script>
</body>
</html>


<?php
/**
 * Giriş Sayfası
 * AIF Otomasyon Sistemi
 */
require_once __DIR__ . '/includes/init.php';

$auth = new Auth();
$error = '';
$success = '';

// Zaten giriş yapılmışsa yönlendir
if ($auth->checkAuth()) {
    $user = $auth->getUser();
    $redirectPath = '/';
    if ($user['role'] === 'super_admin') {
        $redirectPath = '/admin/dashboard.php';
    } elseif ($user['role'] === 'baskan') {
        $redirectPath = '/baskan/dashboard.php';
    } elseif ($user['role'] === 'uye') {
        $redirectPath = '/uye/dashboard.php';
    }
    header('Location: ' . $redirectPath);
    exit;
}

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'E-posta ve şifre alanları boş bırakılamaz.';
    } else {
        $result = $auth->login($email, $password, $remember);
        
        if ($result === true) {
            $user = $auth->getUser();
            $redirectPath = '/';
            if ($user['role'] === 'super_admin') {
                $redirectPath = '/admin/dashboard.php';
            } elseif ($user['role'] === 'baskan') {
                $redirectPath = '/baskan/dashboard.php';
            } elseif ($user['role'] === 'uye') {
                $redirectPath = '/uye/dashboard.php';
            }
            header('Location: ' . $redirectPath);
            exit;
        } elseif ($result === 'password_change_required') {
            header('Location: /change-password.php');
            exit;
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
    }
}

$pageTitle = 'Giriş Yap';
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
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                            <h2 class="fw-bold">AIF Otomasyon Sistemi</h2>
                            <p class="text-muted">Lütfen giriş yapın</p>
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
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Beni hatırla
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </button>
                            
                            <div class="text-center">
                                <a href="/forgot-password.php" class="text-decoration-none">Şifremi Unuttum</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-3 text-white">
                    <small>&copy; <?php echo date('Y'); ?> AIF Otomasyon Sistemi v1.0.1</small>
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
        $('#togglePassword').click(function() {
            const password = $('#password');
            const icon = $(this).find('i');
            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                password.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    </script>
</body>
</html>


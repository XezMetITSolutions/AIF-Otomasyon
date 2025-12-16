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
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
        body.login-page {
            background: url('https://images.unsplash.com/photo-1497294815431-9365093b7331?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding-top: 0 !important; /* Override global style */
        }
        
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 152, 114, 0.9) 0%, rgba(0, 50, 40, 0.8) 100%);
            z-index: 1;
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            padding: 20px;
        }

        .login-card {
            max-width: 450px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-body {
            padding: 3rem !important;
        }

        .logo-container {
            background: white;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .logo-img {
            max-width: 70%;
            height: auto;
        }

        h2 {
            color: white;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        p.text-white-50 {
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
        }

        .btn-toggle-password {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-left: none;
            color: rgba(255, 255, 255, 0.8);
        }

        .btn-toggle-password:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .form-check-input {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .form-check-input:checked {
            background-color: #ffffff;
            border-color: #ffffff;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23009872' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
        }

        .btn-login {
            background: white;
            color: #009872;
            font-weight: 700;
            padding: 12px;
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #f0fdf4;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            color: #007a5e;
        }

        .forgot-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            transition: all 0.3s;
        }

        .forgot-link:hover {
            color: white;
            text-decoration: underline;
        }

        .copyright {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
            margin-top: 1.5rem;
        }

        /* Alert overrides */
        .alert {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            color: #333;
        }
        .alert-danger {
            border-left: 4px solid #dc3545;
        }
        .alert-success {
            border-left: 4px solid #198754;
        }
    </style>
</head>
<body class="login-page">
    <div class="overlay"></div>

    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="logo-container">
                                <img src="/assets/img/AIF.png" alt="AIF Logo" class="logo-img">
                            </div>
                            <h2>AIF Otomasyon</h2>
                            <p class="text-white-50">Yönetim Paneli Girişi</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2 text-danger"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2 text-success"></i><?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="email" class="form-label text-white small text-uppercase fw-bold" style="opacity: 0.8">E-posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="ornek@domain.com" required autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label text-white small text-uppercase fw-bold" style="opacity: 0.8">Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                                    <button class="btn btn-toggle-password" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label text-white" for="remember">
                                        Beni hatırla
                                    </label>
                                </div>
                                <a href="/forgot-password.php" class="forgot-link">Şifremi Unuttum?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-login w-100 mb-3">
                                GİRİŞ YAP <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="text-center copyright">
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


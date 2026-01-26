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
    
    // Rolüne göre yönlendir & Session check
    if ($user['role'] === 'super_admin') {
        session_write_close();
        header('Location: /admin/dashboard.php');
        exit;
    } elseif ($user['role'] === 'uye' || $user['role'] === 'uye') {
        session_write_close();
        header('Location: /panel/dashboard.php');
        exit;
    } else {
        // Tanımsız rol durumunda döngüye girmemek için oturumu kapat
        $auth->logout();
        $error = 'Kullanıcı rolü tanımlanamadı. Lütfen yönetici ile iletişime geçin.';
    }
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
            $redirectPath = null;
            
            if ($user['role'] === 'super_admin') {
                $redirectPath = '/admin/dashboard.php';
            } elseif ($user['role'] === 'uye' || $user['role'] === 'uye') {
                $redirectPath = '/panel/dashboard.php';
            } else {
                $error = 'Geçersiz kullanıcı rolü.'; // Added from instruction
            }
            
            if ($redirectPath) {
                session_write_close();
                header('Location: ' . $redirectPath);
                exit;
            } else {
                $auth->logout();
                // The error message from the instruction takes precedence if set
                if (empty($error)) {
                    $error = 'Kullanıcı rolü için yönlendirme bulunamadı.';
                }
            }
        } elseif ($result === 'password_change_required') {
            header('Location: /change-password.php');
            exit;
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
    }
}

$bgImages = [
    'https://images.unsplash.com/photo-1519817650390-64a93db51149?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Mosque Silhouette Sunset
    'https://images.unsplash.com/photo-1542816417-0983c9c9ad53?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Sheikh Zayed Mosque
    'https://images.unsplash.com/photo-1579294294021-d779f45d1607?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Blue Mosque Istanbul
    'https://images.unsplash.com/photo-1537178082695-1845112fa5b4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Islamic Arches
    'https://images.unsplash.com/photo-1580820716655-22d732c525f6?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Dome Detail
    'https://images.unsplash.com/photo-1596404762512-da7d3536bf30?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Mosque Interior
    'https://images.unsplash.com/photo-1518005052357-e98719a066d2?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // White Marble Mosque
    'https://images.unsplash.com/photo-1558584876-0f8c32d6657e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Qutub Minar / Architecture
    'https://images.unsplash.com/photo-1565552629477-09be335541a9?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Islamic Geometric Pattern
    'https://images.unsplash.com/photo-1601625463687-25541e46da8c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Lanterns / Ramadan
    'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80', // Mosque Courtyard
    'https://images.unsplash.com/photo-1551041777-ed02d7ef14fa?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80'  // Great Mosque of Mecca (General View)
];
$randomBg = $bgImages[array_rand($bgImages)];

$pageTitle = 'Giriş Yap';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - AIFNET</title>
    
    <!-- Bootstrap 5.3.0 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
        :root {
            --primary: #009872;
            --primary-dark: #007a5e;
            --primary-light: #e6f4f1;
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        body {
            background: url('<?php echo $randomBg; ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', 'Inter', sans-serif;
            transition: background-image 0.8s ease-in-out;
            margin: 0;
            overflow: hidden;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 50, 40, 0.85) 100%);
            z-index: 1;
            backdrop-filter: blur(3px);
        }

        .login-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(20, 20, 20, 0.6);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 40px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }

        .logo-circle {
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: rotate(-5deg);
            transition: transform 0.3s ease;
        }
        
        .login-card:hover .logo-circle {
            transform: rotate(0deg) scale(1.05);
        }

        .logo-img {
            max-width: 65%;
            height: auto;
        }

        h1.app-title {
            color: white;
            font-weight: 800;
            font-size: 2rem;
            margin: 0;
            letter-spacing: -0.5px;
            text-align: center;
            background: linear-gradient(to right, #ffffff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        p.app-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-top: 5px;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            font-size: 0.9rem;
            margin-left: 4px;
        }

        .input-group {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 4px;
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 152, 114, 0.15);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            padding-left: 15px;
        }

        .form-control {
            background: transparent;
            border: none;
            color: white;
            padding: 12px 15px;
            font-size: 1rem;
            font-weight: 500;
        }

        .form-control:focus {
            background: transparent;
            box-shadow: none;
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.3);
            font-weight: 400;
        }
        
        /* Autocomplete background fix */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #2a2a2a inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .btn-toggle-password {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            padding-right: 15px;
            transition: color 0.3s;
        }

        .btn-toggle-password:hover {
            color: white;
            background: transparent;
        }

        .form-check-input {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            width: 1.1em;
            height: 1.1em;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .forgot-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .forgot-link:hover {
            color: white;
            text-decoration: none;
        }

        .btn-login {
            background: linear-gradient(135deg, #009872 0%, #00bc8c 100%);
            border: none;
            color: white;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 152, 114, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 152, 114, 0.4);
            background: linear-gradient(135deg, #00ad82 0%, #00d49e 100%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }

        .copyright {
            margin-top: 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.75rem;
            font-weight: 400;
        }

        /* Alerts */
        .alert {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border-left: 3px solid #dc3545;
            color: #ffdae0;
        }
        
        .alert-success {
            background: rgba(25, 135, 84, 0.2);
            border-left: 3px solid #198754;
            color: #d1e7dd;
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="logo-area">
                <div class="logo-circle">
                    <img src="/assets/img/AIF.png" alt="AIF" class="logo-img">
                </div>
                <h1 class="app-title">AIFNET</h1>
                <p class="app-subtitle">Yönetim Paneli Girişi</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="email" class="form-label">E-Posta</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="E-posta adresiniz" required autofocus autocomplete="username">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Şifre</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Şifreniz" required autocomplete="current-password">
                        <button class="btn btn-toggle-password" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label text-white-50" for="remember">
                            Beni hatırla
                        </label>
                    </div>
                    <a href="/forgot-password.php" class="forgot-link">Şifremi unuttum</a>
                </div>
                
                <button type="submit" class="btn btn-login">
                    Giriş Yap
                </button>
            </form>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> AIFNET Otomasyon Sistemi<br>v1.0.1
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


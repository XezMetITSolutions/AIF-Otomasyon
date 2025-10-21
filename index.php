<?php
session_start();

// Eğer zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['username'])) {
    require_once 'admin/auth.php';
    $user = SessionManager::getCurrentUser();
    if ($user) {
        if ($user['role'] === 'superadmin') {
            header('Location: admin/dashboard_superadmin.php');
        } elseif ($user['role'] === 'manager') {
            header('Location: manager/dashboard_manager.php');
        } else {
            header('Location: users/dashboard_member.php');
        }
        exit;
    }
}

// Login işlemi
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gereklidir.';
    } else {
        try {
            require_once 'admin/includes/user_manager_db.php';
            
            error_log("[INDEX] Login attempt for: " . $username);
            
            $user = UserManager::login($username, $password);
            if ($user) {
                error_log("[INDEX] Login successful for: " . $username . " role: " . $user['role']);
                
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                
                // Role göre yönlendirme
                if ($user['role'] === 'superadmin') {
                    error_log("[INDEX] Redirecting to superadmin dashboard");
                    header('Location: admin/dashboard_superadmin.php');
                } elseif ($user['role'] === 'manager') {
                    error_log("[INDEX] Redirecting to manager dashboard");
                    header('Location: manager/dashboard_manager.php');
                } else {
                    error_log("[INDEX] Redirecting to member dashboard");
                    header('Location: users/dashboard_member.php');
                }
                exit;
            } else {
                error_log("[INDEX] Login failed for: " . $username);
                $error = 'Kullanıcı adı veya şifre hatalı.';
            }
        } catch (Exception $e) {
            error_log("[INDEX] Login exception: " . $e->getMessage());
            $error = 'Giriş yapılırken bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Giriş</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #009872;
            --primary-dark: #007a5e;
            --primary-light: #00b085;
            --gradient-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-50px, -50px) rotate(360deg); }
        }

        /* Login Container */
        .login-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 4px 20px rgba(0, 152, 114, 0.3);
        }

        .logo i {
            font-size: 2rem;
            color: white;
        }

        .logo-text h1 {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo-text p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin: 5px 0 0 0;
        }

        /* Form Styles */
        .form-floating {
            margin-bottom: 20px;
        }

        .form-floating > .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 10px;
            padding: 1rem 0.75rem;
            height: auto;
        }

        .form-floating > .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(0, 152, 114, 0.25);
            color: white;
        }

        .form-floating > label {
            color: rgba(255, 255, 255, 0.7);
            padding: 1rem 0.75rem;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary-light);
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Button Styles */
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 152, 114, 0.3);
            color: white;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        /* Alert Styles */
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b7a;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #4ade80;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .login-footer a {
            color: var(--primary-light);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: white;
        }

        /* Loading Animation */
        .loading {
            display: none;
        }

        .loading.show {
            display: inline-block;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .logo {
                width: 60px;
                height: 60px;
            }
            
            .logo i {
                font-size: 1.5rem;
            }
            
            .logo-text h1 {
                font-size: 1.5rem;
            }
        }

        /* Animation */
        .login-container {
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <div class="login-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-building"></i>
            </div>
            <div class="logo-text">
                <h1>AIF Otomasyon</h1>
                <p>Sistem Girişi</p>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" id="loginForm">
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı Adı" required>
                <label for="username">
                    <i class="fas fa-user me-2"></i>Kullanıcı Adı
                </label>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
                <label for="password">
                    <i class="fas fa-lock me-2"></i>Şifre
                </label>
            </div>

            <button type="submit" class="btn btn-login" id="loginBtn">
                <span class="btn-text">
                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                </span>
                <span class="loading">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Giriş yapılıyor...
                </span>
            </button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            <p>&copy; 2024 AIF Otomasyon. Tüm hakları saklıdır.</p>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            
            // Show loading state
            btnText.classList.add('show');
            loading.classList.add('show');
            btn.disabled = true;
            
            // Hide loading after 3 seconds (in case of slow response)
            setTimeout(() => {
                btnText.classList.remove('show');
                loading.classList.remove('show');
                btn.disabled = false;
            }, 3000);
        });

        // Auto-focus on username field
        document.getElementById('username').focus();

        // Enter key handling
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        // Add some interactive effects
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
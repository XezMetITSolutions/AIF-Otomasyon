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
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        :root {
            --primary-color: #009872;
            --primary-dark: #007a5e;
            --primary-light: #00b085;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            margin: 20px;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            color: #333;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 152, 114, 0.25);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: var(--primary-color);
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 152, 114, 0.3);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            transform: none;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-section h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .logo-section p {
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <!-- Logo Section -->
                <div class="logo-section text-center mb-4">
                    <h1>AIF Otomasyon</h1>
                    <p>Yönetim Paneli</p>
                </div>
                
                <!-- Login Form -->
                <div class="login-container">
                    <div class="login-header">
                        <h2><i class="fas fa-sign-in-alt"></i> Giriş Yap</h2>
                    </div>
                    
                    <div class="login-body">
                        <form id="loginForm">
                            <div class="form-group">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Kullanıcı adınızı girin" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Şifrenizi girin" required>
                                </div>
                            </div>
                            
                            <div class="remember-forgot">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">
                                        Beni Hatırla
                                    </label>
                                </div>
                                <a href="#" class="forgot-password">Şifremi Unuttum</a>
                            </div>
                            
                            <button type="submit" class="btn btn-login" id="loginBtn">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-4">
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                        © 2024 AIF Otomasyon. Tüm hakları saklıdır.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Login form submission
            $('#loginForm').submit(function(e) {
                e.preventDefault();
                
                const username = $('#username').val();
                const password = $('#password').val();
                const rememberMe = $('#rememberMe').is(':checked');
                
                // Basic validation
                if (!username || !password) {
                    showAlert('Lütfen tüm alanları doldurun!', 'warning');
                    return;
                }
                
                // Show loading
                const loginBtn = $('#loginBtn');
                const originalText = loginBtn.html();
                loginBtn.html('<span class="loading"></span> Giriş yapılıyor...');
                loginBtn.prop('disabled', true);
                
                // Real login process
                $.ajax({
                    url: 'admin/auth.php',
                    method: 'POST',
                    data: {
                        action: 'login',
                        username: username,
                        password: password,
                        rememberMe: rememberMe
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            
                            // Store login info if remember me is checked
                            if (rememberMe) {
                                localStorage.setItem('rememberedUser', username);
                            }
                            
                            // Redirect to dashboard
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1500);
                        } else {
                            showAlert(response.message, 'danger');
                            loginBtn.html(originalText);
                            loginBtn.prop('disabled', false);
                        }
                    },
                    error: function() {
                        showAlert('Sunucu hatası! Lütfen tekrar deneyin.', 'danger');
                        loginBtn.html(originalText);
                        loginBtn.prop('disabled', false);
                    }
                });
            });
            
            // Check for remembered user
            const rememberedUser = localStorage.getItem('rememberedUser');
            if (rememberedUser) {
                $('#username').val(rememberedUser);
                $('#rememberMe').prop('checked', true);
            }
            
            // Show alert function
            function showAlert(message, type) {
                const alertClass = type === 'success' ? 'alert-success' : 
                                  type === 'warning' ? 'alert-warning' : 
                                  type === 'danger' ? 'alert-danger' : 'alert-info';
                
                const alert = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                          type === 'warning' ? 'exclamation-triangle' : 
                                          type === 'danger' ? 'times-circle' : 'info-circle'}"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
                
                $('.login-body').prepend(alert);
                
                // Auto remove after 5 seconds
                setTimeout(function() {
                    alert.alert('close');
                }, 5000);
            }
            
            // Enter key press
            $('#password').keypress(function(e) {
                if (e.which === 13) {
                    $('#loginForm').submit();
                }
            });
        });
    </script>
</body>
</html>

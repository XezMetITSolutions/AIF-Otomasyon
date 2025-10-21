<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Kullanıcı Bilgileri</title>
    
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
            padding: 20px;
        }
        
        .user-info-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }
        
        .user-info-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .user-info-header h1 {
            margin: 0;
            font-weight: 600;
        }
        
        .user-info-body {
            padding: 2rem;
        }
        
        .user-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 5px solid var(--primary-color);
        }
        
        .user-card h5 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .user-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
        }
        
        .info-item i {
            width: 20px;
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .role-superadmin {
            background-color: #dc3545;
            color: white;
        }
        
        .role-manager {
            background-color: #fd7e14;
            color: white;
        }
        
        .role-user {
            background-color: #6c757d;
            color: white;
        }
        
        @media (max-width: 768px) {
            .user-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="user-info-container">
        <div class="user-info-header">
            <h1><i class="fas fa-users"></i> AIF Otomasyon</h1>
            <p class="mb-0">Demo Kullanıcı Bilgileri</p>
        </div>
        
        <div class="user-info-body">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Demo Kullanıcıları:</strong> Aşağıdaki kullanıcı bilgileri ile sisteme giriş yapabilirsiniz.
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <div class="user-card">
                        <h5><i class="fas fa-crown"></i> Superadmin</h5>
                        <div class="user-info">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <strong>Kullanıcı Adı:</strong> admin
                            </div>
                            <div class="info-item">
                                <i class="fas fa-lock"></i>
                                <strong>Şifre:</strong> admin123
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <strong>E-posta:</strong> admin@aifotomasyon.com
                            </div>
                            <div class="info-item">
                                <i class="fas fa-tag"></i>
                                <strong>Rol:</strong> <span class="role-badge role-superadmin">Superadmin</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-3">
                    <div class="user-card">
                        <h5><i class="fas fa-user-shield"></i> Superadmin (Alternatif)</h5>
                        <div class="user-info">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <strong>Kullanıcı Adı:</strong> superadmin
                            </div>
                            <div class="info-item">
                                <i class="fas fa-lock"></i>
                                <strong>Şifre:</strong> superadmin123
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <strong>E-posta:</strong> superadmin@aifotomasyon.com
                            </div>
                            <div class="info-item">
                                <i class="fas fa-tag"></i>
                                <strong>Rol:</strong> <span class="role-badge role-superadmin">Superadmin</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-3">
                    <div class="user-card">
                        <h5><i class="fas fa-user-tie"></i> Yönetici</h5>
                        <div class="user-info">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <strong>Kullanıcı Adı:</strong> manager
                            </div>
                            <div class="info-item">
                                <i class="fas fa-lock"></i>
                                <strong>Şifre:</strong> manager123
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <strong>E-posta:</strong> manager@aifotomasyon.com
                            </div>
                            <div class="info-item">
                                <i class="fas fa-tag"></i>
                                <strong>Rol:</strong> <span class="role-badge role-manager">Manager</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-3">
                    <div class="user-card">
                        <h5><i class="fas fa-user"></i> Demo Kullanıcı</h5>
                        <div class="user-info">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <strong>Kullanıcı Adı:</strong> demo
                            </div>
                            <div class="info-item">
                                <i class="fas fa-lock"></i>
                                <strong>Şifre:</strong> demo123
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <strong>E-posta:</strong> demo@aifotomasyon.com
                            </div>
                            <div class="info-item">
                                <i class="fas fa-tag"></i>
                                <strong>Rol:</strong> <span class="role-badge role-user">User</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Giriş Sayfasına Git
                    </a>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Not:</strong> Bu demo kullanıcıları sadece test amaçlıdır. Gerçek kullanımda güvenli şifreler kullanın.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
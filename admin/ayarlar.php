<?php
/**
 * Ana Yönetici - Sistem Ayarları
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Sistem Ayarları';

$success = '';
$error = '';

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = 'Ayarlar başarıyla kaydedildi.';
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-cog me-2"></i>Sistem Ayarları
                </h1>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#genel">Genel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#smtp">SMTP Ayarları</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#guvenlik">Güvenlik</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tema">Tema & Görünüm</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="genel">
                                <h5 class="mb-3">Genel Ayarlar</h5>
                                <div class="mb-3">
                                    <label class="form-label">Uygulama Adı</label>
                                    <input type="text" class="form-control" value="AIF Otomasyon Sistemi" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Versiyon</label>
                                    <input type="text" class="form-control" value="1.0.1" readonly>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="smtp">
                                <h5 class="mb-3">SMTP E-posta Ayarları</h5>
                                <div class="mb-3">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" name="smtp_host" placeholder="smtp.gmail.com">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" name="smtp_port" value="587">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">SMTP Kullanıcı</label>
                                    <input type="text" class="form-control" name="smtp_user" placeholder="email@example.com">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">SMTP Şifre</label>
                                    <input type="password" class="form-control" name="smtp_pass" placeholder="Şifre">
                                </div>
                                <p class="text-muted"><small>Not: Bu ayarlar config/app.php dosyasında yapılandırılmalıdır.</small></p>
                            </div>
                            
                            <div class="tab-pane fade" id="guvenlik">
                                <h5 class="mb-3">Güvenlik Ayarları</h5>
                                <div class="mb-3">
                                    <label class="form-label">Oturum Süresi (saniye)</label>
                                    <input type="number" class="form-control" value="7200" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Minimum Şifre Uzunluğu</label>
                                    <input type="number" class="form-control" value="8" readonly>
                                </div>
                                <p class="text-muted"><small>Not: Bu ayarlar config/app.php dosyasında yapılandırılmalıdır.</small></p>
                            </div>
                            
                            <div class="tab-pane fade" id="tema">
                                <h5 class="mb-3">Tema & Görünüm</h5>
                                <div class="mb-3">
                                    <label class="form-label">Tema Rengi</label>
                                    <input type="color" class="form-control form-control-color" value="#007bff">
                                </div>
                                <p class="text-muted"><small>Tema ayarları yakında eklenecektir.</small></p>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Ayarları Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>


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

// Ayarları veritabanından getir
$allSettings = [];
try {
    $rows = $db->fetchAll("SELECT * FROM sistem_ayarlari");
    foreach ($rows as $row) {
        $allSettings[$row['ayar_key']] = $row['ayar_value'];
    }
} catch (Exception $e) {
    $error = "Ayarlar tablosu bulunamadı. Lütfen veritabanı kurulumunu kontrol edin.";
}

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            // Sadece sistem_ayarlari tablosunda olan anahtarları güncelle
            if (array_key_exists($key, $allSettings)) {
                $db->query(
                    "UPDATE sistem_ayarlari SET ayar_value = ? WHERE ayar_key = ?",
                    [$value, $key]
                );
                $allSettings[$key] = $value; // Local cache'i güncelle
            }
        }
        $success = 'Ayarlar başarıyla kaydedildi.';
    } catch (Exception $e) {
        $error = 'Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage();
    }
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

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="genel-tab" data-bs-toggle="tab" href="#genel">Genel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="smtp-tab" data-bs-toggle="tab" href="#smtp">SMTP Ayarları</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="guvenlik-tab" data-bs-toggle="tab" href="#guvenlik">Güvenlik</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tema-tab" data-bs-toggle="tab" href="#tema">Tema & Görünüm</a>
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
                                <input type="text" class="form-control" name="app_name"
                                    value="<?php echo htmlspecialchars($allSettings['app_name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Uygulama URL</label>
                                <input type="url" class="form-control" name="app_url"
                                    value="<?php echo htmlspecialchars($allSettings['app_url'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Versiyon</label>
                                <input type="text" class="form-control" name="app_version"
                                    value="<?php echo htmlspecialchars($allSettings['app_version'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="tab-pane fade" id="smtp">
                            <h5 class="mb-3">SMTP E-posta Ayarları</h5>
                            <div class="mb-3">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" name="smtp_host"
                                    value="<?php echo htmlspecialchars($allSettings['smtp_host'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" name="smtp_port"
                                    value="<?php echo htmlspecialchars($allSettings['smtp_port'] ?? '587'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Kullanıcı</label>
                                <input type="text" class="form-control" name="smtp_user"
                                    value="<?php echo htmlspecialchars($allSettings['smtp_user'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Şifre</label>
                                <input type="password" class="form-control" name="smtp_pass"
                                    placeholder="Değiştirmek istemiyorsanız boş bırakın">
                                <small class="text-muted">Girdiğiniz şifre .env dosyasına kaydedilmez, sadece bu modülde
                                    kullanılır.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Güvenlik</label>
                                <select class="form-select" name="smtp_secure">
                                    <option value="tls" <?php echo ($allSettings['smtp_secure'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($allSettings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo ($allSettings['smtp_secure'] ?? '') === 'none' ? 'selected' : ''; ?>>Yok</option>
                                </select>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-label">Gönderen E-posta (From Email)</label>
                                <input type="email" class="form-control" name="smtp_from_email"
                                    value="<?php echo htmlspecialchars($allSettings['smtp_from_email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gönderen Adı (From Name)</label>
                                <input type="text" class="form-control" name="smtp_from_name"
                                    value="<?php echo htmlspecialchars($allSettings['smtp_from_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="tab-pane fade" id="guvenlik">
                            <h5 class="mb-3">Güvenlik Ayarları</h5>
                            <div class="mb-3">
                                <label class="form-label">Oturum Süresi (saniye)</label>
                                <input type="number" class="form-control" name="session_lifetime"
                                    value="<?php echo htmlspecialchars($allSettings['session_lifetime'] ?? '7200'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Minimum Şifre Uzunluğu</label>
                                <input type="number" class="form-control" name="min_password_length"
                                    value="<?php echo htmlspecialchars($allSettings['min_password_length'] ?? '8'); ?>">
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tema">
                            <h5 class="mb-3">Tema & Görünüm</h5>
                            <div class="mb-3">
                                <label class="form-label">Tema Rengi</label>
                                <input type="color" class="form-control form-control-color" name="theme_color"
                                    value="<?php echo htmlspecialchars($allSettings['theme_color'] ?? '#00936F'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">
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
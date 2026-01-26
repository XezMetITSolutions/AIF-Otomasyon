<?php
/**
 * Email Debug Tool
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Mail.php';

Middleware::requireSuperAdmin();

$pageTitle = 'E-posta Debug Aracı';
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle database update
    if (isset($_POST['update_db_settings'])) {
        require_once __DIR__ . '/../classes/Database.php';
        $db = Database::getInstance()->getConnection();
        
        try {
            // Update SMTP user
            $stmt = $db->prepare("UPDATE sistem_ayarlari SET ayar_value = ? WHERE ayar_key = 'smtp_user'");
            $stmt->execute(['aifnet@islamischefoederation.at']);
            
            // Update SMTP from email
            $stmt = $db->prepare("UPDATE sistem_ayarlari SET ayar_value = ? WHERE ayar_key = 'smtp_from_email'");
            $stmt->execute(['aifnet@islamischefoederation.at']);
            
            // Update SMTP from name
            $stmt = $db->prepare("UPDATE sistem_ayarlari SET ayar_value = ? WHERE ayar_key = 'smtp_from_name'");
            $stmt->execute(['AİFNET']);
            
            $result = "✅ Veritabanı ayarları başarıyla güncellendi!\n\n" .
                      "• smtp_user: aifnet@islamischefoederation.at\n" .
                      "• smtp_from_email: aifnet@islamischefoederation.at\n" .
                      "• smtp_from_name: AİFNET";
        } catch (Exception $e) {
            $error = "❌ Veritabanı güncellenirken hata oluştu: " . $e->getMessage();
        }
    }
    // Handle test email
    elseif (isset($_POST['test_email'])) {
        $test_email = $_POST['test_email'] ?? '';
        
        if (!empty($test_email)) {
            // Test basic SMTP connection
            $data = [
                'ad_soyad' => 'Test Kullanıcı',
                'email' => $test_email,
                'baslik' => 'Test Toplantısı',
                'toplanti_tarihi' => date('Y-m-d H:i:s'),
                'konum' => 'Test Lokasyon',
                'aciklama' => 'Bu bir test davetiyesidir.',
                'token' => 'test-token-' . time()
            ];
            
            if (Mail::sendMeetingInvitation($data)) {
                $result = "✅ Test e-postası başarıyla gönderildi: $test_email";
            } else {
                $error = "❌ E-posta gönderilemedi!\n\nHata: " . (Mail::$lastError ?? 'Bilinmeyen hata');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <h1 class="h3 mb-4">
            <i class="fas fa-bug me-2"></i>E-posta Debug Aracı
        </h1>

        <?php if ($result): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo nl2br(htmlspecialchars($result)); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo nl2br(htmlspecialchars($error)); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4 border-warning">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-database me-2"></i>Veritabanı Ayarlarını Güncelle</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            Veritabanındaki SMTP ayarlarını <strong>sitzung@islamischefoederation.at</strong> 
                            adresinden <strong>aifnet@islamischefoederation.at</strong> adresine güncelleyin.
                        </p>
                        <form method="POST" onsubmit="return confirm('Veritabanı ayarlarını güncellemek istediğinize emin misiniz?');">
                            <input type="hidden" name="update_db_settings" value="1">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-sync-alt me-2"></i>Veritabanını Güncelle
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Test E-postası Gönder</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Test E-posta Adresi</label>
                                <input type="email" name="test_email" class="form-control" 
                                       placeholder="test@example.com" required>
                                <small class="text-muted">
                                    Toplantı daveti şablonu ile test e-postası gönderilecek.
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Test Gönder
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>SMTP Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $config = require __DIR__ . '/../config/mail.php';
                        ?>
                        <table class="table table-sm">
                            <tr>
                                <th>Host:</th>
                                <td><?php echo htmlspecialchars($config['host']); ?></td>
                            </tr>
                            <tr>
                                <th>Port:</th>
                                <td><?php echo htmlspecialchars($config['port']); ?></td>
                            </tr>
                            <tr>
                                <th>Username:</th>
                                <td><?php echo htmlspecialchars($config['username']); ?></td>
                            </tr>
                            <tr>
                                <th>Security:</th>
                                <td><?php echo htmlspecialchars(strtoupper($config['secure'])); ?></td>
                            </tr>
                            <tr>
                                <th>From Email:</th>
                                <td><?php echo htmlspecialchars($config['from_email']); ?></td>
                            </tr>
                            <tr>
                                <th>From Name:</th>
                                <td><?php echo htmlspecialchars($config['from_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Server Logları</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">
                            Sunucu logları PHP error_log dosyasına yazılıyor. 
                            "AIF:" ile başlayan satırları arayın.
                        </p>
                        <p class="small">
                            <strong>Log Konumu:</strong><br>
                            <?php echo ini_get('error_log') ?: '/var/log/apache2/error.log'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

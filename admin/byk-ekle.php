<?php
/**
 * Ana Yönetici - Yeni BYK Ekleme (byk_categories tablosu)
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Yeni BYK Ekle';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '#009872');
    $description = trim($_POST['description'] ?? '');
    
    // Validasyon
    if (empty($code)) {
        $errors[] = 'BYK kodu gereklidir.';
    } elseif (strlen($code) > 10) {
        $errors[] = 'BYK kodu en fazla 10 karakter olabilir.';
    } else {
        // Kodu kontrol et (byk_categories tablosunda)
        try {
            $existing = $db->fetch("SELECT id FROM byk_categories WHERE code = ?", [$code]);
            if ($existing) {
                $errors[] = 'Bu BYK kodu zaten kullanılıyor.';
            }
        } catch (Exception $e) {
            // Tablo yoksa devam et
        }
    }
    
    if (empty($name)) {
        $errors[] = 'BYK adı gereklidir.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'BYK adı en fazla 100 karakter olabilir.';
    }
    
    if (!empty($color) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        $errors[] = 'Geçersiz renk kodu formatı. Örnek: #009872';
    }
    
    if (empty($errors)) {
        try {
            // byk_categories tablosuna ekle
            $db->query("
                INSERT INTO byk_categories (code, name, color, description) 
                VALUES (?, ?, ?, ?)
            ", [$code, $name, $color, $description]);
            
            $success = true;
            header('Location: /admin/byk.php?success=1');
            exit;
        } catch (Exception $e) {
            $errors[] = 'BYK eklenirken bir hata oluştu: ' . $e->getMessage();
        }
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
                <i class="fas fa-plus-circle me-2"></i>Yeni BYK Ekle
            </h1>
            <a href="/admin/byk.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Geri Dön
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">BYK Kodu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" 
                                   value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" 
                                   maxlength="10" required placeholder="Örn: AT, KT, GT">
                            <small class="form-text text-muted">Benzersiz bir kod (maksimum 10 karakter)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Renk Kodu</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="colorPicker" 
                                       value="<?php echo htmlspecialchars($_POST['color'] ?? '#009872'); ?>" 
                                       title="Renk seçin">
                                <input type="text" class="form-control" id="color" name="color" 
                                       value="<?php echo htmlspecialchars($_POST['color'] ?? '#009872'); ?>" 
                                       pattern="^#[0-9A-Fa-f]{6}$" placeholder="#009872">
                            </div>
                            <small class="form-text text-muted">Hex renk kodu (örn: #009872)</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">BYK Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                               maxlength="100" required placeholder="Örn: Ana Teşkilat">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="BYK hakkında açıklama..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/byk.php" class="btn btn-outline-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
// Renk seçici ile text input senkronizasyonu
document.getElementById('colorPicker').addEventListener('input', function(e) {
    document.getElementById('color').value = e.target.value;
});

document.getElementById('color').addEventListener('input', function(e) {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.getElementById('colorPicker').value = e.target.value;
    }
});
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>


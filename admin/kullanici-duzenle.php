<?php
/**
 * Ana Yönetici - Kullanıcı Düzenleme
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Kullanıcı Düzenle';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: /admin/kullanicilar.php');
    exit;
}

// Kullanıcı bilgilerini al
$kullanici = $db->fetch("SELECT * FROM kullanicilar WHERE kullanici_id = ?", [$id]);
if (!$kullanici) {
    header('Location: /admin/kullanicilar.php?error=notfound');
    exit;
}

// Roller ve BYK'lar
$roller = $db->fetchAll("SELECT * FROM roller ORDER BY rol_yetki_seviyesi DESC");
// BYK'lar (filtre için) - Önce byk_categories'i kontrol et
try {
    $bykList = $db->fetchAll("SELECT id as byk_id, name as byk_adi, code as byk_kodu FROM byk_categories ORDER BY code");
} catch (Exception $e) {
    // byk_categories yoksa eski byk tablosunu kullan
    $bykList = $db->fetchAll("SELECT * FROM byk WHERE aktif = 1 ORDER BY byk_adi");
}

// Alt birimler (görevler)
try {
    $altBirimler = $db->fetchAll("
        SELECT ab.alt_birim_id,
               ab.alt_birim_adi,
               COALESCE(bc.name, b.byk_adi, '-') AS byk_adi
        FROM alt_birimler ab
        LEFT JOIN byk b ON ab.byk_id = b.byk_id
        LEFT JOIN byk_categories bc ON b.byk_kodu = bc.code
        ORDER BY byk_adi, ab.alt_birim_adi
    ");
} catch (Exception $e) {
    $altBirimler = $db->fetchAll("
        SELECT ab.alt_birim_id,
               ab.alt_birim_adi,
               b.byk_adi
        FROM alt_birimler ab
        LEFT JOIN byk b ON ab.byk_id = b.byk_id
        ORDER BY b.byk_adi, ab.alt_birim_adi
    ");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    $rol_id = (int)($_POST['rol_id'] ?? 0);
    $byk_id = !empty($_POST['byk_id']) ? (int)$_POST['byk_id'] : null;
    $alt_birim_id = !empty($_POST['alt_birim_id']) ? (int)$_POST['alt_birim_id'] : null;
    $alt_birim_id = !empty($_POST['alt_birim_id']) ? (int)$_POST['alt_birim_id'] : null;
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $divan_uyesi = isset($_POST['divan_uyesi']) ? 1 : 0;
    
    // Validasyon
    if (empty($ad)) $errors[] = 'Ad gereklidir.';
    if (empty($soyad)) $errors[] = 'Soyad gereklidir.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi gereklidir.';
    }
    if ($rol_id <= 0) $errors[] = 'Rol seçilmelidir.';
    
    // E-posta kontrolü (başka kullanıcıda varsa)
    if (empty($errors)) {
        $existing = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = ? AND kullanici_id != ?", [$email, $id]);
        if ($existing) {
            $errors[] = 'Bu e-posta adresi zaten kullanılıyor.';
        }
    }
    
    // Güncelle
    if (empty($errors)) {
        try {
            if (!empty($sifre)) {
                // Şifre değiştirildi
                if (strlen($sifre) < 6) {
                    $errors[] = 'Şifre en az 6 karakter olmalıdır.';
                } else {
                    $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);
                    $db->query("
                        UPDATE kullanicilar 
                        SET ad = ?, soyad = ?, email = ?, sifre = ?, rol_id = ?, byk_id = ?, alt_birim_id = ?, aktif = ?, divan_uyesi = ?
                        WHERE kullanici_id = ?
                    ", [$ad, $soyad, $email, $sifre_hash, $rol_id, $byk_id, $alt_birim_id, $aktif, $divan_uyesi, $id]);
                }
            } else {
                // Şifre değiştirilmedi
                $db->query("
                    UPDATE kullanicilar 
                    SET ad = ?, soyad = ?, email = ?, rol_id = ?, byk_id = ?, alt_birim_id = ?, aktif = ?, divan_uyesi = ?
                    WHERE kullanici_id = ?
                ", [$ad, $soyad, $email, $rol_id, $byk_id, $alt_birim_id, $aktif, $divan_uyesi, $id]);
            }
            
            if (empty($errors)) {
                header('Location: /admin/kullanicilar.php?success=1');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Kullanıcı güncellenirken bir hata oluştu: ' . $e->getMessage();
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
                <i class="fas fa-user-edit me-2"></i>Kullanıcı Düzenle
            </h1>
            <a href="/admin/kullanicilar.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Geri Dön
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ad" value="<?php echo htmlspecialchars($_POST['ad'] ?? $kullanici['ad']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Soyad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="soyad" value="<?php echo htmlspecialchars($_POST['soyad'] ?? $kullanici['soyad']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-posta <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $kullanici['email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Yeni Şifre <small class="text-muted">(Değiştirmek istemiyorsanız boş bırakın)</small></label>
                            <input type="password" class="form-control" name="sifre" minlength="6">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" name="rol_id" required>
                                <option value="">Rol Seçiniz</option>
                                <?php foreach ($roller as $rol): ?>
                                    <option value="<?php echo $rol['rol_id']; ?>" <?php echo (isset($_POST['rol_id']) ? $_POST['rol_id'] : $kullanici['rol_id']) == $rol['rol_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rol['rol_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">BYK</label>
                            <select class="form-select" name="byk_id">
                                <option value="">BYK Seçiniz (Opsiyonel)</option>
                                <?php foreach ($bykList as $byk): ?>
                                    <option value="<?php echo $byk['byk_id']; ?>" <?php echo (isset($_POST['byk_id']) ? $_POST['byk_id'] : $kullanici['byk_id']) == $byk['byk_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($byk['byk_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Görev / Alt Birim</label>
                            <select class="form-select" name="alt_birim_id">
                                <option value="">Görev Seçiniz (Opsiyonel)</option>
                                <?php foreach ($altBirimler as $altBirim): ?>
                                    <option value="<?php echo $altBirim['alt_birim_id']; ?>" <?php echo (isset($_POST['alt_birim_id']) ? $_POST['alt_birim_id'] : $kullanici['alt_birim_id']) == $altBirim['alt_birim_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($altBirim['byk_adi'] ? $altBirim['byk_adi'] . ' - ' : '') . $altBirim['alt_birim_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="aktif" id="aktif" value="1" <?php echo (isset($_POST['aktif']) ? $_POST['aktif'] : $kullanici['aktif']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="aktif">
                                Aktif Kullanıcı
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="divan_uyesi" id="divan_uyesi" value="1" <?php echo (isset($_POST['divan_uyesi']) ? $_POST['divan_uyesi'] : ($kullanici['divan_uyesi'] ?? 0)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="divan_uyesi">
                                Divan Üyesi
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/kullanicilar.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Güncelle
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


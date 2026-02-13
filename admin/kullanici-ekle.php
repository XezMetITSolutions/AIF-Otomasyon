<?php
/**
 * Ana Yönetici - Yeni Kullanıcı Ekleme
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Yeni Kullanıcı Ekle';

// Roller ve BYK'lar
$roller = $db->fetchAll("SELECT * FROM roller ORDER BY rol_yetki_seviyesi DESC");

// BYK'lar (filtre için) - Önce byk_categories'i kontrol et
try {
    $bykList = $db->fetchAll("SELECT id as byk_id, name as byk_adi, code as byk_kodu FROM byk_categories WHERE code IN ('AT', 'GT', 'KGT', 'gt', 'KT') ORDER BY code");
} catch (Exception $e) {
    // byk_categories yoksa eski byk tablosunu kullan
    $bykList = $db->fetchAll("SELECT * FROM byk WHERE aktif = 1 AND byk_kodu IN ('AT', 'GT', 'KGT', 'gt', 'KT') ORDER BY byk_adi");
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    $rol_id = (int) ($_POST['rol_id'] ?? 0);
    $byk_ids = $_POST['byk_ids'] ?? [];
    $byk_id_primary = !empty($byk_ids) ? (int) $byk_ids[0] : null;
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $divan_uyesi = isset($_POST['divan_uyesi']) ? 1 : 0;

    // Validasyon
    if (empty($ad))
        $errors[] = 'Ad gereklidir.';
    if (empty($soyad))
        $errors[] = 'Soyad gereklidir.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi gereklidir.';
    }
    if (empty($sifre) || strlen($sifre) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    }
    if ($rol_id <= 0)
        $errors[] = 'Rol seçilmelidir.';

    // E-posta kontrolü
    if (empty($errors)) {
        $existing = $db->fetch("SELECT kullanici_id FROM kullanicilar WHERE email = ?", [$email]);
        if ($existing) {
            $errors[] = 'Bu e-posta adresi zaten kullanılıyor.';
        }
    }

    // Kaydet
    if (empty($errors)) {
        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        try {
            $db->query("
                INSERT INTO kullanicilar (rol_id, byk_id, email, sifre, ad, soyad, aktif, divan_uyesi, ilk_giris_zorunlu, olusturma_tarihi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ", [$rol_id, $byk_id_primary, $email, $sifre_hash, $ad, $soyad, $aktif, $divan_uyesi]);

            $newUserId = $db->lastInsertId();

            // BYK İlişkilerini Kaydet
            foreach ($byk_ids as $bid) {
                $db->query("INSERT INTO kullanici_byklar (kullanici_id, byk_id) VALUES (?, ?)", [$newUserId, $bid]);
            }

            // Yeni Kullanıcıya E-posta Gönder
            require_once __DIR__ . '/../classes/Mail.php';
            Mail::sendWithTemplate($email, 'yeni_kullanici', [
                'ad_soyad' => $ad . ' ' . $soyad,
                'email' => $email
            ]);

            $success = true;
            header('Location: /admin/kullanicilar.php?success=1');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Kullanıcı eklenirken bir hata oluştu: ' . $e->getMessage();
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
                <i class="fas fa-user-plus me-2"></i>Yeni Kullanıcı Ekle
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
                            <input type="text" class="form-control" name="ad"
                                value="<?php echo htmlspecialchars($_POST['ad'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Soyad <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="soyad"
                                value="<?php echo htmlspecialchars($_POST['soyad'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-posta <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Şifre <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="sifre" minlength="6" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" name="rol_id" required>
                                <option value="">Rol Seçiniz</option>
                                <?php foreach ($roller as $rol): ?>
                                    <option value="<?php echo $rol['rol_id']; ?>" <?php echo (isset($_POST['rol_id']) && $_POST['rol_id'] == $rol['rol_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rol['rol_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">BYK (Birden fazla seçilebilir)</label>
                            <select class="form-select select2-multiple" name="byk_ids[]" multiple>
                                <?php foreach ($bykList as $byk): ?>
                                    <option value="<?php echo $byk['byk_id']; ?>" <?php echo (isset($_POST['byk_ids']) && in_array($byk['byk_id'], $_POST['byk_ids'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($byk['byk_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Birden fazla birim seçmek için Ctrl tuşuna basılı tutun.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="aktif" id="aktif" value="1" <?php echo (!isset($_POST['aktif']) || $_POST['aktif']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="aktif">
                                Aktif Kullanıcı
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="divan_uyesi" id="divan_uyesi"
                                value="1" <?php echo (isset($_POST['divan_uyesi']) && $_POST['divan_uyesi']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="divan_uyesi">
                                Divan Üyesi
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/kullanicilar.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Kaydet
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
<?php
/**
 * Ana Yönetici - Toplantı Düzenleme
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireSuperAdmin();

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Toplantı Düzenle';

$toplanti_id = $_GET['id'] ?? null;
if (!$toplanti_id) {
    header('Location: /admin/toplantilar.php');
    exit;
}

// Toplantı bilgilerini getir
$toplanti = $db->fetch("
    SELECT t.*, b.byk_adi, b.byk_kodu
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    WHERE t.toplanti_id = ?
", [$toplanti_id]);

if (!$toplanti) {
    header('Location: /admin/toplantilar.php');
    exit;
}

// Katılımcıları getir
$katilimcilar = $db->fetchAll("
    SELECT 
        tk.*,
        k.ad,
        k.soyad,
        k.email,
        ab.alt_birim_adi
    FROM toplanti_katilimcilar tk
    INNER JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
    LEFT JOIN alt_birimler ab ON k.alt_birim_id = ab.alt_birim_id
    WHERE tk.toplanti_id = ?
    ORDER BY FIELD(tk.katilim_durumu, 'katilacak', 'beklemede', 'katilmayacak'), k.ad, k.soyad
", [$toplanti_id]);

// Gündem maddelerini getir
$gundem_maddeleri = $db->fetchAll("
    SELECT * FROM toplanti_gundem
    WHERE toplanti_id = ?
    ORDER BY sira_no
", [$toplanti_id]);

// Kararları getir
$kararlar = $db->fetchAll("
    SELECT 
        tk.*,
        tg.baslik as gundem_baslik
    FROM toplanti_kararlar tk
    LEFT JOIN toplanti_gundem tg ON tk.gundem_id = tg.gundem_id
    WHERE tk.toplanti_id = ?
    ORDER BY tk.karar_id
", [$toplanti_id]);



// BYK listesi
$bykler = $db->fetchAll("SELECT byk_id, byk_adi, byk_kodu FROM byk ORDER BY byk_adi");

$error = '';
$success = $_GET['success'] ?? '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_toplanti') {
            $baslik = trim($_POST['baslik'] ?? '');
            $aciklama = trim($_POST['aciklama'] ?? '');
            $toplanti_tarihi = $_POST['toplanti_tarihi'] ?? '';
            $bitis_tarihi = $_POST['bitis_tarihi'] ?? null;
            $konum = trim($_POST['konum'] ?? '');
            $toplanti_turu = $_POST['toplanti_turu'] ?? 'normal';
            $durum = $_POST['durum'] ?? 'planlandi';
            $sekreter_id = !empty($_POST['sekreter_id']) ? $_POST['sekreter_id'] : null;

            $db->query("
                UPDATE toplantilar SET
                    baslik = ?,
                    aciklama = ?,
                    toplanti_tarihi = ?,
                    bitis_tarihi = ?,
                    konum = ?,
                    toplanti_turu = ?,
                    durum = ?,
                    sekreter_id = ?
                WHERE toplanti_id = ?
            ", [
                $baslik,
                $aciklama,
                $toplanti_tarihi,
                $bitis_tarihi,
                $konum,
                $toplanti_turu,
                $durum,
                $sekreter_id,
                $toplanti_id
            ]);

            $success = 'Toplantı bilgileri güncellendi!';

            // Auto-Sync: Description to Agenda
            if (!empty($aciklama)) {
                // 1. Get existing agenda items to prevent duplicates
                $existing_items = $db->fetchAll("SELECT baslik FROM toplanti_gundem WHERE toplanti_id = ?", [$toplanti_id]);
                $existing_titles = array_map(function ($item) {
                    return mb_strtolower(trim($item['baslik']));
                }, $existing_items);

                // 2. Parse description
                $lines = explode("\n", $aciklama);
                $new_items = [];

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line))
                        continue;

                    // Remove bullets/numbers (e.g., "1.", "1-", "-", "*", "•")
                    // Supports unicode bullets and various separators
                    $clean_line = preg_replace('/^(\d+[\.\-\)]|[\-\*•])\s+/u', '', $line);
                    $clean_line = trim($clean_line);

                    if (empty($clean_line))
                        continue;

                    // Check if exists (case-insensitive)
                    if (!in_array(mb_strtolower($clean_line), $existing_titles)) {
                        $new_items[] = $clean_line;
                    }
                }

                // 3. Insert new items
                if (!empty($new_items)) {
                    // Get current max sort order
                    $max_sort = $db->fetch("SELECT MAX(sira_no) as max_sira FROM toplanti_gundem WHERE toplanti_id = ?", [$toplanti_id]);
                    $current_sort = ($max_sort['max_sira'] ?? 0) + 1;

                    $insert_sql = "INSERT INTO toplanti_gundem (toplanti_id, baslik, sira_no, durum) VALUES (?, ?, ?, 'beklemede')";
                    $stmt = $db->getConnection()->prepare($insert_sql);

                    foreach ($new_items as $item) {
                        $stmt->execute([$toplanti_id, $item, $current_sort]);
                        $current_sort++;
                    }

                    $success .= " (" . count($new_items) . " yeni gündem maddesi eklendi)";
                }
            }
        } elseif ($action === 'send_invitations') {
            require_once __DIR__ . '/../classes/Mail.php';

            error_log("AIF: Send invitations action triggered");

            $selected_ids = $_POST['selected_participants'] ?? [];

            error_log("AIF: Selected participant IDs: " . print_r($selected_ids, true));

            if (empty($selected_ids)) {
                error_log("AIF: No participants selected");
                throw new Exception('Lütfen en az bir katılımcı seçin.');
            }

            $success_count = 0;
            $fail_count = 0;
            $last_error = '';
            $failed_recipients = [];

            // Seçilen katılımcıların detaylarını getir
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            $params = array_merge([$toplanti_id], $selected_ids);

            $targets = $db->fetchAll("
                SELECT k.ad, k.soyad, k.email, tk.token, tk.katilimci_id
                FROM toplanti_katilimcilar tk
                INNER JOIN kullanicilar k ON tk.kullanici_id = k.kullanici_id
                WHERE tk.toplanti_id = ? AND tk.katilimci_id IN ($placeholders)
            ", $params);

            error_log("AIF: Found " . count($targets) . " participants to send invitations to");

            if (empty($targets)) {
                error_log("AIF: No valid participants found for selected IDs");
                throw new Exception('Seçili katılımcılar bulunamadı.');
            }

            foreach ($targets as $target) {
                error_log("AIF: Sending invitation to: " . $target['email']);

                // Token yoksa oluştur ve kaydet
                if (empty($target['token'])) {
                    $target['token'] = bin2hex(random_bytes(32));
                    $db->query("UPDATE toplanti_katilimcilar SET token = ? WHERE katilimci_id = ?", [
                        $target['token'],
                        $target['katilimci_id']
                    ]);
                    error_log("AIF: Generated new token for participant " . $target['katilimci_id']);
                }

                $mailData = [
                    'email' => $target['email'],
                    'ad_soyad' => $target['ad'] . ' ' . $target['soyad'],
                    'baslik' => $toplanti['baslik'],
                    'toplanti_tarihi' => $toplanti['toplanti_tarihi'],
                    'konum' => $toplanti['konum'],
                    'aciklama' => $toplanti['aciklama'],
                    'token' => $target['token']
                ];

                if (Mail::sendMeetingInvitation($mailData)) {
                    $success_count++;
                    error_log("AIF: Successfully sent invitation to: " . $target['email']);
                } else {
                    $fail_count++;
                    $last_error = Mail::$lastError ?? 'Bilinmeyen hata';
                    $failed_recipients[] = $target['ad'] . ' ' . $target['soyad'] . ' (' . $target['email'] . ')';
                    error_log("AIF: Failed to send invitation to: " . $target['email'] . " - Error: " . $last_error);
                }
            }

            error_log("AIF: Invitation sending complete. Success: $success_count, Failed: $fail_count");

            $success = "İşlem tamamlandı: $success_count başarılı, $fail_count başarısız gönderim.";

            if ($fail_count > 0) {
                $error = "E-posta gönderilemedi: " . implode(', ', $failed_recipients) . "\n";
                if (!empty($last_error)) {
                    $error .= "Hata detayı: " . $last_error;
                }
                error_log("AIF: Invitation errors: " . $error);
            }
        }

        // Sayfayı yenile
        header("Location: /admin/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
        exit;

    } catch (Exception $e) {
        $msg = $e->getMessage();

        // Self-Healing for missing columns
        if (strpos($msg, 'Unknown column') !== false) {

            // Handle 'token' column missing (common in invitation flow)
            if (strpos($msg, 'token') !== false) {
                $db->query("ALTER TABLE toplanti_katilimcilar ADD COLUMN token VARCHAR(64) NULL AFTER katilim_durumu");

                // Retry sending invitations if that was the action
                if ($action === 'send_invitations') {
                    header("Location: /admin/toplanti-duzenle.php?id={$toplanti_id}&error=" . urlencode("Sistem güncellendi ('token' sütunu eklendi). Lütfen işlemi tekrar deneyin."));
                    exit;
                }
            }

            // Handle 'toplantilar' columns missing (only for update action)
            if ($action === 'update_toplanti') {
                $healed = false;
                if (strpos($msg, 'bitis_tarihi') !== false) {
                    $db->query("ALTER TABLE toplantilar ADD COLUMN bitis_tarihi DATETIME NULL AFTER toplanti_tarihi");
                    $healed = true;
                }
                if (strpos($msg, 'toplanti_turu') !== false) {
                    $db->query("ALTER TABLE toplantilar ADD COLUMN toplanti_turu ENUM('normal', 'acil', 'ozel', 'olagan', 'olaganüstü') DEFAULT 'normal' AFTER gundem");
                    $healed = true;
                }

                if ($healed) {
                    // Retry Update
                    $db->query("
                        UPDATE toplantilar SET
                            baslik = ?,
                            aciklama = ?,
                            toplanti_tarihi = ?,
                            bitis_tarihi = ?,
                            konum = ?,
                            toplanti_turu = ?,
                            durum = ?
                        WHERE toplanti_id = ?
                    ", [
                        $baslik,
                        $aciklama,
                        $toplanti_tarihi,
                        $bitis_tarihi,
                        $konum,
                        $toplanti_turu,
                        $durum,
                        $toplanti_id
                    ]);
                    $success = 'Toplantı bilgileri güncellendi (Sistem onarımı yapıldı).';
                    header("Location: /admin/toplanti-duzenle.php?id={$toplanti_id}&success=" . urlencode($success));
                    exit;
                }
            }
        }

        $error = $msg;
    }
}

// Katılım istatistikleri
$katilim_stats = [
    'beklemede' => 0,
    'katilacak' => 0,
    'katilmayacak' => 0,
    'mazeret' => 0
];
foreach ($katilimcilar as $k) {
    $katilim_stats[$k['katilim_durumu']]++;
}

// Admin Panel Variables (Admin is always creator/manager in this context)
$isCreator = true;
$canManageContent = true;

include __DIR__ . '/../includes/header.php';
?>


<link rel="stylesheet" href="/assets/css/toplanti-yonetimi.css?v=<?php echo time(); ?>">

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper" data-toplanti-id="<?php echo $toplanti_id; ?>">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-edit me-2"></i><?php echo htmlspecialchars($toplanti['baslik']); ?>
            </h1>
            <div>
                <a href="/admin/toplanti-pdf.php?id=<?php echo $toplanti_id; ?>" class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>PDF İndir
                </a>
                <a href="/admin/toplantilar.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Geri Dön
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#bilgiler">
                    <i class="fas fa-info-circle me-2"></i>Temel Bilgiler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#katilimcilar">
                    <i class="fas fa-users me-2"></i>Katılımcılar
                    <span
                        class="badge bg-primary ms-1"><?php echo $katilim_stats['katilacak'] . '/' . count($katilimcilar); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#gundem">
                    <i class="fas fa-list me-2"></i>Gündem
                    <span class="badge bg-primary ms-1"><?php echo count($gundem_maddeleri); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#kararlar">
                    <i class="fas fa-gavel me-2"></i>Kararlar
                    <span class="badge bg-primary ms-1"><?php echo count($kararlar); ?></span>
                </a>
            </li>

        </ul>

        <div class="tab-content">
            <!-- Temel Bilgiler Tab -->
            <div class="tab-pane fade show active" id="bilgiler">
                <?php include __DIR__ . '/../includes/toplanti-tabs/bilgiler.php'; ?>
            </div>

            <!-- Katılımcılar Tab -->
            <div class="tab-pane fade" id="katilimcilar">
                <?php include __DIR__ . '/../includes/toplanti-tabs/katilimcilar.php'; ?>
            </div>



            <!-- Gündem Tab -->
            <div class="tab-pane fade" id="gundem">
                <?php include __DIR__ . '/../includes/toplanti-tabs/gundem.php'; ?>
            </div>

            <!-- Kararlar Tab -->
            <div class="tab-pane fade" id="kararlar">
                <?php include __DIR__ . '/../includes/toplanti-tabs/kararlar.php'; ?>
            </div>


        </div>
        <script src="/assets/js/toplanti-yonetimi.js?v=<?php echo time(); ?>"></script>
        <script>
            if (typeof ToplantiYonetimi !== 'undefined') {
                ToplantiYonetimi.init(<?php echo $toplanti_id; ?>);
            }
        </script>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>
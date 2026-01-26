<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

// Auto-Migration: Ensure 'red_nedeni' column exists
try {
    $db->query("SELECT red_nedeni FROM toplanti_katilimcilar LIMIT 1");
} catch (Exception $e) {
    // Column likely doesn't exist, add it
    $db->query("ALTER TABLE toplanti_katilimcilar ADD COLUMN red_nedeni TEXT NULL AFTER katilim_durumu");
}

$token = $_GET['token'] ?? $_POST['token'] ?? null;
$yanit = $_GET['yanit'] ?? $_POST['yanit'] ?? null;
$mazeret = $_POST['mazeret'] ?? null;
$message = '';
$messageType = 'info';
$showForm = false;

if ($token && $yanit) {
    // Katılımcıyı bul
    $katilimci = $db->fetch("
        SELECT tk.*, t.baslik, t.toplanti_tarihi, t.konum, t.aciklama
        FROM toplanti_katilimcilar tk
        INNER JOIN toplantilar t ON tk.toplanti_id = t.toplanti_id
        WHERE tk.token = ?
    ", [$token]);

    // ICS Download Handler
    if (isset($_GET['action']) && $_GET['action'] === 'download_ics' && $katilimci) {
        $eventStart = strtotime($katilimci['toplanti_tarihi']);
        $eventEnd = $eventStart + 3600; // 1 hour duration default
        
        $now = gmdate('Ymd\THis\Z');
        $start = gmdate('Ymd\THis\Z', $eventStart);
        $end = gmdate('Ymd\THis\Z', $eventEnd);
        
        $summary = $katilimci['baslik'];
        $location = $katilimci['konum'] ?? '';
        $description = trim(preg_replace('/\s+/', ' ', strip_tags($katilimci['aciklama'] ?? '')));
        
        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//AIF CRM//Meeting//EN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";
        $icsContent .= "BEGIN:VEVENT\r\n";
        $icsContent .= "UID:" . md5($katilimci['toplanti_id'] . $now) . "@aifcrm.metechnik.at\r\n";
        $icsContent .= "DTSTAMP:" . $now . "\r\n";
        $icsContent .= "DTSTART:" . $start . "\r\n";
        $icsContent .= "DTEND:" . $end . "\r\n";
        $icsContent .= "SUMMARY:" . $summary . "\r\n";
        if ($location) $icsContent .= "LOCATION:" . $location . "\r\n";
        if ($description) $icsContent .= "DESCRIPTION:" . $description . "\r\n";
        $icsContent .= "END:VEVENT\r\n";
        $icsContent .= "END:VCALENDAR";

        // buffer clearing
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="davetiye.ics"');
        echo $icsContent;
        exit;
    }

    if ($katilimci) {
        $konu = htmlspecialchars($katilimci['baslik'] ?? '');
        $tarih = date('d.m.Y H:i', strtotime($katilimci['toplanti_tarihi'] ?? 'now'));
        
        // Mantık Akışı
        if ($yanit === 'katiliyor') {
            // Doğrudan Onay
            $db->query("
                UPDATE toplanti_katilimcilar 
                SET katilim_durumu = 'katilacak', red_nedeni = NULL
                WHERE katilimci_id = ?
            ", [$katilimci['katilimci_id']]);
            
            $messageType = 'success';
            $message = "Teşekkürler, <strong>'$konu'</strong> ($tarih) konulu toplantıya katılımınız onaylandı.";

        } elseif ($yanit === 'katilmiyor') {
            // Mazeret Formu Gösterilip Gösterilmeyeceği
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_mazeret'])) {
                // Form Gönderildi -> Kaydet
                $db->query("
                    UPDATE toplanti_katilimcilar 
                    SET katilim_durumu = 'katilmayacak', red_nedeni = ?
                    WHERE katilimci_id = ?
                ", [$mazeret, $katilimci['katilimci_id']]);
                
                $messageType = 'warning';
                $message = "<strong>'$konu'</strong> ($tarih) konulu toplantıya katılım sağlayamayacağınız ve mazeretiniz kaydedildi.";
            } else {
                // İlk Tıklama -> Formu Göster
                $showForm = true;
            }
        }
    } else {
        $messageType = 'danger';
        $message = 'Geçersiz veya süresi dolmuş davetiye bağlantısı.';
    }
} else {
    $messageType = 'danger';
    $message = 'Hatalı parametreler.';
}

include __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center p-5">
                    
                    <?php if ($showForm): ?>
                        <div class="display-1 text-warning mb-3">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h2 class="h4 mb-4">Katılım İptali</h2>
                        <p class="mb-4">Toplantıya katılamayacağınızı bildirmek üzeresiniz. Lütfen aşağıya bir mazeret belirterek işlemi tamamlayın.</p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <input type="hidden" name="yanit" value="katilmiyor">
                            <input type="hidden" name="submit_mazeret" value="1">
                            
                            <div class="mb-3 text-start">
                                <label for="mazeret" class="form-label fw-bold">Toplantıya Katılamama Mazeretiniz:</label>
                                <textarea class="form-control" id="mazeret" name="mazeret" rows="3" placeholder="Lütfen mazeretinizi buraya yazınız..." required></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger">Mazereti Gönder ve Reddet</button>
                                <a href="/" class="btn btn-light text-muted">İptal</a>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- Sonuç Ekranı -->
                        <?php if ($messageType === 'success'): ?>
                            <div class="display-1 text-success mb-3">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2 class="h4 mb-3">Katılım Onaylandı</h2>
                        <?php elseif ($messageType === 'warning'): ?>
                            <div class="display-1 text-warning mb-3">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h2 class="h4 mb-3">Katılım İptali ve Mazeret Kaydedildi</h2>
                        <?php else: ?>
                            <div class="display-1 text-danger mb-3">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h2 class="h4 mb-3">Bir Sorun Oluştu</h2>
                        <?php endif; ?>

                        <p class="lead mb-4"><?php echo $message; ?></p>
                        
                        <a href="/" class="btn btn-primary px-4">Ana Sayfaya Dön</a>
                        
                        <?php if ($yanit === 'katiliyor'): ?>
                            <a href="?token=<?php echo urlencode($token); ?>&yanit=katiliyor&action=download_ics" class="btn btn-outline-success px-4 ms-2">
                                <i class="fas fa-calendar-plus me-2"></i>Takvime Ekle
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

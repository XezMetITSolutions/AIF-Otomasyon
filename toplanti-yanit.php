<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

$token = $_GET['token'] ?? null;
$yanit = $_GET['yanit'] ?? null;
$message = '';
$messageType = 'info';

if ($token && $yanit) {
    // Katılımcıyı bul
    $katilimci = $db->fetch("
        SELECT tk.*, t.baslik, t.toplanti_tarihi
        FROM toplanti_katilimcilar tk
        INNER JOIN toplantilar t ON tk.toplanti_id = t.toplanti_id
        WHERE tk.token = ?
    ", [$token]);

    if ($katilimci) {
        $konu = htmlspecialchars($katilimci['baslik'] ?? '');
        $tarih = date('d.m.Y H:i', strtotime($katilimci['toplanti_tarihi'] ?? 'now'));
        
        $yeniDurum = ($yanit === 'katiliyor') ? 'katilacak' : 'katilmayacak';
        
        $db->query("
            UPDATE toplanti_katilimcilar 
            SET katilim_durumu = ? 
            WHERE katilimci_id = ?
        ", [$yeniDurum, $katilimci['katilimci_id']]);

        // Self-Healing: If 'karilimci_id' column name is typo in previous schema, try correct 'katilimci_id'
         if ($db->lastError()) {
             $db->query("
                UPDATE toplanti_katilimcilar 
                SET katilim_durumu = ? 
                WHERE katilimci_id = ?
            ", [$yeniDurum, $katilimci['katilimci_id']]);
         }

        if ($yeniDurum === 'katilacak') {
            $messageType = 'success';
            $message = "Teşekkürler, <strong>'$konu'</strong> ($tarih) konulu toplantıya katılımınız onaylandı.";
        } else {
            $messageType = 'warning';
            $message = "<strong>'$konu'</strong> ($tarih) konulu toplantıya katılım sağlayamayacağınız kaydedildi.";
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
                    <?php if ($messageType === 'success'): ?>
                        <div class="display-1 text-success mb-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="h4 mb-3">Katılım Onaylandı</h2>
                    <?php elseif ($messageType === 'warning'): ?>
                        <div class="display-1 text-warning mb-3">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h2 class="h4 mb-3">Katılım İptali</h2>
                    <?php else: ?>
                        <div class="display-1 text-danger mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2 class="h4 mb-3">Bir Sorun Oluştu</h2>
                    <?php endif; ?>

                    <p class="lead mb-4"><?php echo $message; ?></p>
                    
                    <a href="/" class="btn btn-primary px-4">Ana Sayfaya Dön</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

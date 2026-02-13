<?php
/**
 * Ana Yönetici - Toplantı Yönetimi
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Allow both super_admin and baskan roles
Middleware::requireRole(['super_admin', 'uye']);

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$pageTitle = 'Toplantı Yönetimi';

// Toplantılar
$toplantilar = $db->fetchAll("
    SELECT t.*, b.byk_adi, CONCAT(u.ad, ' ', u.soyad) as olusturan,
           (SELECT COUNT(*) FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id) as total_participants,
           (SELECT COUNT(*) FROM toplanti_katilimcilar tk WHERE tk.toplanti_id = t.toplanti_id AND tk.katilim_durumu = 'katilacak') as confirmed_participants
    FROM toplantilar t
    INNER JOIN byk b ON t.byk_id = b.byk_id
    INNER JOIN kullanicilar u ON t.olusturan_id = u.kullanici_id
    ORDER BY t.toplanti_tarihi DESC
    LIMIT 50
");

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<main class="container-fluid mt-4">
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-users-cog me-2"></i>Toplantı Yönetimi
            </h1>
            <a href="/admin/toplanti-ekle.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Yeni Toplantı Ekle
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                Toplam: <strong><?php echo count($toplantilar); ?></strong> toplantı
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Başlık</th>
                                <th>BYK</th>
                                <th>Tarih</th>
                                <th>Katılım</th>
                                <th>Oluşturan</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($toplantilar)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Henüz toplantı eklenmemiş.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($toplantilar as $toplanti): ?>
                                    <tr>
                                        <td>
                                            <a href="/admin/toplanti-duzenle.php?id=<?php echo $toplanti['toplanti_id']; ?>"
                                                class="text-dark text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($toplanti['baslik']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($toplanti['byk_adi']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($toplanti['toplanti_tarihi'])); ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-users me-1"></i>
                                                <?php echo $toplanti['confirmed_participants']; ?> /
                                                <?php echo $toplanti['total_participants']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($toplanti['olusturan']); ?></td>
                                        <td>
                                            <a href="/admin/toplanti-duzenle.php?id=<?php echo $toplanti['toplanti_id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($toplanti['durum'] !== 'iptal' && $toplanti['durum'] !== 'tamamlandi'): ?>
                                                <button class="btn btn-sm btn-outline-warning"
                                                    onclick="cancelMeeting(<?php echo $toplanti['toplanti_id']; ?>, '<?php echo htmlspecialchars(addslashes($toplanti['baslik'])); ?>')"
                                                    title="İptal Et">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteMeeting(<?php echo $toplanti['toplanti_id']; ?>, '<?php echo htmlspecialchars(addslashes($toplanti['baslik'])); ?>')"
                                                title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cancel Meeting Modal (Inside content-wrapper for SPA) -->
        <div class="modal fade" id="cancelMeetingModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Toplantıyı İptal Et</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>⚠️ <strong id="meetingTitle"></strong> toplantısını iptal etmek istediğinize emin misiniz?</p>
                        <p class="text-muted">Tüm katılımcılara iptal bildirimi e-postası gönderilecektir.</p>

                        <div class="mb-3">
                            <label for="cancelReason" class="form-label">İptal Nedeni (Opsiyonel)</label>
                            <textarea class="form-control" id="cancelReason" rows="3"
                                placeholder="İptal nedenini buraya yazabilirsiniz..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                        <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                            <i class="fas fa-times-circle me-2"></i>Toplantıyı İptal Et
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Use a block or check to prevent re-declaration if needed, 
            // but SPA loader usually executes scripts in a way that's fine.
            (function() {
                let currentMeetingId = null;

                window.cancelMeeting = function(id, title) {
                    currentMeetingId = id;
                    const titleEl = document.getElementById('meetingTitle');
                    const reasonEl = document.getElementById('cancelReason');
                    if (titleEl) titleEl.textContent = title;
                    if (reasonEl) reasonEl.value = '';
                    const modal = new bootstrap.Modal(document.getElementById('cancelMeetingModal'));
                    modal.show();
                };

                const confirmBtn = document.getElementById('confirmCancelBtn');
                if (confirmBtn) {
                    // Remove old listeners to avoid multiple calls
                    const newBtn = confirmBtn.cloneNode(true);
                    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
                    
                    newBtn.addEventListener('click', async function () {
                        const reasonEl = document.getElementById('cancelReason');
                        const reason = reasonEl ? reasonEl.value : '';
                        const btn = this;
                        const originalText = btn.innerHTML;

                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>İptal Ediliyor...';

                        try {
                            const response = await fetch('/api/cancel-meeting.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    toplanti_id: currentMeetingId,
                                    iptal_nedeni: reason
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                alert('✅ ' + data.message);
                                location.reload();
                            } else {
                                alert('❌ Hata: ' + data.message);
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                            }
                        } catch (error) {
                            alert('❌ Bir hata oluştu: ' + error.message);
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }
                    });
                }

                // Delete meeting function
                window.deleteMeeting = function(id, title) {
                    if (!confirm(`⚠️ "${title}" toplantısını kalıcı olarak silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz ve tüm ilgili veriler (katılımcılar, gündem, kararlar) silinecektir.`)) {
                        return;
                    }

                    // Second confirmation for safety
                    if (!confirm(`Son uyarı: Toplantıyı silmek istediğinize %100 emin misiniz?`)) {
                        return;
                    }

                    // Show loading
                    const btn = (typeof event !== 'undefined' && event.target) ? event.target.closest('button') : null;
                    const originalHTML = btn ? btn.innerHTML : '';
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    }

                    fetch('/api/delete-meeting.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            toplanti_id: id
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('✅ ' + data.message);
                                location.reload();
                            } else {
                                alert('❌ Hata: ' + data.message);
                                if (btn) {
                                    btn.disabled = false;
                                    btn.innerHTML = originalHTML;
                                }
                            }
                        })
                        .catch(error => {
                            alert('❌ Bir hata oluştu: ' + error.message);
                            if (btn) {
                                btn.disabled = false;
                                btn.innerHTML = originalHTML;
                            }
                        });
                };
            })();
        </script>
    </div>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>
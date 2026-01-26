<!-- Katılımcılar Tab İçeriği -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Katılımcı Listesi</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#katilimciEkleModal">
                    <i class="fas fa-plus me-1"></i>Katılımcı Ekle
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($katilimcilar)): ?>
                    <p class="text-muted text-center py-4">
                        <i class="fas fa-info-circle me-2"></i>Henüz katılımcı eklenmemiş
                    </p>
                <?php else: ?>
                    <form method="POST" id="davetiyeForm">
                        <input type="hidden" name="action" value="send_invitations">
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Seçili katılımcılara davetiye e-postası gönderilecek. Emin misiniz?')">
                                <i class="fas fa-paper-plane me-2"></i>Seçilenlere Davetiye Gönder
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-2" id="btnSelectAll">
                                <i class="fas fa-check-double me-2"></i>Tümünü Seç
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>Ad Soyad</th>
                                        <th>Mazeret</th>
                                        <th>Katılım Durumu</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($katilimcilar as $katilimci): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input katilimci-checkbox" type="checkbox" name="selected_participants[]" value="<?php echo $katilimci['katilimci_id']; ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($katilimci['ad'] . ' ' . $katilimci['soyad']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($katilimci['email']); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($katilimci['red_nedeni'])) {
                                                    echo '<span class="text-danger small"><i class="fas fa-info-circle me-1"></i>' . htmlspecialchars($katilimci['red_nedeni']) . '</span>';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm katilim-durum-select" 
                                                        data-katilimci-id="<?php echo $katilimci['katilimci_id']; ?>">
                                                    <option value="beklemede" <?php echo $katilimci['katilim_durumu'] === 'beklemede' ? 'selected' : ''; ?>>
                                                        ⌛ Davet Edildi
                                                    </option>
                                                    <option value="katilacak" <?php echo $katilimci['katilim_durumu'] === 'katilacak' ? 'selected' : ''; ?>>
                                                        ✅ Katılacak
                                                    </option>
                                                    <option value="katilmayacak" <?php echo $katilimci['katilim_durumu'] === 'katilmayacak' ? 'selected' : ''; ?>>
                                                        ❌ Katılmayacak
                                                    </option>
                                                </select>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger katilimci-sil-btn" 
                                                        data-katilimci-id="<?php echo $katilimci['katilimci_id']; ?>"
                                                        data-ad="<?php echo htmlspecialchars($katilimci['ad'] . ' ' . $katilimci['soyad']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    
                    <script>
                        // Existing checkbox listener
                        document.getElementById('selectAll').addEventListener('change', function() {
                            const checkboxes = document.querySelectorAll('.katilimci-checkbox');
                            checkboxes.forEach(cb => cb.checked = this.checked);
                        });
                        
                        // New Button listener
                        document.getElementById('btnSelectAll').addEventListener('click', function() {
                            const masterCheckbox = document.getElementById('selectAll');
                            const checkboxes = document.querySelectorAll('.katilimci-checkbox');
                            
                            // Toggle based on master or check all if unchecked
                            const newState = !masterCheckbox.checked;
                            
                            masterCheckbox.checked = newState;
                            checkboxes.forEach(cb => cb.checked = newState);
                            
                            // Update button text logic (Optional)
                            this.innerHTML = newState ? 
                                '<i class="fas fa-times me-2"></i>Seçimi Kaldır' : 
                                '<i class="fas fa-check-double me-2"></i>Tümünü Seç';
                        });
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Katılım İstatistikleri</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-hourglass-half text-secondary me-2"></i>Davet Edildi</span>
                        <span class="badge bg-secondary"><?php echo $katilim_stats['beklemede'] ?? 0; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-secondary" style="width: <?php echo count($katilimcilar) > 0 ? (($katilim_stats['beklemede'] ?? 0) / count($katilimcilar) * 100) : 0; ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-check-circle text-success me-2"></i>Katılacak</span>
                        <span class="badge bg-success"><?php echo $katilim_stats['katilacak'] ?? 0; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?php echo count($katilimcilar) > 0 ? (($katilim_stats['katilacak'] ?? 0) / count($katilimcilar) * 100) : 0; ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-times-circle text-danger me-2"></i>Katılmayacak</span>
                        <span class="badge bg-danger"><?php echo $katilim_stats['katilmayacak'] ?? 0; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-danger" style="width: <?php echo count($katilimcilar) > 0 ? (($katilim_stats['katilmayacak'] ?? 0) / count($katilimcilar) * 100) : 0; ?>%"></div>
                    </div>
                </div>

                <hr>

                <div class="text-center">
                    <h3 class="mb-0"><?php echo count($katilimcilar); ?></h3>
                    <p class="text-muted mb-0">Toplam Katılımcı</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Katılımcı Ekle Modal -->
<div class="modal fade" id="katilimciEkleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Katılımcı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kullanıcı Seç</label>
                    <select class="form-select" id="yeni_katilimci_id">
                        <option value="">Seçiniz...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Katılım Durumu</label>
                    <select class="form-select" id="yeni_katilim_durumu">
                        <option value="beklemede">Davet Edildi</option>
                        <option value="katildi">Katıldı</option>
                        <option value="ozur_diledi">Özür Diledi</option>
                        <option value="izinli">İzinli</option>
                        <option value="katilmadi">Katılmadı</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="katilimciEkleBtn">
                    <i class="fas fa-plus me-2"></i>Ekle
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal açıldığında BYK üyelerini yükle
document.getElementById('katilimciEkleModal').addEventListener('show.bs.modal', function() {
    const bykId = <?php echo $toplanti['byk_id']; ?>;
    const select = document.getElementById('yeni_katilimci_id');
    
    fetch(`/admin/api-byk-uyeler.php?byk_id=${bykId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                select.innerHTML = '<option value="">Seçiniz...</option>';
                data.uyeler.forEach(uye => {
                    // Zaten katılımcı olmayanları göster
                    const mevcutKatilimci = <?php echo json_encode(array_column($katilimcilar, 'kullanici_id')); ?>;
                    if (!mevcutKatilimci.includes(uye.kullanici_id)) {
                        select.innerHTML += `<option value="${uye.kullanici_id}">${uye.ad} ${uye.soyad}</option>`;
                    }
                });
            }
        });
});
</script>

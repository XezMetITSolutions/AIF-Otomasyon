<?php
// Kararları gündem maddelerine göre grupla
$kararlar_grouped = [];
foreach ($kararlar as $k) {
    $gid = $k['gundem_id'] ?? 'genel';
    $kararlar_grouped[$gid][] = $k;
}
?>

<!-- Kararlar Tab İçeriği -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Alınan Kararlar</h5>
        <?php if ($canManageContent): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                data-bs-target="#kararEkleModal" data-gundem-id="">
                <i class="fas fa-plus me-1"></i>Genel Karar Ekle
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($gundem_maddeleri) && empty($kararlar)): ?>
            <p class="text-muted text-center py-4">
                <i class="fas fa-info-circle me-2"></i>Henüz gündem veya karar eklenmemiş
            </p>
        <?php else: ?>

            <!-- Gündem Maddelerine Göre Kararlar -->
            <?php foreach ($gundem_maddeleri as $gundem): ?>
                <div class="card mb-4 border-light shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary me-2"><?php echo $gundem['sira_no']; ?></span>
                            <strong><?php echo htmlspecialchars($gundem['baslik']); ?></strong>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Karar Metni Düzenleyici (Görüşme Notu Gibi) -->
                        <?php
                        // Mevcut kararı bul
                        $mevcut_karar = $kararlar_grouped[$gundem['gundem_id']][0] ?? null;
                        $karar_metni = $mevcut_karar ? $mevcut_karar['karar_metni'] : '';
                        ?>

                        <div class="p-3 bg-light rounded container-karar-text">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label text-muted small fw-bold mb-0">
                                    <i class="fas fa-gavel me-1"></i>Karar Metni
                                </label>
                                <span class="small text-muted fst-italic save-status-decision"></span>
                            </div>
                            <textarea class="form-control form-control-sm decision-text-input mb-2" rows="3"
                                placeholder="Bu madde ile ilgili alınan kararı buraya yazınız..."
                                data-gundem-id="<?php echo $gundem['gundem_id']; ?>"
                                data-toplanti-id="<?php echo $toplanti_id; ?>" <?php echo !$canManageContent ? 'readonly' : ''; ?>><?php echo htmlspecialchars($karar_metni); ?></textarea>
                            <div class="text-end">
                                <small class="text-muted me-2">Otomatik kaydedilir</small>
                                <?php if ($canManageContent): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success decision-save-btn"
                                        data-gundem-id="<?php echo $gundem['gundem_id']; ?>"
                                        data-toplanti-id="<?php echo $toplanti_id; ?>">
                                        <i class="fas fa-save me-1"></i>Kaydet
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Genel Kararlar -->
            <?php if (!empty($kararlar_grouped['genel'])): ?>
                <div class="card mb-3 border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">Genel Kararlar (Gündem Dışı)</h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionGenel">
                            <?php foreach ($kararlar_grouped['genel'] as $karar): ?>
                                <!-- (Same accordion item structure as above, simplified for brevity in thought, but full implementation in code) -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#karar<?php echo $karar['karar_id']; ?>">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <div>
                                                    <?php if ($karar['karar_no']): ?>
                                                        <span
                                                            class="badge bg-secondary me-2"><?php echo htmlspecialchars($karar['karar_no']); ?></span>
                                                    <?php endif; ?>
                                                    <strong><?php echo htmlspecialchars($karar['baslik']); ?></strong>
                                                </div>
                                                <?php if ($karar['karar_sonucu']): ?>
                                                    <span class="badge bg-<?php
                                                    echo $karar['karar_sonucu'] === 'kabul' ? 'success' :
                                                        ($karar['karar_sonucu'] === 'red' ? 'danger' : 'warning');
                                                    ?>">
                                                        <?php echo ucfirst($karar['karar_sonucu']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="karar<?php echo $karar['karar_id']; ?>" class="accordion-collapse collapse"
                                        data-bs-parent="#accordionGenel">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <h6>Karar Metni:</h6>
                                                <p><?php echo nl2br(htmlspecialchars($karar['karar_metni'])); ?></p>
                                            </div>
                                            <?php if ($karar['oylama_yapildi']): ?>
                                                <div class="mb-3">
                                                    <h6>Oylama Sonuçları:</h6>
                                                    <div class="d-flex gap-3">
                                                        <div class="text-success"><i class="fas fa-check-circle me-1"></i>Kabul:
                                                            <?php echo $karar['kabul_oyu']; ?></div>
                                                        <div class="text-danger"><i class="fas fa-times-circle me-1"></i>Red:
                                                            <?php echo $karar['red_oyu']; ?></div>
                                                        <div class="text-warning"><i class="fas fa-minus-circle me-1"></i>Çekimser:
                                                            <?php echo $karar['cekinser_oyu']; ?></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="d-flex gap-2 justify-content-end">
                                                <?php if ($canManageContent): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary karar-duzenle-btn"
                                                        data-karar-id="<?php echo $karar['karar_id']; ?>">
                                                        <i class="fas fa-edit me-1"></i>Düzenle
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger karar-sil-btn"
                                                        data-karar-id="<?php echo $karar['karar_id']; ?>">
                                                        <i class="fas fa-trash me-1"></i>Sil
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<!-- Karar Ekle Modal (Same as before but ID updated) -->
<div class="modal fade" id="kararEkleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Karar Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="kararEkleForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Karar No</label>
                            <input type="text" class="form-control" id="karar_no" placeholder="Örn: 2024/001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İlgili Gündem</label>
                            <select class="form-select" id="karar_gundem_id">
                                <option value="">Genel (Gündem Dışı)</option>
                                <?php foreach ($gundem_maddeleri as $gundem): ?>
                                    <option value="<?php echo $gundem['gundem_id']; ?>">
                                        <?php echo $gundem['sira_no']; ?>.
                                        <?php echo htmlspecialchars($gundem['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- Reuse other fields -->
                    <div class="mb-3">
                        <label class="form-label">Karar Başlığı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="karar_baslik" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Karar Metni <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="karar_metni" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="oylama_yapildi">
                            <label class="form-check-label" for="oylama_yapildi">
                                Oylama Yapıldı
                            </label>
                        </div>
                    </div>
                    <div id="oylama_alani" style="display: none;">
                        <!-- Oylama inputs same as before -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Kabul Oyu</label>
                                <input type="number" class="form-control" id="kabul_oyu" value="0" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Red Oyu</label>
                                <input type="number" class="form-control" id="red_oyu" value="0" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Çekimser Oyu</label>
                                <input type="number" class="form-control" id="cekinser_oyu" value="0" min="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Karar Sonucu</label>
                            <select class="form-select" id="karar_sonucu">
                                <option value="">Seçiniz...</option>
                                <option value="kabul">Kabul</option>
                                <option value="red">Red</option>
                                <option value="ertelendi">Ertelendi</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="kararKaydetBtn">
                    <i class="fas fa-save me-2"></i>Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Modal açıldığında gundem-id set et
    document.getElementById('kararEkleModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const gundemId = button.getAttribute('data-gundem-id');
        const select = document.getElementById('karar_gundem_id');
        if (gundemId) {
            select.value = gundemId;
        } else {
            select.value = "";
        }
    });

    document.getElementById('oylama_yapildi').addEventListener('change', function () {
        document.getElementById('oylama_alani').style.display = this.checked ? 'block' : 'none';
    });
</script>
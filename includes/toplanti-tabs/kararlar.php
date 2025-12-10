<!-- Kararlar Tab İçeriği -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Alınan Kararlar</h5>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#kararEkleModal">
            <i class="fas fa-plus me-1"></i>Karar Ekle
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($kararlar)): ?>
            <p class="text-muted text-center py-4">
                <i class="fas fa-info-circle me-2"></i>Henüz karar eklenmemiş
            </p>
        <?php else: ?>
            <div class="accordion" id="kararlarAccordion">
                <?php foreach ($kararlar as $index => $karar): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#karar<?php echo $karar['karar_id']; ?>">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div>
                                        <?php if ($karar['karar_no']): ?>
                                            <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($karar['karar_no']); ?></span>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($karar['baslik']); ?></strong>
                                        <?php if ($karar['gundem_baslik']): ?>
                                            <br><small class="text-muted">Gündem: <?php echo htmlspecialchars($karar['gundem_baslik']); ?></small>
                                        <?php endif; ?>
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
                        <div id="karar<?php echo $karar['karar_id']; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                             data-bs-parent="#kararlarAccordion">
                            <div class="accordion-body">
                                <div class="mb-3">
                                    <h6>Karar Metni:</h6>
                                    <p><?php echo nl2br(htmlspecialchars($karar['karar_metni'])); ?></p>
                                </div>

                                <?php if ($karar['oylama_yapildi']): ?>
                                    <div class="mb-3">
                                        <h6>Oylama Sonuçları:</h6>
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <div class="p-3 bg-success bg-opacity-10 rounded">
                                                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                                    <h4 class="mb-0"><?php echo $karar['kabul_oyu']; ?></h4>
                                                    <small>Kabul</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 bg-danger bg-opacity-10 rounded">
                                                    <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
                                                    <h4 class="mb-0"><?php echo $karar['red_oyu']; ?></h4>
                                                    <small>Red</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 bg-warning bg-opacity-10 rounded">
                                                    <i class="fas fa-minus-circle text-warning fa-2x mb-2"></i>
                                                    <h4 class="mb-0"><?php echo $karar['cekinser_oyu']; ?></h4>
                                                    <small>Çekimser</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary karar-duzenle-btn" 
                                            data-karar-id="<?php echo $karar['karar_id']; ?>">
                                        <i class="fas fa-edit me-1"></i>Düzenle
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger karar-sil-btn" 
                                            data-karar-id="<?php echo $karar['karar_id']; ?>">
                                        <i class="fas fa-trash me-1"></i>Sil
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Karar Ekle Modal -->
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
                                <option value="">Seçiniz...</option>
                                <?php foreach ($gundem_maddeleri as $gundem): ?>
                                    <option value="<?php echo $gundem['gundem_id']; ?>">
                                        <?php echo $gundem['sira_no']; ?>. <?php echo htmlspecialchars($gundem['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
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
document.getElementById('oylama_yapildi').addEventListener('change', function() {
    document.getElementById('oylama_alani').style.display = this.checked ? 'block' : 'none';
});
</script>

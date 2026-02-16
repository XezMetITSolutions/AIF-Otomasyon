<?php
/**
 * Toplantı Değerlendirme Tab İçeriği
 */
?>
<div class="card shadow-sm border-0 rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom border-light">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Bölge Başkanı Değerlendirmesi
            </h5>
            <div id="evalSaveStatus" class="small text-muted fst-italic"></div>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-info-circle fa-2x opacity-50"></i>
                </div>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Blg. Bşk. Değerlendirmesi</h6>
                    <p class="mb-0 small">Bu bölüm toplantı genelindeki performans ve gündem maddeleri ile ilgili Bölge Başkanı'nın özel değerlendirmesi içindir.</p>
                </div>
            </div>
        </div>

        <div class="evaluation-container">
            <label class="form-label fw-bold text-secondary mb-2">Genel Değerlendirme ve Notlar</label>
            <textarea id="baskanDegerlendirmeInput" 
                      class="form-control rounded-3 shadow-none border-2" 
                      rows="12" 
                      placeholder="Toplantı değerlendirmesini buraya yazınız..."
                      data-toplanti-id="<?php echo $toplanti_id; ?>"
                      style="font-size: 1.05rem; line-height: 1.6; border-color: #f1f5f9;"><?php echo htmlspecialchars($toplanti['baskan_degerlendirmesi'] ?? ''); ?></textarea>
            
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">Toplantı Bitiş Zamanı</label>
                <div class="input-group">
                    <input type="datetime-local" id="toplantiBitisInput" 
                        class="form-control rounded-start-3 shadow-none border-2"
                        value="<?php echo !empty($toplanti['bitis_tarihi']) ? date('Y-m-d\TH:i', strtotime($toplanti['bitis_tarihi'])) : ''; ?>"
                        data-toplanti-id="<?php echo $toplanti_id; ?>">
                    <button type="button" id="btnSetCurrentTime" class="btn btn-outline-secondary" title="Şu Anki Saati Ayarla">
                        <i class="fas fa-clock me-1"></i>Şu An
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted">
                    <i class="fas fa-sync-alt me-1"></i>Otomatik olarak kaydedilir
                </div>
                <button type="button" 
                        id="btnSaveEvaluation" 
                        class="btn btn-primary px-4 rounded-3 shadow-sm"
                        data-toplanti-id="<?php echo $toplanti_id; ?>">
                    <i class="fas fa-save me-2"></i>Değerlendirmeyi Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<style>
#baskanDegerlendirmeInput:focus {
    border-color: #0d6efd;
    background-color: #fff;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.05);
}
</style>

<!-- Gündem Tab İçeriği -->
<?php $canManageContent = isset($canManageContent) ? $canManageContent : false; ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Gündem Maddeleri</h5>
        <?php if ($canManageContent): ?>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#gundemEkleModal">
            <i class="fas fa-plus me-1"></i>Gündem Ekle
        </button>
        <?php endif; ?>
        <?php if (strpos($toplanti['byk_kodu'], 'AT') !== false): ?>
            <button type="button" class="btn btn-sm btn-info text-white ms-2" id="atStandartGundemBtn">
                <i class="fas fa-magic me-1"></i>Birim Gündemini Ekle
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($gundem_maddeleri)): ?>
            <p class="text-muted text-center py-4">
                <i class="fas fa-info-circle me-2"></i>Henüz gündem maddesi eklenmemiş
            </p>
        <?php else: ?>
            <div id="gundem-list">
                <?php foreach ($gundem_maddeleri as $gundem): ?>
                    <div class="card mb-3 gundem-item" data-gundem-id="<?php echo $gundem['gundem_id']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-secondary me-2"><?php echo $gundem['sira_no']; ?></span>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($gundem['baslik']); ?></h6>
                                    </div>
                                    <?php if ($gundem['aciklama']): ?>
                                        <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars($gundem['aciklama'])); ?></p>
                                    <?php endif; ?>

                                    <!-- Görüşme Notları Alanı -->
                                    <div class="mt-3 p-3 bg-light rounded container-gorusme-notu">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label text-muted small fw-bold mb-0">
                                                <i class="fas fa-sticky-note me-1"></i>Görüşme Notları
                                            </label>
                                            <span class="small text-muted fst-italic save-status"></span>
                                        </div>
                                        <textarea class="form-control form-control-sm gorusme-notu-input mb-2" rows="2"
                                            placeholder="Bu gündem maddesiyle ilgili görüşme notlarını buraya yazabilirsiniz..."
                                            data-gundem-id="<?php echo $gundem['gundem_id']; ?>"
                                            <?php echo !$canManageContent ? 'readonly' : ''; ?>><?php echo htmlspecialchars($gundem['gorusme_notlari'] ?? ''); ?></textarea>
                                        <div class="text-end">
                                            <?php if ($canManageContent): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success gorusme-notu-kaydet-btn"
                                                data-gundem-id="<?php echo $gundem['gundem_id']; ?>">
                                                <i class="fas fa-save me-1"></i>Notu Kaydet
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-3 mt-3">
                                        <?php if ($gundem['durum'] !== 'beklemede'): ?>
                                            <span class="badge bg-<?php
                                            echo $gundem['durum'] === 'karara_baglandi' ? 'success' :
                                                ($gundem['durum'] === 'gorusuluyor' ? 'warning' :
                                                    ($gundem['durum'] === 'ertelendi' ? 'danger' : 'info'));
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $gundem['durum'])); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($gundem['sunum_dosyasi'] ?? null)): ?>
                                            <a href="/uploads/toplanti/<?php echo htmlspecialchars($gundem['sunum_dosyasi']); ?>"
                                                target="_blank" class="text-decoration-none">
                                                <i class="fas fa-file-pdf me-1"></i>Sunum
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($canManageContent): ?>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary gundem-duzenle-btn"
                                        data-gundem-id="<?php echo $gundem['gundem_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger gundem-sil-btn"
                                        data-gundem-id="<?php echo $gundem['gundem_id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Gündem Ekle Modal -->
<div class="modal fade" id="gundemEkleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gündem Maddesi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="gundemEkleForm">
                    <div class="mb-3">
                        <label class="form-label">Sıra No</label>
                        <input type="number" class="form-control" id="gundem_sira_no"
                            value="<?php echo count($gundem_maddeleri) + 1; ?>" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gündem Maddesi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="gundem_baslik" placeholder="Örn: Bütçe Görüşmeleri"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" id="gundem_aciklama" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select class="form-select" id="gundem_durum">
                            <option value="beklemede">Beklemede</option>
                            <option value="gorusuluyor">Görüşülüyor</option>
                            <option value="karara_baglandi">Karara Bağlandı</option>
                            <option value="ertelendi">Ertelendi</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="gundemKaydetBtn">
                    <i class="fas fa-save me-2"></i>Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Gündem Düzenle Modal -->
<div class="modal fade" id="gundemDuzenleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gündem Maddesi Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="gundemDuzenleForm">
                    <input type="hidden" id="edit_gundem_id">
                    <div class="mb-3">
                        <label class="form-label">Sıra No</label>
                        <input type="number" class="form-control" id="edit_gundem_sira_no" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gündem Maddesi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_gundem_baslik"
                            placeholder="Örn: Bütçe Görüşmeleri" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" id="edit_gundem_aciklama" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Durum</label>
                        <select class="form-select" id="edit_gundem_durum">
                            <option value="beklemede">Beklemede</option>
                            <option value="gorusuluyor">Görüşülüyor</option>
                            <option value="karara_baglandi">Karara Bağlandı</option>
                            <option value="ertelendi">Ertelendi</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="gundemGuncelleBtn">
                    <i class="fas fa-save me-2"></i>Güncelle
                </button>
            </div>
        </div>
    </div>
</div>
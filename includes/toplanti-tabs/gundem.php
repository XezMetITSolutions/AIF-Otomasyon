<!-- Gündem Tab İçeriği -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Gündem Maddeleri</h5>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#gundemEkleModal">
            <i class="fas fa-plus me-1"></i>Gündem Ekle
        </button>
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
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-<?php 
                                            echo $gundem['durum'] === 'karara_baglandi' ? 'success' : 
                                                ($gundem['durum'] === 'gorusuluyor' ? 'warning' : 
                                                ($gundem['durum'] === 'ertelendi' ? 'danger' : 'info')); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $gundem['durum'])); ?>
                                        </span>
                                        <?php if (!empty($gundem['sunum_dosyasi'] ?? null)): ?>
                                            <a href="/uploads/toplanti/<?php echo htmlspecialchars($gundem['sunum_dosyasi']); ?>" target="_blank" class="text-decoration-none">
                                                <i class="fas fa-file-pdf me-1"></i>Sunum
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
                            </div>
                            <!-- Notlar ve Sorumlu Atama Bölümü -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0 fw-bold"><i class="fas fa-sticky-note me-1 text-warning"></i>Notlar & Görevliler</label>
                                    <small class="text-muted">Kişi atamak için <strong>@isim</strong> yazın.</small>
                                </div>
                                <div class="position-relative">
                                    <textarea class="form-control gundem-not-input" 
                                              data-gundem-id="<?php echo $gundem['gundem_id']; ?>"
                                              data-sorumlu-id="<?php echo $gundem['sorumlu_id'] ?? ''; ?>"
                                              rows="2" 
                                              placeholder="Toplantı notlarını buraya girin..."><?php echo htmlspecialchars($gundem['notlar'] ?? ''); ?></textarea>
                                    
                                    <!-- Sorumlu Göstergesi -->
                                    <div class="mt-2 sorumlu-badge-container" id="sorumlu-container-<?php echo $gundem['gundem_id']; ?>">
                                        <?php if (!empty($gundem['sorumlu_id'])): ?>
                                            <?php 
                                            // Sorumlu ismini bul (Performance note: scanning array is fine for small N)
                                            $sorumlu_ad = 'Bilinmeyen Kişi';
                                            foreach ($katilimcilar as $k) {
                                                if ($k['kullanici_id'] == $gundem['sorumlu_id']) {
                                                    $sorumlu_ad = $k['ad'] . ' ' . $k['soyad'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-user-tag me-1"></i>Sorumlu: <?php echo htmlspecialchars($sorumlu_ad); ?>
                                                <i class="fas fa-times ms-2 pointer sorumlu-sil-btn" onclick="ToplantiYonetimi.sorumluSil(<?php echo $gundem['gundem_id']; ?>)"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Auto-complete List Container -->
                                    <div class="mention-list list-group position-absolute shadow-lg" 
                                         id="mention-list-<?php echo $gundem['gundem_id']; ?>" 
                                         style="display:none; z-index: 1000; width: 250px; max-height: 200px; overflow-y: auto;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Global JS Variables for Participants -->
            <script>
                const TOKEN_KATILIMCILAR = <?php 
                    $js_katilimcilar = [];
                    foreach ($katilimcilar as $k) {
                        $js_katilimcilar[] = [
                            'id' => $k['kullanici_id'],
                            'name' => $k['ad'] . ' ' . $k['soyad'],
                            'email' => $k['email']
                        ];
                    }
                    echo json_encode($js_katilimcilar);
                ?>;
            </script>
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
                        <input type="number" class="form-control" id="gundem_sira_no" value="<?php echo count($gundem_maddeleri) + 1; ?>" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlık <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="gundem_baslik" required>
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
                        <label class="form-label">Başlık <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_gundem_baslik" required>
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

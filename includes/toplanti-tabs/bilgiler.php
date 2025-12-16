<!-- Temel Bilgiler Tab İçeriği -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Toplantı Bilgileri</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_toplanti">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">BYK</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($toplanti['byk_adi']); ?>" disabled>
                    <small class="text-muted">BYK değiştirilemez</small>
                </div>
            </div>

            <div class="mb-3">
                <label for="baslik" class="form-label">Toplantı Başlığı</label>
                <input type="text" class="form-control" id="baslik" name="baslik" 
                       value="<?php echo htmlspecialchars($toplanti['baslik']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="aciklama" class="form-label">Gündem</label>
                <textarea class="form-control" id="aciklama" name="aciklama" rows="3"><?php echo htmlspecialchars($toplanti['aciklama'] ?? ''); ?></textarea>
            </div>
            
            <script>
            // Auto-bullet for aciklama
            document.getElementById('aciklama').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    
                    const cursorPosition = this.selectionStart;
                    const currentValue = this.value;
                    const beforeCursor = currentValue.substring(0, cursorPosition);
                    const afterCursor = currentValue.substring(cursorPosition);
                    
                    // Check if current line starts with bullet
                    const lastLineIndex = beforeCursor.lastIndexOf('\n');
                    const lastLine = beforeCursor.substring(lastLineIndex + 1);
                    const bulletMatch = lastLine.match(/^(\s*)([-*•>])\s*/);
                    
                    let insertion = '\n';
                    
                    if (bulletMatch) {
                        insertion += bulletMatch[1] + bulletMatch[2] + ' ';
                    } else if (lastLine.trim().length > 0) {
                        // If no bullet but line exists, start new list? OR just newline
                        // User requirement: "her satırda yeni aufzählungszeichen"
                        // Force bullet if none exists
                         insertion += '• ';
                    } else {
                         insertion += '• ';
                    }
                    
                    this.value = beforeCursor + insertion + afterCursor;
                    this.selectionStart = this.selectionEnd = cursorPosition + insertion.length;
                    
                    // Scroll to bottom/cursor
                    this.blur();
                    this.focus();
                }
            });
            // Init with bullet if empty
            if (document.getElementById('aciklama').value.trim() === '') {
                 document.getElementById('aciklama').value = '• ';
            }
            </script>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="toplanti_tarihi" class="form-label">Başlangıç Tarihi & Saati</label>
                    <input type="datetime-local" class="form-control" id="toplanti_tarihi" name="toplanti_tarihi" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($toplanti['toplanti_tarihi'])); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="konum" class="form-label">Konum</label>
                <input type="text" class="form-control" id="konum" name="konum" 
                       value="<?php echo htmlspecialchars($toplanti['konum'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="durum" class="form-label">Durum</label>
                <select class="form-select" id="durum" name="durum">
                    <option value="planlandi" <?php echo $toplanti['durum'] === 'planlandi' ? 'selected' : ''; ?>>Planlandı</option>
                    <option value="devam_ediyor" <?php echo $toplanti['durum'] === 'devam_ediyor' ? 'selected' : ''; ?>>Devam Ediyor</option>
                    <option value="tamamlandi" <?php echo $toplanti['durum'] === 'tamamlandi' ? 'selected' : ''; ?>>Tamamlandı</option>
                    <option value="iptal" <?php echo $toplanti['durum'] === 'iptal' ? 'selected' : ''; ?>>İptal</option>
                </select>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">İstatistikler</h6>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="stat-box">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h4><?php echo count($katilimcilar); ?></h4>
                    <p class="text-muted mb-0">Toplam Katılımcı</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <i class="fas fa-list fa-2x text-info mb-2"></i>
                    <h4><?php echo count($gundem_maddeleri); ?></h4>
                    <p class="text-muted mb-0">Gündem Maddesi</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <i class="fas fa-gavel fa-2x text-success mb-2"></i>
                    <h4><?php echo count($kararlar); ?></h4>
                    <p class="text-muted mb-0">Alınan Karar</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <i class="fas fa-file-alt fa-2x text-warning mb-2"></i>
                    <h4><?php echo $tutanak ? '1' : '0'; ?></h4>
                    <p class="text-muted mb-0">Tutanak</p>
                </div>
            </div>
        </div>
    </div>
</div>

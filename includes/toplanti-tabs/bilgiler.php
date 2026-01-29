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

            <style>
                .suggestions {
                    position: absolute;
                    z-index: 1000;
                    background: #fff;
                    border: 1px solid #dee2e6;
                    border-radius: 0.25rem;
                    width: 100%;
                    max-height: 200px;
                    overflow-y: auto;
                    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                    display: none;
                }
                .suggestion-item {
                    padding: 0.5rem 0.75rem;
                    cursor: pointer;
                    border-bottom: 1px solid #f8f9fa;
                }
                .suggestion-item:last-child { border-bottom: none; }
                .suggestion-item:hover { background-color: #f8f9fa; color: #009872; }
            </style>

            <div class="mb-3 position-relative">
                <label for="konum" class="form-label">Konum</label>
                <input type="text" class="form-control" id="konum" name="konum" 
                       value="<?php echo htmlspecialchars($toplanti['konum'] ?? ''); ?>" placeholder="Adres aramaya başlayın...">
                <div class="suggestions" id="konumSuggestions"></div>
            </div>

            <script>
            // OpenRouteService API Logic
            if (typeof ORS_API_KEY === 'undefined') {
                var ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjdiYWRhNGRlODEwNjQ1ZjY4NmI0MmMzZDgwOTExODJlIiwiaCI6Im11cm11cjY0In0=';
            }
            
            if (typeof debounce === 'undefined') {
                var debounce = function(fn, delay) {
                    let t;
                    return (...args) => {
                        clearTimeout(t);
                        t = setTimeout(() => fn(...args), delay);
                    };
                };
            }

            if (typeof fetchAddressSuggestions === 'undefined') {
                var fetchAddressSuggestions = async function(query) {
                    if (!query || query.length < 2) return [];
                    const url = `https://api.openrouteservice.org/geocode/autocomplete?api_key=${encodeURIComponent(ORS_API_KEY)}&text=${encodeURIComponent(query)}&size=5&boundary.country=AT,DE,CH&lang=tr`;
                    try {
                        const res = await fetch(url);
                        const data = await res.json();
                        if (!data.features) return [];
                        return data.features.map(f => ({ label: f.properties.label || '' }));
                    } catch (e) {
                        console.error('ORS Error:', e);
                        return [];
                    }
                };
            }

            function initBilgilerTab() {
                const inputEl = document.getElementById('konum');
                const suggestionsEl = document.getElementById('konumSuggestions');

                if (inputEl && suggestionsEl) {
                    const render = (items) => {
                        suggestionsEl.innerHTML = '';
                        if (!items.length) { suggestionsEl.style.display = 'none'; return; }
                        suggestionsEl.style.display = 'block';
                        items.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.textContent = item.label;
                            div.onclick = () => {
                                inputEl.value = item.label;
                                suggestionsEl.style.display = 'none';
                            };
                            suggestionsEl.appendChild(div);
                        });
                    };

                    const debouncedSearch = debounce(async () => {
                        const q = inputEl.value.trim();
                        const items = await fetchAddressSuggestions(q);
                        render(items);
                    }, 300);

                    inputEl.addEventListener('input', debouncedSearch);
                    
                    // Close on blur (delayed to allow click)
                    inputEl.addEventListener('blur', () => setTimeout(() => suggestionsEl.style.display = 'none', 200));
                    
                    // Show if focused and has content
                    inputEl.addEventListener('focus', () => { 
                        if (inputEl.value.trim().length >= 2) debouncedSearch(); 
                    });
                }
            }

            // Standart sayfa yükleme
            document.addEventListener('DOMContentLoaded', initBilgilerTab);
            // AJAX (SPA) sayfa yükleme
            $(document).on('page:loaded', initBilgilerTab);
            // Hemen çalıştır
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                initBilgilerTab();
            }
            </script>

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

        </div>
    </div>
</div>

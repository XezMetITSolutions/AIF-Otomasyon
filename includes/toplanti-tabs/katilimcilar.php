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
                            <button type="button" class="btn btn-warning ms-2" id="btnMailDebug">
                                <i class="fas fa-bug me-2"></i>Mail Debug (Test)
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
                <!-- Mail Debug Sonuç Modal -->
<div class="modal fade" id="mailDebugModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-bug me-2"></i>Mail Debug Sonuçları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mailDebugResults" class="accordion">
                    <div class="text-center p-4">
                        <div class="spinner-border text-warning mb-2"></div>
                        <p>E-postalar test ediliyor, lütfen bekleyin...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnMailDebug = document.getElementById('btnMailDebug');
    if (btnMailDebug) {
        btnMailDebug.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.katilimci-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('Lütfen en az bir katılımcı seçin.');
                return;
            }

            const modal = new bootstrap.Modal(document.getElementById('mailDebugModal'));
            const resultsContainer = document.getElementById('mailDebugResults');
            resultsContainer.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-warning mb-2"></div>
                    <p>E-postalar test ediliyor, lütfen bekleyin...</p>
                </div>`;
            modal.show();

            const toplantiId = new URLSearchParams(window.location.search).get('id');

            fetch('/admin/api-mail-debug.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    toplanti_id: toplantiId,
                    katilimci_ids: selectedIds
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let html = '';
                    data.results.forEach((res, index) => {
                        const statusClass = res.success ? 'text-success' : 'text-danger';
                        const icon = res.success ? 'fa-check-circle' : 'fa-exclamation-triangle';
                        
                        html += `
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#debug-${index}">
                                        <i class="fas ${icon} ${statusClass} me-2"></i>
                                        <strong>${res.name}</strong> (${res.email})
                                        <span class="ms-2 badge ${res.success ? 'bg-success' : 'bg-danger'}">${res.success ? 'BAŞARILI' : 'HATA'}</span>
                                    </button>
                                </h2>
                                <div id="debug-${index}" class="accordion-collapse collapse">
                                    <div class="accordion-body bg-light">
                                        <pre class="small mb-0"><code>${res.log || 'Log bilgisi yok.'}</code></pre>
                                    </div>
                                </div>
                            </div>`;
                    });
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = `<div class="alert alert-danger">${data.error || 'Bir hata oluştu.'}</div>`;
                }
            })
            .catch(err => {
                resultsContainer.innerHTML = `<div class="alert alert-danger">Bağlantı hatası: ${err.message}</div>`;
            });
        });
    }
});
</script>
<?php endif; ?>
<?php
// Note: If adding more script blocks, ensure they are inside the conditional or correctly managed.
?>

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
    <div class="modal-dialog modal-lg"> <!-- Large modal for better list view -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Katılımcı Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <!-- Filters -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Bölge (BYK) Seçimi</label>
                        <select class="form-select" id="modal_byk_select">
                            <option value="">Lütfen BYK seçiniz...</option>
                            <?php foreach ($bykler as $byk): ?>
                                <option value="<?php echo $byk['byk_id']; ?>" <?php echo ($byk['byk_id'] == $toplanti['byk_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($byk['byk_adi'] . ' (' . $byk['byk_kodu'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Katılım Durumu</label>
                        <select class="form-select" id="yeni_katilim_durumu">
                            <option value="beklemede">Davet Edildi (Varsayılan)</option>
                            <option value="katildi">Katıldı</option>
                            <option value="katilacak">Katılacak</option>
                            <option value="ozur_diledi">Özür Diledi</option>
                            <option value="izinli">İzinli</option>
                            <option value="katilmadi">Katılmadı</option>
                        </select>
                    </div>
                    
                    <!-- Search & List -->
                     <div class="col-12 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                             <label class="form-label fw-bold mb-0">Üye Listesi</label>
                             <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="modal_select_all_users">
                                <label class="form-check-label small" for="modal_select_all_users">Tümünü Seç</label>
                            </div>
                        </div>
                        
                        <div class="border rounded p-2 bg-light mb-2">
                            <input type="text" class="form-control form-control-sm" id="modal_user_search" placeholder="İsim veya e-posta ara...">
                        </div>

                        <div class="user-list-container border rounded bg-white p-0" style="height: 300px; overflow-y: auto;">
                            <!-- Users will be loaded here -->
                            <div id="modal_user_list" class="list-group list-group-flush">
                                <div class="text-center p-4 text-muted">
                                    <i class="fas fa-search fa-2x mb-2"></i><br>
                                    Lütfen önce yukarıdan bir Bölge (BYK) seçiniz.
                                </div>
                            </div>
                        </div>
                        <div class="mt-1 text-end">
                            <small class="text-muted" id="selected_count_badge">0 kişi seçildi</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary px-4" id="katilimciEkleBtn">
                    <i class="fas fa-plus-circle me-2"></i>Seçilenleri Ekle
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const bykSelect = document.getElementById('modal_byk_select');
        const userListContainer = document.getElementById('modal_user_list');
        const searchInput = document.getElementById('modal_user_search');
        const selectAllCheck = document.getElementById('modal_select_all_users');
        const countBadge = document.getElementById('selected_count_badge');
        
        // Robust PHP to JS conversion
        let rawIds = <?php echo !empty($katilimcilar) ? json_encode(array_column($katilimcilar, 'kullanici_id')) : '[]'; ?>;
        // Ensure rawIds is an array (handle case where PHP associative array turns into JS object)
        if (rawIds && typeof rawIds === 'object' && !Array.isArray(rawIds)) {
            rawIds = Object.values(rawIds);
        }
        const existingUserIds = Array.isArray(rawIds) ? rawIds.map(String) : [];

        // Initial Load if BYK is selected (and script runs after render)
        if (bykSelect && bykSelect.value) {
            loadUsers(bykSelect.value);
        }

        // Change BYK
        if (bykSelect) {
            bykSelect.addEventListener('change', function() {
                loadUsers(this.value);
            });
        }

        // Search Filter
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                const items = userListContainer.querySelectorAll('.list-group-item');
                
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if(text.includes(term)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        // Select All
        if (selectAllCheck) {
            selectAllCheck.addEventListener('change', function() {
                const visibleCheckboxes = Array.from(userListContainer.querySelectorAll('input[type="checkbox"]'))
                    .filter(cb => cb.closest('.list-group-item').style.display !== 'none');
                    
                visibleCheckboxes.forEach(cb => {
                    if(!cb.disabled) cb.checked = this.checked;
                });
                updateCount();
            });
        }

        // Update Count on individual check
        if (userListContainer) {
            userListContainer.addEventListener('change', function(e) {
                if(e.target.matches('input[type="checkbox"]')) {
                    updateCount();
                }
            });
        }
        
        // Helper: Update count text
        function updateCount() {
            const count = userListContainer.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)').length;
            if (countBadge) countBadge.textContent = count + ' kişi seçildi';
            
            // Update Select All state
            const allCheckable = userListContainer.querySelectorAll('input[type="checkbox"]:not(:disabled)');
            if (selectAllCheck) {
                if(allCheckable.length > 0 && count === allCheckable.length) selectAllCheck.checked = true;
                else if(count === 0) selectAllCheck.checked = false;
                else selectAllCheck.indeterminate = true;
            }
        }

        // Load Users Function
        function loadUsers(bykId) {
            if(!bykId) {
                userListContainer.innerHTML = '<div class="text-center p-4 text-muted">Lütfen BYK seçiniz.</div>';
                return;
            }

            userListContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><div class="mt-2">Yükleniyor...</div></div>';
            
            fetch(`/admin/api-byk-uyeler.php?byk_id=${bykId}`)
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        renderUsers(data.uyeler);
                    } else {
                        userListContainer.innerHTML = `<div class="text-center p-4 text-danger">Hata: ${data.error}</div>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    userListContainer.innerHTML = '<div class="text-center p-4 text-danger">Bağlantı hatası!</div>';
                });
        }

        function renderUsers(users) {
            if(!users || users.length === 0) {
                userListContainer.innerHTML = '<div class="text-center p-4 text-muted">Bu birimde üye bulunamadı.</div>';
                return;
            }

            let html = '';
            users.forEach(u => {
                // Check if already added
                const isAlreadyAdded = existingUserIds.includes(String(u.kullanici_id));
                const disabledAttr = isAlreadyAdded ? 'disabled checked' : '';
                const statusBadge = isAlreadyAdded ? '<span class="badge bg-success ms-2">Eklendi</span>' : '';
                const opacityClass = isAlreadyAdded ? 'opacity-75 bg-light' : '';

                // Role Badge
                const roleBadge = u.rol_adi ? `<span class="badge bg-secondary ms-1" style="font-size:0.7em">${u.rol_adi}</span>` : '';
                const unitBadge = u.alt_birim_adi ? `<span class="badge bg-info text-dark ms-1" style="font-size:0.7em">${u.alt_birim_adi}</span>` : '';

                html += `
                    <label class="list-group-item list-group-item-action d-flex align-items-center justify-content-between cursor-pointer ${opacityClass}">
                        <div class="d-flex align-items-center">
                            <input class="form-check-input me-3" type="checkbox" value="${u.kullanici_id}" ${disabledAttr} ${isAlreadyAdded ? '' : 'name="new_participants[]"'}>
                            <div>
                                <span class="fw-bold">${u.ad} ${u.soyad}</span>
                                ${roleBadge} ${unitBadge}
                                <div class="small text-muted">${u.email}</div>
                            </div>
                        </div>
                        ${statusBadge}
                    </label>
                `;
            });
            userListContainer.innerHTML = html;
            updateCount();
        }
        
    } catch(e) {
        console.error("Katılımcı modal script hatası:", e);
    }
});
</script>

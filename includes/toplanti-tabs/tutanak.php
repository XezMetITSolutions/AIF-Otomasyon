<!-- Tutanak Tab İçeriği -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Toplantı Tutanağı</h5>
    </div>
    <div class="card-body">
        <form id="tutanakForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tutanak No</label>
                    <input type="text" class="form-control" id="tutanak_no" 
                           value="<?php echo htmlspecialchars($tutanak['tutanak_no'] ?? ''); ?>" 
                           placeholder="Örn: 2024/001">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tutanak Tarihi</label>
                    <input type="date" class="form-control" id="tutanak_tarihi" 
                           value="<?php echo $tutanak['tutanak_tarihi'] ?? date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Tutanak Metni</label>
                <div class="border rounded p-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertTemplate()">
                        <i class="fas fa-file-alt me-1"></i>Şablon Ekle
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertGundem()">
                        <i class="fas fa-list me-1"></i>Gündem Ekle
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertKararlar()">
                        <i class="fas fa-gavel me-1"></i>Kararları Ekle
                    </button>
                </div>
                <textarea class="form-control" id="tutanak_metni" rows="20" 
                          placeholder="Toplantı tutanağını buraya yazınız..."><?php echo htmlspecialchars($tutanak['tutanak_metni'] ?? ''); ?></textarea>
                <small class="text-muted">Son kayıt: <?php echo $tutanak ? date('d.m.Y H:i', strtotime($tutanak['guncelleme_tarihi'])) : 'Henüz kaydedilmedi'; ?></small>
            </div>

            <div class="mb-3">
                <label class="form-label">Durum</label>
                <select class="form-select" id="tutanak_durum">
                    <option value="taslak" <?php echo ($tutanak['durum'] ?? 'taslak') === 'taslak' ? 'selected' : ''; ?>>Taslak</option>
                    <option value="onay_bekliyor" <?php echo ($tutanak['durum'] ?? '') === 'onay_bekliyor' ? 'selected' : ''; ?>>Onay Bekliyor</option>
                    <option value="onaylandi" <?php echo ($tutanak['durum'] ?? '') === 'onaylandi' ? 'selected' : ''; ?>>Onaylandı</option>
                </select>
            </div>

            <div class="d-grid gap-2">
                <button type="button" class="btn btn-primary btn-lg" id="tutanakKaydetBtn">
                    <i class="fas fa-save me-2"></i>Tutanağı Kaydet
                </button>
                <button type="button" class="btn btn-outline-secondary" id="otomatikKaydetBtn">
                    <i class="fas fa-clock me-2"></i>Otomatik Kayıt: Kapalı
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Otomatik kayıt
let autoSaveInterval = null;
let autoSaveEnabled = false;

document.getElementById('otomatikKaydetBtn').addEventListener('click', function() {
    autoSaveEnabled = !autoSaveEnabled;
    
    if (autoSaveEnabled) {
        this.innerHTML = '<i class="fas fa-clock me-2"></i>Otomatik Kayıt: Açık';
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-success');
        
        // Her 30 saniyede bir kaydet
        autoSaveInterval = setInterval(function() {
            saveTutanak(true);
        }, 30000);
    } else {
        this.innerHTML = '<i class="fas fa-clock me-2"></i>Otomatik Kayıt: Kapalı';
        this.classList.remove('btn-success');
        this.classList.add('btn-outline-secondary');
        
        if (autoSaveInterval) {
            clearInterval(autoSaveInterval);
        }
    }
});

// Şablon ekle
function insertTemplate() {
    const template = `BAŞKANLIK DİVANI TOPLANTI TUTANAĞI

Toplantı No: ${document.getElementById('tutanak_no').value || '[TUTANAK NO]'}
Tarih: <?php echo date('d.m.Y', strtotime($toplanti['toplanti_tarihi'])); ?>
Saat: <?php echo date('H:i', strtotime($toplanti['toplanti_tarihi'])); ?>
Yer: <?php echo htmlspecialchars($toplanti['konum'] ?? '[KONUM]'); ?>

KATILIMCILAR:
<?php foreach ($katilimcilar as $k): ?>
<?php if ($k['katilim_durumu'] === 'katildi'): ?>
- <?php echo htmlspecialchars($k['ad'] . ' ' . $k['soyad']); ?> (<?php echo htmlspecialchars($k['alt_birim_adi'] ?? ''); ?>)
<?php endif; ?>
<?php endforeach; ?>

GÜNDEM:
[Gündem maddeleri buraya eklenecek]

GÖRÜŞMELER VE KARARLAR:
[Görüşmeler ve kararlar buraya eklenecek]

SONUÇ:
Toplantı saat [SAAT]'te sona ermiştir.
`;
    
    document.getElementById('tutanak_metni').value = template;
}

// Gündem ekle
function insertGundem() {
    let gundemText = '\nGÜNDEM MADDELERİ:\n\n';
    <?php foreach ($gundem_maddeleri as $g): ?>
    gundemText += '<?php echo $g['sira_no']; ?>. <?php echo addslashes($g['baslik']); ?>\n';
    <?php if ($g['aciklama']): ?>
    gundemText += '   <?php echo addslashes($g['aciklama']); ?>\n';
    <?php endif; ?>
    gundemText += '\n';
    <?php endforeach; ?>
    
    const textarea = document.getElementById('tutanak_metni');
    textarea.value += gundemText;
}

// Kararları ekle
function insertKararlar() {
    let kararlarText = '\nALINAN KARARLAR:\n\n';
    <?php foreach ($kararlar as $k): ?>
    kararlarText += 'KARAR <?php echo $k['karar_no'] ? addslashes($k['karar_no']) : '[NO]'; ?>:\n';
    kararlarText += '<?php echo addslashes($k['karar_metni']); ?>\n';
    <?php if ($k['oylama_yapildi']): ?>
    kararlarText += 'Oylama: Kabul: <?php echo $k['kabul_oyu']; ?>, Red: <?php echo $k['red_oyu']; ?>, Çekimser: <?php echo $k['cekinser_oyu']; ?>\n';
    kararlarText += 'Sonuç: <?php echo strtoupper($k['karar_sonucu']); ?>\n';
    <?php endif; ?>
    kararlarText += '\n';
    <?php endforeach; ?>
    
    const textarea = document.getElementById('tutanak_metni');
    textarea.value += kararlarText;
}

// Tutanak kaydet
function saveTutanak(isAutoSave = false) {
    const data = {
        action: 'save_tutanak',
        toplanti_id: <?php echo $toplanti_id; ?>,
        tutanak_no: document.getElementById('tutanak_no').value,
        tutanak_tarihi: document.getElementById('tutanak_tarihi').value,
        tutanak_metni: document.getElementById('tutanak_metni').value,
        durum: document.getElementById('tutanak_durum').value
    };
    
    fetch('/admin/api-toplanti-tutanak.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (!isAutoSave) {
                showAlert('success', 'Tutanak başarıyla kaydedildi!');
            } else {
                console.log('Otomatik kayıt yapıldı');
            }
        } else {
            showAlert('danger', 'Hata: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (!isAutoSave) {
            showAlert('danger', 'Tutanak kaydedilirken hata oluştu');
        }
    });
}

document.getElementById('tutanakKaydetBtn').addEventListener('click', function() {
    saveTutanak(false);
});

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

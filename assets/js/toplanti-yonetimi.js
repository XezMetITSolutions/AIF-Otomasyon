/**
 * Toplantı Yönetimi - JavaScript
 * Tüm AJAX işlemleri ve dinamik etkileşimler
 */

var ToplantiYonetimi = {
    toplanti_id: null,

    init: function (toplantiId) {
        this.toplanti_id = toplantiId;
        this.initEventListeners();
        this.initMentions();
    },

    initMentions: function () {
        if (typeof Tribute === 'undefined' || typeof MEETING_PARTICIPANTS === 'undefined') return;

        const tribute = new Tribute({
            values: MEETING_PARTICIPANTS,
            selectTemplate: function (item) {
                return '@' + item.original.value;
            },
            menuItemTemplate: function (item) {
                return item.string;
            }
        });

        // Attach to existing inputs
        const inputs = document.querySelectorAll('.gorusme-notu-input, .decision-text-input');
        if (inputs.length > 0) {
            tribute.attach(inputs);
        }
    },

    initEventListeners: function () {
        // Katılımcı İşlemleri
        document.getElementById('katilimciEkleBtn')?.addEventListener('click', () => this.katilimciEkle());

        document.querySelectorAll('.katilim-durum-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const katilimciId = e.target.dataset.katilimciId;
                const durum = e.target.value;
                this.katilimDurumuGuncelle(katilimciId, durum);
            });
        });

        document.querySelectorAll('.katilimci-sil-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const katilimciId = e.target.closest('button').dataset.katilimciId;
                const ad = e.target.closest('button').dataset.ad;
                this.katilimciSil(katilimciId, ad);
            });
        });

        // Gündem İşlemleri
        document.getElementById('gundemKaydetBtn')?.addEventListener('click', () => this.gundemEkle());
        document.getElementById('gundemGuncelleBtn')?.addEventListener('click', () => this.gundemGuncelle());

        document.querySelectorAll('.gundem-duzenle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const gundemId = e.target.closest('button').dataset.gundemId;
                this.gundemDuzenleModalAc(gundemId);
            });
        });

        document.querySelectorAll('.gundem-sil-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const gundemId = e.target.closest('button').dataset.gundemId;
                this.gundemSil(gundemId);
            });
        });

        // Karar İşlemleri
        document.getElementById('kararKaydetBtn')?.addEventListener('click', () => this.kararEkle());

        document.querySelectorAll('.karar-duzenle-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const kararId = e.target.closest('button').dataset.kararId;
                this.kararDuzenleModalAc(kararId);
            });
        });

        document.querySelectorAll('.karar-sil-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const kararId = e.target.closest('button').dataset.kararId;
                this.kararSil(kararId);
            });
        });

        // Görüşme Notu İşlemleri
        document.querySelectorAll('.gorusme-notu-kaydet-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                const gundemId = btn.dataset.gundemId;
                // Find textarea in same container
                const container = btn.closest('.container-gorusme-notu');
                const textarea = container.querySelector('.gorusme-notu-input');
                const notlar = textarea.value.trim();

                this.gorusmeNotuKaydet(gundemId, notlar, btn);
            });
        });

        // Auto-bullet logic
        document.querySelectorAll('.gorusme-notu-input').forEach(textarea => {
            textarea.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    const cursorPosition = this.selectionStart;
                    const currentValue = this.value;
                    const beforeCursor = currentValue.substring(0, cursorPosition);
                    const lastLineIndex = beforeCursor.lastIndexOf('\n');
                    const lastLine = beforeCursor.substring(lastLineIndex + 1);

                    const bulletMatch = lastLine.match(/^(\s*)([-*•])\s+/);

                    if (bulletMatch) {
                        e.preventDefault();
                        const bullet = `\n${bulletMatch[1]}${bulletMatch[2]} `;
                        const afterCursor = currentValue.substring(cursorPosition);

                        this.value = beforeCursor + bullet + afterCursor;
                        this.selectionStart = this.selectionEnd = cursorPosition + bullet.length;
                    }
                }
            });

            // Auto-save logic
            let debounceTimer;
            textarea.addEventListener('input', (e) => {
                const gundemId = e.target.dataset.gundemId;
                const notlar = e.target.value.trim();
                const container = e.target.closest('.container-gorusme-notu');
                const statusSpan = container.querySelector('.save-status');

                statusSpan.textContent = 'Kaydediliyor...';

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.autoSaveNote(gundemId, notlar, statusSpan);
                }, 1000);
            });
        });

        // Karar Metni İşlemleri (Görüşme Notu Gibi)
        document.querySelectorAll('.decision-save-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                const gundemId = btn.dataset.gundemId;
                const toplantiId = btn.dataset.toplantiId;
                const container = btn.closest('.container-karar-text');
                const textarea = container.querySelector('.decision-text-input');
                const text = textarea.value.trim();

                this.saveDecisionManual(toplantiId, gundemId, text, btn);
            });
        });

        document.querySelectorAll('.decision-text-input').forEach(textarea => {
            let debounceTimer;
            textarea.addEventListener('input', (e) => {
                const gundemId = e.target.dataset.gundemId;
                const toplantiId = e.target.dataset.toplantiId;
                const text = e.target.value.trim();
                const container = e.target.closest('.container-karar-text');
                const statusSpan = container.querySelector('.save-status-decision');

                statusSpan.textContent = 'Kaydediliyor...';

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.autoSaveDecision(toplantiId, gundemId, text, statusSpan);
                }, 1000);
            });
        });
    },

    // ==================== KATILIMCI İŞLEMLERİ ====================

    katilimciEkle: function () {
        const checkboxes = document.querySelectorAll('input[name="new_participants[]"]:checked');
        const katilimDurumu = document.getElementById('yeni_katilim_durumu').value;
        let ids = [];

        checkboxes.forEach(cb => ids.push(cb.value));

        // Fallback for old single select if it ever exists
        const singleSelect = document.getElementById('yeni_katilimci_id');
        if (singleSelect && singleSelect.value) {
            ids.push(singleSelect.value);
        }

        if (ids.length === 0) {
            this.showAlert('warning', 'Lütfen en az bir katılımcı seçiniz');
            return;
        }

        const btn = document.getElementById('katilimciEkleBtn');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ekleniyor...';

        const data = {
            action: 'add',
            toplanti_id: this.toplanti_id,
            kullanici_id: ids,
            katilim_durumu: katilimDurumu
        };

        this.apiRequest('/admin/api-toplanti-katilimci.php', data)
            .then(result => {
                if (result.success) {
                    this.showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    this.showAlert('danger', result.error);
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
    },

    katilimDurumuGuncelle: function (katilimciId, durum) {
        const data = {
            action: 'update',
            katilimci_id: katilimciId,
            katilim_durumu: durum
        };

        this.apiRequest('/admin/api-toplanti-katilimci.php', data)
            .then(result => {
                if (result.success) {
                    this.showAlert('success', 'Katılım durumu güncellendi', 2000);
                } else {
                    this.showAlert('danger', result.error);
                }
            });
    },

    katilimciSil: function (katilimciId, ad) {
        if (!confirm(`${ad} isimli katılımcıyı silmek istediğinize emin misiniz?`)) {
            return;
        }

        const data = {
            action: 'delete',
            katilimci_id: katilimciId
        };

        this.apiRequest('/admin/api-toplanti-katilimci.php', data)
            .then(result => {
                if (result.success) {
                    this.showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showAlert('danger', result.error);
                }
            });
    },

    // ==================== GÜNDEM İŞLEMLERİ ====================

    gundemEkle: function () {
        const siraNo = document.getElementById('gundem_sira_no').value;
        const baslik = document.getElementById('gundem_baslik').value.trim();
        const aciklama = document.getElementById('gundem_aciklama').value.trim();
        const durum = document.getElementById('gundem_durum').value;

        if (!baslik) {
            this.showAlert('warning', 'Başlık gereklidir');
            return;
        }

        const data = {
            action: 'add',
            toplanti_id: this.toplanti_id,
            sira_no: siraNo,
            baslik: baslik,
            aciklama: aciklama,
            durum: durum
        };

        this.apiRequest('/admin/api-toplanti-gundem.php', data)
            .then(result => {
                if (result.success) {
                    this.showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showAlert('danger', result.error);
                }
            });
    },

    gundemDuzenleModalAc: function (gundemId) {
        fetch(`/admin/api-toplanti-gundem.php?action=get&gundem_id=${gundemId}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const gundem = result.gundem;
                    document.getElementById('edit_gundem_id').value = gundem.gundem_id;
                    document.getElementById('edit_gundem_sira_no').value = gundem.sira_no;
                    document.getElementById('edit_gundem_baslik').value = gundem.baslik;
                    document.getElementById('edit_gundem_aciklama').value = gundem.aciklama || '';
                    document.getElementById('edit_gundem_durum').value = gundem.durum;

                    const modal = new bootstrap.Modal(document.getElementById('gundemDuzenleModal'));
                    modal.show();
                } else {
                    this.showAlert('danger', result.error);
                }
            });
    },

    gundemGuncelle: function () {
        const gundemId = document.getElementById('edit_gundem_id').value;
        const siraNo = document.getElementById('edit_gundem_sira_no').value;
        const baslik = document.getElementById('edit_gundem_baslik').value.trim();
        const aciklama = document.getElementById('edit_gundem_aciklama').value.trim();
        const durum = document.getElementById('edit_gundem_durum').value;

        if (!baslik) {
            this.showAlert('warning', 'Başlık gereklidir');
            return;
        }

        const data = {
            action: 'update',
            gundem_id: gundemId,
            sira_no: siraNo,
            baslik: baslik,
            aciklama: aciklama,
            durum: durum
        };

        this.apiRequest('/admin/api-toplanti-gundem.php', data)
            .then(result => {
                if (result.success) {
                    this.showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showAlert('danger', result.error);
                }
            });
    },

    gundemSil: function (gundemId) {
        if (!confirm('Bu gündem maddesini silmek istediğinize emin misiniz?')) {
            return;
        }

        const data = {
            action: 'delete',
            gundem_id: gundemId
        };

        this.apiRequest('/admin/api-toplanti-gundem.php', data)
            .then(result => {
                if (result.success) {
                    this.showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showAlert('danger', result.error);
                }
            });
    },

    gorusmeNotuKaydet: function (gundemId, notlar, btn) {
        // Loading state
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kaydediliyor...';
        btn.disabled = true;

        const data = {
            gundem_id: gundemId,
            notlar: notlar
        };

        this.apiRequest('/api/update-agenda-note.php', data)
            .then(result => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;

                if (result.success) {
                    this.showAlert('success', 'Görüşme notu kaydedildi');
                } else {
                    this.showAlert('danger', result.message || 'Hata oluştu');
                }
            });
    },

    autoSaveNote: function (gundemId, notlar, statusSpan) {
        const data = {
            gundem_id: gundemId,
            notlar: notlar
        };

        fetch('/api/update-agenda-note.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    statusSpan.textContent = 'Kaydedildi';
                    statusSpan.classList.add('text-success');
                    setTimeout(() => {
                        statusSpan.textContent = '';
                        statusSpan.classList.remove('text-success');
                    }, 2000);
                } else {
                    statusSpan.textContent = 'Hata!';
                    statusSpan.classList.add('text-danger');
                }
            })
            .catch(err => {
                console.error(err);
                statusSpan.textContent = 'Hata!';
                statusSpan.classList.add('text-danger');
            });
    },

    saveDecisionManual: function (toplantiId, gundemId, text, btn) {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kaydediliyor...';
        btn.disabled = true;

        const data = {
            toplanti_id: toplantiId,
            gundem_id: gundemId,
            karar_metni: text
        };

        this.apiRequest('/api/update-decision-text.php', data)
            .then(result => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;

                if (result.success) {
                    this.showAlert('success', 'Karar kaydedildi');
                } else {
                    this.showAlert('danger', result.message || 'Hata oluştu');
                }
            });
    },

    autoSaveDecision: function (toplantiId, gundemId, text, statusSpan) {
        const data = {
            toplanti_id: toplantiId,
            gundem_id: gundemId,
            karar_metni: text
        };

        fetch('/api/update-decision-text.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    statusSpan.textContent = 'Kaydedildi';
                    statusSpan.classList.add('text-success');
                    setTimeout(() => {
                        statusSpan.textContent = '';
                        statusSpan.classList.remove('text-success');
                    }, 2000);
                } else {
                    statusSpan.textContent = 'Hata!';
                    statusSpan.classList.add('text-danger');
                }
            })
            .catch(err => {
                console.error(err);
                statusSpan.textContent = 'Hata!';
                statusSpan.classList.add('text-danger');
            });
    },

    // ==================== KARAR İŞLEMLERİ ====================

    kararEkle: function () {
        const kararNo = document.getElementById('karar_no').value.trim();
        const gundemId = document.getElementById('karar_gundem_id').value;
        const baslik = document.getElementById('karar_baslik').value.trim();
        const kararMetni = document.getElementById('karar_metni').value.trim();
        const oylamaYapildi = document.getElementById('oylama_yapildi').checked ? 1 : 0;
        const kabulOyu = document.getElementById('kabul_oyu').value;
        const redOyu = document.getElementById('red_oyu').value;
        const cekinserOyu = document.getElementById('cekinser_oyu').value;
        const kararSonucu = document.getElementById('karar_sonucu').value;

        if (!baslik || !kararMetni) {
            this.showAlert('warning', 'Başlık ve karar metni gereklidir');
            return;
        }

        const data = {
            action: 'add',
            toplanti_id: this.toplanti_id,
            gundem_id: gundemId || null,
            karar_no: kararNo,
            baslik: baslik,
            karar_metni: kararMetni,
            oylama_yapildi: oylamaYapildi,
            kabul_oyu: kabulOyu,
            red_oyu: redOyu,
            cekinser_oyu: cekinserOyu,
            karar_sonucu: kararSonucu || null
        };

        this.apiRequest('/admin/api-toplanti-karar.php', data)
            .then(result => {
                if (result.success) {
                    this.showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showAlert('danger', result.error);
                }
            });
    },

    kararDuzenleModalAc: function (kararId) {
        // TODO: Implement karar düzenleme modal
        this.showAlert('info', 'Karar düzenleme özelliği yakında eklenecek');
    },

    kararSil: function (kararId) {
        if (!confirm('Bu kararı silmek istediğinize emin misiniz?')) {
            return;
        }

        const data = {
            action: 'delete',
            karar_id: kararId
        };

        this.apiRequest('/admin/api-toplanti-karar.php', data)
            .then(result => {
                if (result.success) {
                    this.showAlert('success', result.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    this.showAlert('danger', result.error);
                }
            });
    },

    // ==================== YARDIMCI FONKSİYONLAR ====================

    apiRequest: function (url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('danger', 'Bir hata oluştu');
                return { success: false, error: 'Network error' };
            });
    },

    showAlert: function (type, message, duration = 3000) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, duration);
    }
};

// Başlatma fonksiyonu
function initToplantiYonetimi() {
    const toplantiIdElement = document.querySelector('[data-toplanti-id]');
    if (toplantiIdElement) {
        const toplantiId = toplantiIdElement.dataset.toplantiId;
        ToplantiYonetimi.init(toplantiId);
    }
}

// Standart sayfa yükleme
document.addEventListener('DOMContentLoaded', initToplantiYonetimi);

// AJAX (SPA) sayfa yükleme
$(document).on('page:loaded', initToplantiYonetimi);

// Eğer script gecikmeli yüklendiyse ve DOM zaten hazırsa hemen çalıştır
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initToplantiYonetimi();
}

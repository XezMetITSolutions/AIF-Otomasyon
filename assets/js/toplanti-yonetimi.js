/**
 * Toplantı Yönetimi - JavaScript
 * Tüm AJAX işlemleri ve dinamik etkileşimler
 */

const ToplantiYonetimi = {
    toplanti_id: null,

    init: function (toplantiId) {
        this.toplanti_id = toplantiId;
        this.initEventListeners();
        this.initMentionSystem();
        this.handleHashNavigation();
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

        // Tab Değişimi Manuel Tetikleme (Bootstrap Conflict Fix)
        document.querySelectorAll('a[data-bs-toggle="pill"]').forEach(tabEl => {
            tabEl.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof bootstrap !== 'undefined') {
                    const target = e.target.closest('a'); // Icon'a tıklanırsa a'yı bul
                    const tab = new bootstrap.Tab(target);
                    tab.show();
                }
            });
        });
    },

    handleHashNavigation: function () {
        const hash = window.location.hash;
        if (hash) {
            const triggerEl = document.querySelector(`a[data-bs-toggle="pill"][href="${hash}"]`);
            if (triggerEl && typeof bootstrap !== 'undefined') {
                const tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }
    },

    // ==================== KATILIMCI İŞLEMLERİ ====================

    katilimciEkle: function () {
        const kullaniciId = document.getElementById('yeni_katilimci_id').value;
        const katilimDurumu = document.getElementById('yeni_katilim_durumu').value;

        if (!kullaniciId) {
            this.showAlert('warning', 'Lütfen bir kullanıcı seçiniz');
            return;
        }

        const data = {
            action: 'add',
            toplanti_id: this.toplanti_id,
            kullanici_id: kullaniciId,
            katilim_durumu: katilimDurumu
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
    },

    // ==================== NOT & MENTION SİSTEMİ ====================

    initMentionSystem: function () {
        document.querySelectorAll('.gundem-not-input').forEach(textarea => {
            // Autosave timer
            let timeoutId;

            textarea.addEventListener('input', (e) => {
                const gundemId = e.target.dataset.gundemId;
                const val = e.target.value;
                const cursorPos = e.target.selectionStart;

                // Handle @ Mention
                this.handleMentionTrigger(e.target, val, cursorPos, gundemId);

                // Debounce Autosave
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    this.gundemNotKaydet(gundemId, val, e.target.dataset.sorumluId);
                }, 1000);
            });

            // Hide checklist on click outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.mention-list')) {
                    document.querySelectorAll('.mention-list').forEach(el => el.style.display = 'none');
                }
            });
        });
    },

    handleMentionTrigger: function (textarea, val, cursorPos, gundemId) {
        // Check if cursor is after @
        const lastAt = val.lastIndexOf('@', cursorPos - 1);
        if (lastAt !== -1) {
            const query = val.substring(lastAt + 1, cursorPos);
            if (query.length < 20) {
                this.showMentionList(gundemId, query, lastAt, textarea);
            } else {
                this.hideMentionList(gundemId);
            }
        } else {
            this.hideMentionList(gundemId);
        }
    },

    showMentionList: function (gundemId, query, atIndex, textarea) {
        const listEl = document.getElementById(`mention-list-${gundemId}`);
        if (!listEl) return;

        // Filter participants
        const users = (typeof TOKEN_KATILIMCILAR !== 'undefined') ? TOKEN_KATILIMCILAR : [];
        const filtered = users.filter(u => u.name.toLowerCase().includes(query.toLowerCase()));

        if (filtered.length === 0) {
            listEl.style.display = 'none';
            return;
        }

        let html = '';
        filtered.forEach(u => {
            html += `
                <button type="button" class="list-group-item list-group-item-action" 
                    onclick="ToplantiYonetimi.selectMention(${gundemId}, '${u.id}', '${u.name}', ${atIndex}, '${query}', this)">
                    <div class="d-flex align-items-center">
                        <div class="ms-2">
                            <div class="fw-bold">${u.name}</div>
                            <small class="text-muted">${u.email}</small>
                        </div>
                    </div>
                </button>
            `;
        });

        listEl.innerHTML = html;
        listEl.style.display = 'block';
    },

    hideMentionList: function (gundemId) {
        const listEl = document.getElementById(`mention-list-${gundemId}`);
        if (listEl) listEl.style.display = 'none';
    },

    selectMention: function (gundemId, userId, userName, atIndex, query, btnElement) {
        const textarea = document.querySelector(`.gundem-not-input[data-gundem-id="${gundemId}"]`);
        if (!textarea) return;

        const text = textarea.value;
        const before = text.substring(0, atIndex);
        const after = text.substring(atIndex + 1 + query.length);

        textarea.value = before + '@' + userName + ' ' + after;

        this.sorumluAta(gundemId, userId, userName);
        this.hideMentionList(gundemId);
        textarea.focus();

        this.gundemNotKaydet(gundemId, textarea.value, userId);
    },

    sorumluAta: function (gundemId, userId, userName) {
        const container = document.getElementById(`sorumlu-container-${gundemId}`);
        container.innerHTML = `
            <span class="badge bg-primary">
                <i class="fas fa-user-tag me-1"></i>Sorumlu: ${userName}
                <i class="fas fa-times ms-2 pointer sorumlu-sil-btn" onclick="ToplantiYonetimi.sorumluSil(${gundemId})"></i>
            </span>
        `;

        const textarea = document.querySelector(`.gundem-not-input[data-gundem-id="${gundemId}"]`);
        if (textarea) textarea.dataset.sorumluId = userId;
    },

    sorumluSil: function (gundemId) {
        const container = document.getElementById(`sorumlu-container-${gundemId}`);
        container.innerHTML = '';

        const textarea = document.querySelector(`.gundem-not-input[data-gundem-id="${gundemId}"]`);
        if (textarea) {
            textarea.dataset.sorumluId = '';
            this.gundemNotKaydet(gundemId, textarea.value, null);
        }
    },

    gundemNotKaydet: function (gundemId, notlar, sorumluId) {
        const data = {
            gundem_id: gundemId,
            notlar: notlar,
            sorumlu_id: sorumluId || null
        };

        fetch('/api/gundem-islem.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    console.error('Save failed:', res.error);
                }
            });
    }
};

// Sayfa yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', function () {
    const toplantiIdElement = document.querySelector('[data-toplanti-id]');
    if (toplantiIdElement) {
        const toplantiId = toplantiIdElement.dataset.toplantiId;
        ToplantiYonetimi.init(toplantiId);
    }
});

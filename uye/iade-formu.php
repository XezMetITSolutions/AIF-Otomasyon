<?php
/**
 * Üye - İade Talebi Formu
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

Middleware::requireUye();
Middleware::requireModulePermission('uye_iade_formu');

$auth = new Auth();
$user = $auth->getUser();
$db = Database::getInstance();

$uyeDetay = $db->fetch("
    SELECT k.ad, k.soyad, b.byk_kodu, b.byk_adi
    FROM kullanicilar k
    LEFT JOIN byk b ON k.byk_id = b.byk_id
    WHERE k.kullanici_id = ?
", [$user['id']]);

$uyeAd = $uyeDetay['ad'] ?? explode(' ', $user['name'])[0];
$uyeSoyad = $uyeDetay['soyad'] ?? (explode(' ', $user['name'])[1] ?? '');
$uyeBykKodu = $uyeDetay['byk_kodu'] ?? '';
$uyeBykAdi = $uyeDetay['byk_adi'] ?? '';

$pageTitle = 'İade Talebi Formu';
$formBasePath = '/Hesaplama';

include __DIR__ . '/../includes/header.php';
?>


        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        <style>
            :root {
                --bg: #ffffff;
                --card: #ffffff;
                --muted: #6b7280;
                --text: #000000;
                --primary: #009872;
                --primary-600: #007a5e;
                --accent: #009872;
                --danger: #dc2626;
                --warning: #d97706;
                --border: #e5e7eb;
                --input: #ffffff;
            }
            #iade-form-wrapper * { box-sizing: border-box; }
            #iade-form-wrapper {
                font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
                background: radial-gradient(1200px 600px at 20% 0%, rgba(0,152,114,.05), transparent 60%),
                            radial-gradient(900px 500px at 80% 0%, rgba(0,152,114,.03), transparent 60%),
                            var(--bg);
                color: var(--text);
                margin: 0;
                padding: 0;
            }
            #iade-form-wrapper .container {
                max-width: 1000px;
                margin: 0 auto;
                padding: 24px;
                background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9));
                border: 2px solid var(--primary);
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0,152,114,.1), inset 0 1px 0 rgba(255,255,255,.9);
                backdrop-filter: blur(8px);
            }
            #iade-form-wrapper h2 {
                margin: 0 0 16px;
                text-align: center;
                font-weight: 700;
                letter-spacing: .3px;
                color: var(--primary);
                font-size: 28px;
            }
            #iade-form-wrapper .subtitle {
                text-align: center;
                color: var(--muted);
                margin-bottom: 24px;
                font-size: 14px;
            }
            #iade-form-wrapper .form-row {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
            }
            #iade-form-wrapper .form-group {
                margin-bottom: 14px;
            }
            #iade-form-wrapper .form-group label {
                display: block;
                margin-bottom: 6px;
                font-size: 13px;
                color: var(--muted);
            }
            #iade-form-wrapper .form-group input,
            #iade-form-wrapper .form-group select,
            #iade-form-wrapper .form-group textarea {
                width: 100%;
                padding: 10px 12px;
                background: var(--input);
                color: var(--text);
                border: 1px solid var(--border);
                border-radius: 10px;
                outline: none;
                transition: border-color .15s, box-shadow .15s;
            }
            #iade-form-wrapper .form-group input:focus,
            #iade-form-wrapper .form-group select:focus,
            #iade-form-wrapper .form-group textarea:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(0,152,114,.15);
            }
            #iade-form-wrapper .form-group textarea {
                resize: vertical;
                min-height: 70px;
            }
            #iade-form-wrapper .items-container { margin: 8px 0 16px; }
            #iade-form-wrapper .item {
                border: 1px solid var(--border);
                padding: 14px;
                border-radius: 12px;
                margin-bottom: 12px;
                background: linear-gradient(180deg, rgba(255,255,255,.9), rgba(248,250,252,.7));
                border: 1px solid var(--primary);
            }
            #iade-form-wrapper .add-item-button {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 14px;
                background-color: var(--primary);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-weight: 600;
            }
            #iade-form-wrapper .add-item-button:hover { background-color: var(--primary-600); }
            #iade-form-wrapper .remove-item-button {
                padding: 6px 10px;
                background-color: var(--danger);
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                float: right;
            }
            #iade-form-wrapper .button-container { text-align: right; margin-top: 16px; }
            #iade-form-wrapper button[type="submit"] {
                padding: 10px 16px;
                background-color: var(--accent);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-weight: 600;
            }
            #iade-form-wrapper button[type="submit"]:hover { filter: brightness(1.05); }
            #spinner {
                display: none;
                margin: 12px auto 0;
                width: 40px; height: 40px;
                border: 4px solid rgba(255,255,255,.2);
                border-top-color: var(--primary);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin { to { transform: rotate(360deg); } }
            .error-message { color: #fca5a5; font-weight: 600; margin-top: 10px; }
            .route-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            .calculate-km-btn {
                background-color: var(--accent);
                color: white;
                padding: 8px 12px;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                margin-top: 6px;
                font-weight: 600;
            }
            .calculate-km-btn:hover { filter: brightness(1.05); }
            .km-loading { display: none; color: var(--accent); font-size: 12px; margin-top: 6px; }
            .suggestions {
                position: absolute;
                left: 0; right: 0;
                z-index: 10;
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 10px;
                display: none;
                overflow: hidden;
                box-shadow: 0 10px 20px rgba(0,0,0,.15);
            }
            .suggestion-item {
                padding: 10px 12px;
                cursor: pointer;
                border-bottom: 1px solid rgba(226,232,240,.6);
            }
            .suggestion-item:last-child { border-bottom: 0; }
            .suggestion-item:hover { background: rgba(0,152,114,.08); }

            /* CSS Overrides for Mobile Layout Fix */
            .dashboard-layout {
                display: block;
            }

            .sidebar-wrapper {
                display: none;
            }

            .content-wrapper {
                width: 100% !important;
                min-width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                padding: 1rem !important;
                background: transparent !important;
                box-shadow: none !important;
            }

            .main-content {
                width: 100%;
            }

            /* Desktop View */
            @media (min-width: 992px) {
                .dashboard-layout {
                    display: flex;
                    flex-direction: row;
                }

                .sidebar-wrapper {
                    display: block;
                    width: 250px;
                    flex-shrink: 0;
                    z-index: 1000;
                }
                
                .main-content {
                    flex-grow: 1;
                    width: auto;
                }

                .content-wrapper {
                    padding: 1.5rem 2rem !important;
                    max-width: 1400px !important;
                    margin: 0 auto !important;
                }
            }
        </style>

        <div class="dashboard-layout">
            <!-- Sidebar Wrapper -->
            <div class="sidebar-wrapper">
                <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <main class="main-content">
                <div class="content-wrapper">
                <div class="card mb-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1 class="h4 mb-2">
                        <i class="fas fa-hand-holding-usd me-2 text-success"></i>İade Talebi Formu
                    </h1>
                    <p class="text-muted mb-0">
                        Ücretini kendisi ödeyen üyeler, belgeleriyle birlikte geri ödeme talebini bu form üzerinden gönderebilir.
                    </p>
                </div>
                <div class="text-end">
                    <a href="<?php echo $formBasePath; ?>/index.html" target="_blank" rel="noopener" class="btn btn-outline-success">
                        <i class="fas fa-external-link-alt me-1"></i>Yedek Tam Ekran
                    </a>
                </div>
                </div>
            </div>
        </div>

        <div id="iade-form-wrapper">
            <div class="container" id="mainContent">
                <h2>AİF Gider Formu</h2>
                <div class="subtitle">Gider kalemlerinizi ekleyin, belgeleri yükleyin ve gönderin.</div>
                <form id="expenseForm" onsubmit="handleSubmit(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">İsim</label>
                            <input type="text" id="name" name="name" placeholder="Adınızı giriniz" value="<?php echo htmlspecialchars($uyeAd); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="surname">Soyisim</label>
                            <input type="text" id="surname" name="surname" placeholder="Soyadınızı giriniz" value="<?php echo htmlspecialchars($uyeSoyad); ?>" required>
                        </div>
                    </div>
                    <div class="items-container" id="itemsContainer"></div>
                    <button type="button" class="add-item-button" onclick="addItem()">+ Kalem ekle</button>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="iban">IBAN</label>
                            <input type="text" id="iban" name="iban" placeholder="AT.. (4’lü bloklarla)" required>
                        </div>
                        <div class="form-group">
                            <label for="total">Toplam Tutar (€)</label>
                            <input type="number" id="total" name="total" readonly>
                        </div>
                    </div>
                    <div class="button-container">
                        <button type="submit">Gideri bildir</button>
                    </div>
                </form>
                <div id="spinner"></div>
                <div id="errorMessage" class="error-message"></div>
            </div>
        </div>
    </div>
            </main>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
    const HESAPLAMA_BASE = '<?php echo $formBasePath; ?>';
    const DEFAULT_BYK = '<?php echo htmlspecialchars($uyeBykKodu); ?>';
    window.addEventListener('DOMContentLoaded', function () {
        const wrapper = document.getElementById('mainContent');
        if (wrapper) {
            wrapper.style.display = 'block';
        }
    });

    if (window['pdfjsLib']) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    function arrayBufferToBase64(buffer){
        let binary='';
        const bytes=new Uint8Array(buffer);
        const len=bytes.byteLength;
        for(let i=0;i<len;i++){ binary+=String.fromCharCode(bytes[i]); }
        return btoa(binary);
    }
    function formatCurrency(value){
        const n = typeof value === 'number' ? value : parseFloat(String(value).replace(/[^0-9.]/g,'')) || 0;
        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'EUR' }).format(n);
    }
    function getAndIncrementGiderNo(){
        const key = 'aif_gider_no';
        let val = parseInt(localStorage.getItem(key) || '92', 10);
        val = isNaN(val) ? 92 : val;
        val += 1;
        localStorage.setItem(key, String(val));
        return val;
    }
    let uploadedImages = [];
    let itemCounter = 0;
    const ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjdiYWRhNGRlODEwNjQ1ZjY4NmI0MmMzZDgwOTExODJlIiwiaCI6Im11cm11cjY0In0=';
    const AUTOCOMPLETE_DEBOUNCE_MS = 250;
    const DEBUG = false;
    const geocodeCache = {};
    const routeCache = {};

    const originalConsoleError = console.error;
    const originalConsoleWarn = console.warn;

    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('dpt.js') ||
            message.includes('updateTimeDifferenceInterval') ||
            message.includes('Cannot read properties of null') ||
            message.includes('reading \'value\'')) {
            return;
        }
        originalConsoleError.apply(console, args);
    };

    console.warn = function(...args) {
        const message = args.join(' ');
        if (message.includes('dpt.js') ||
            message.includes('updateTimeDifferenceInterval')) {
            return;
        }
        originalConsoleWarn.apply(console, args);
    };

    window.addEventListener('unhandledrejection', (e) => {
        if (e.reason && e.reason.toString().includes('dpt.js')) {
            e.preventDefault();
            return;
        }
    });

    function debounce(fn, delay) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    }

    function dlog(...args) { if (DEBUG && window && window.console) { console.log('[gider-formu]', ...args); } }

    window.addEventListener('error', (e) => {
        if (e.filename && (e.filename.includes('dpt.js') || e.filename.includes('islamfederasyonu.at'))) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        if (e.filename && !e.filename.includes(window.location.hostname)) {
            e.preventDefault();
            return false;
        }
        if (e.message && e.message.includes('Cannot read properties of null')) {
            e.preventDefault();
            return false;
        }
        const msg = document.getElementById('errorMessage');
        if (msg) { msg.textContent = e.error && e.error.message ? e.error.message : (e.message || 'Bilinmeyen hata'); }
        dlog('GlobalError:', e.error || e.message);
    });

    async function fetchAddressSuggestions(query) {
        if (!query || query.length < 2) return [];
        const url = `https://api.openrouteservice.org/geocode/autocomplete?api_key=${encodeURIComponent(ORS_API_KEY)}&text=${encodeURIComponent(query)}&size=5&boundary.country=AT,DE,CH&lang=tr`;
        const res = await fetch(url);
        const data = await res.json();
        if (!data.features) return [];
        return data.features.map(f => ({ label: f.properties.label || '', lon: f.geometry.coordinates[0], lat: f.geometry.coordinates[1] }));
    }

    function attachAutocomplete(inputEl, suggestionsEl) {
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
        const debounced = debounce(async () => {
            const q = inputEl.value.trim();
            const items = await fetchAddressSuggestions(q).catch(() => []);
            render(items);
        }, AUTOCOMPLETE_DEBOUNCE_MS);
        inputEl.addEventListener('input', debounced);
        inputEl.addEventListener('blur', () => setTimeout(() => suggestionsEl.style.display = 'none', 150));
        inputEl.addEventListener('focus', () => { if (inputEl.value.trim().length >= 2) debounced(); });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const ibanEl = document.getElementById('iban');
        if (ibanEl) {
            const formatIbanAT = () => {
                const raw = (ibanEl.value || '').replace(/\s+/g, '').toUpperCase();
                const digits = raw.replace(/^AT/, '').replace(/[^0-9]/g, '').slice(0, 18);
                const compact = 'AT' + digits;
                const groups = compact.match(/.{1,4}/g);
                ibanEl.value = groups ? groups.join(' ') : compact;
            };
            ibanEl.addEventListener('input', formatIbanAT);
            ibanEl.addEventListener('focus', () => { if (!ibanEl.value.trim()) { ibanEl.value = 'AT '; } else { formatIbanAT(); } });
            ibanEl.addEventListener('blur', () => {
                const valid = isValidAustrianIban(ibanEl.value);
                const err = document.getElementById('errorMessage');
                if (!valid) {
                    err.textContent = 'IBAN geçersiz. Avusturya IBAN\'ı AT ile başlar ve 20 karakterdir.';
                } else {
                    err.textContent = '';
                }
            });
            if (!ibanEl.value.trim()) { ibanEl.value = 'AT '; }
        }
    });

    function isValidAustrianIban(input) {
        const cleaned = (input || '').replace(/\s+/g, '').toUpperCase();
        return /^AT[0-9]{18}$/.test(cleaned);
    }

    function addItem() {
        const container = document.getElementById('itemsContainer');
        const newItem = document.createElement('div');
        newItem.className = 'item';
        const itemId = itemCounter++;

        newItem.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label>Tarih</label>
                    <input type="date" name="position-datum[]" required>
                </div>
                <div class="form-group">
                    <label>Bölge Yönetim Kurulu</label>
                    <select name="region[]" required>
                        <option value="AT" ${DEFAULT_BYK === 'AT' ? 'selected' : ''}>AT</option>
                        <option value="KT" ${DEFAULT_BYK === 'KT' ? 'selected' : ''}>KT</option>
                        <option value="GT" ${DEFAULT_BYK === 'GT' ? 'selected' : ''}>GT</option>
                        <option value="KGT" ${DEFAULT_BYK === 'KGT' ? 'selected' : ''}>KGT</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Birim</label>
                    <select name="birim[]" required>
                        <option value="baskan">Başkan</option>
                        <option value="byk">BYK Üyesi</option>
                        <option value="egitim">Eğitim</option>
                        <option value="fuar">Fuar</option>
                        <option value="gob">Spor/Gezi (GOB)</option>
                        <option value="hacumre">Hac/Umre</option>
                        <option value="idair">İdari İşler</option>
                        <option value="irsad">İrşad</option>
                        <option value="kurumsal">Kurumsal İletişim</option>
                        <option value="muhasebe">Muhasebe</option>
                        <option value="ortaogretim">Orta Öğretim</option>
                        <option value="raggal">Raggal</option>
                        <option value="sosyal">Sosyal Hizmetler</option>
                        <option value="tanitma">Tanıtma</option>
                        <option value="teftis">Teftiş</option>
                        <option value="teskilatlanma">Teşkilatlanma</option>
                        <option value="universiteler">Üniversiteler</option>
                        <option value="baska">Başka</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="gider-turu[]" required onchange="handleGiderTuruChange(this)">
                        <option value="">Lütfen seçin</option>
                        <option value="genel">Genel</option>
                        <option value="ikram">İkram</option>
                        <option value="ulasim">Ulaşım</option>
                        <option value="yakit">Yakıt</option>
                        <option value="malzeme">Malzeme</option>
                        <option value="konaklama">Konaklama</option>
                        <option value="buro">Büro</option>
                        <option value="diger">Diğer</option>
                    </select>
                </div>
            </div>
            <div class="form-group yakit-fields" style="display:none;">
                <label>Rota (Otomatik Mesafe)</label>
                <div class="route-inputs">
                    <div style="position:relative;">
                        <input type="text" class="route-start" placeholder="Amberggsse 10, 6800 Feldkirch" data-item="${itemId}">
                        <div class="suggestions"></div>
                    </div>
                    <div style="position:relative;">
                        <input type="text" class="route-end" placeholder="Sterzingerstraße 6, 6020 Innsbruck" data-item="${itemId}">
                        <div class="suggestions"></div>
                    </div>
                </div>
                <button type="button" class="calculate-km-btn" onclick="calculateDistance(${itemId})">🗺️ Mesafeyi Hesapla</button>
                <div class="km-loading" id="loading-${itemId}">Hesaplanıyor...</div>
                <input type="text" name="route" class="route-full" placeholder="Tam rota açıklaması" style="margin-top:10px;">
            </div>
            <div class="form-group yakit-fields" style="display:none;">
                <label>Toplam Kilometre</label>
                <input type="number" name="kilometer" class="kilometer-field" placeholder="Toplam km" step="0.01" onchange="calculateYakitAmount(this)" data-item="${itemId}">
            </div>
            <div class="form-group">
                <label>Gider Miktarı (€)</label>
                <input type="text" name="gider-miktari[]" required onchange="calculateTotal()" oninput="formatToDecimal(this)">
            </div>
            <div class="form-group">
                <label>Açıklama (Opsiyonel)</label>
                <textarea name="beschreibung[]" rows="2" placeholder="Kısa açıklama"></textarea>
            </div>
            <div class="form-group">
                <label>Belgeler (Görsel/PDF)</label>
                <input type="file" class="item-documents" accept=".jpg,.jpeg,.png,.webp,.heic,image/webp,.pdf,application/pdf" multiple>
            </div>
            <button type="button" class="remove-item-button" onclick="removeItem(this)">Kaldır</button>
        `;

        container.appendChild(newItem);
        const [startWrapper, endWrapper] = newItem.querySelectorAll('.route-inputs > div');
        const startInput = startWrapper.querySelector('.route-start');
        const endInput = endWrapper.querySelector('.route-end');
        const startSug = startWrapper.querySelector('.suggestions');
        const endSug = endWrapper.querySelector('.suggestions');
        attachAutocomplete(startInput, startSug);
        attachAutocomplete(endInput, endSug);
        calculateTotal();
    }

    async function calculateDistance(itemId) {
        const item = document.querySelector(`input[data-item="${itemId}"]`).closest('.item');
        const startInput = item.querySelector('.route-start');
        const endInput = item.querySelector('.route-end');
        const kmField = item.querySelector('.kilometer-field');
        const routeField = item.querySelector('.route-full');
        const loading = document.getElementById(`loading-${itemId}`);

        const start = startInput.value.trim();
        const end = endInput.value.trim();

        if (!start || !end) {
            alert('Lütfen başlangıç ve bitiş adreslerini girin.');
            return;
        }

        loading.style.display = 'block';

        try {
            const startCoords = await geocodeCity(start);
            const endCoords = await geocodeCity(end);
            if (!startCoords || !endCoords) { throw new Error('Adres bulunamadı. Lütfen geçerli bir adres girin.'); }
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${startCoords.lon},${startCoords.lat};${endCoords.lon},${endCoords.lat}?overview=false`;
            const osrmRes = await fetch(osrmUrl);
            const osrmData = await osrmRes.json();
            if (osrmData.code !== 'Ok') { throw new Error('OSRM rota bulamadı'); }
            const oneWayKm = osrmData.routes[0].distance / 1000;
            const roundTripKm = (oneWayKm * 2).toFixed(2);
            kmField.value = roundTripKm;
            routeField.value = `${start} → ${end}`;
            calculateYakitAmount(kmField);
        } catch (error) {
            alert('Rota hesaplanamadı: ' + (error.message || ''));
        } finally {
            loading.style.display = 'none';
        }
    }

    async function geocodeCity(cityName) {
        const url = `https://api.openrouteservice.org/geocode/search?api_key=${encodeURIComponent(ORS_API_KEY)}&text=${encodeURIComponent(cityName)}&size=1&boundary.country=AT,DE,CH&lang=tr`;
        const response = await fetch(url);
        const data = await response.json();
        if (!data.features || data.features.length === 0) return null;
        const coords = data.features[0].geometry.coordinates;
        return { lat: parseFloat(coords[1]), lon: parseFloat(coords[0]) };
    }

    function formatToDecimal(input) {
        input.value = input.value.replace(',', '.');
        input.value = input.value.replace(/[^0-9.]/g, '');
        if ((input.value.match(/\./g) || []).length > 1) {
            input.value = input.value.substring(0, input.value.lastIndexOf('.'));
        }
    }

    function removeItem(button) {
        const item = button.closest('.item');
        item.remove();
        calculateTotal();
    }

    function calculateYakitAmount(input) {
        const kilometer = parseFloat(input.value) || 0;
        const giderMiktari = kilometer * 0.25;
        const giderField = input.closest('.item').querySelector('input[name="gider-miktari[]"]');
        giderField.value = giderMiktari.toFixed(2);
        calculateTotal();
    }

    function calculateTotal() {
        const amounts = document.querySelectorAll('input[name="gider-miktari[]"]');
        let total = 0;
        amounts.forEach(amount => { total += parseFloat(amount.value) || 0; });
        document.getElementById('total').value = total.toFixed(2);
    }

    function handleGiderTuruChange(selectElement) {
        const item = selectElement.closest('.item');
        const yakitFields = item.querySelectorAll('.yakit-fields');
        const giderField = item.querySelector('input[name="gider-miktari[]"]');
        const descArea = item.querySelector('textarea[name="beschreibung[]"]');
        yakitFields.forEach(field => field.style.display = 'none');
        giderField.readOnly = false;
        if (selectElement.value === 'ulasim') {
            yakitFields.forEach(field => field.style.display = 'block');
            giderField.readOnly = true;
            giderField.value = "";
            if (descArea) { descArea.required = false; }
        } else {
            giderField.value = "";
            if (descArea) { descArea.required = false; }
        }
    }

    async function fileToDataURL(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    async function imageToJpegDataURL(dataUrl) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.width; canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                try {
                    const jpeg = canvas.toDataURL('image/jpeg', 0.9);
                    resolve(jpeg);
                } catch (e) {
                    resolve(dataUrl);
                }
            };
            img.onerror = () => resolve(dataUrl);
            img.src = dataUrl;
        });
    }

    async function pdfFileToFirstPageImageDataURL(file, scale = 1.5) {
        try {
            if (!window['pdfjsLib']) return null;
            const buf = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: buf }).promise;
            const page = await pdf.getPage(1);
            const viewport = page.getViewport({ scale });
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = viewport.width; canvas.height = viewport.height;
            await page.render({ canvasContext: ctx, viewport }).promise;
            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            return dataUrl;
        } catch (_) {
            return null;
        }
    }

    async function collectItemImages(itemEl) {
        const input = itemEl ? itemEl.querySelector('.item-documents') : null;
        if (!input || !input.files || input.files.length === 0) { return []; }
        const out = [];
        for (const file of Array.from(input.files)) {
            try {
                if (file.type && file.type.startsWith('image/')) {
                    const dataUrl = await fileToDataURL(file);
                    const jpegDataUrl = await imageToJpegDataURL(dataUrl);
                    out.push(jpegDataUrl);
                } else if (file.type === 'application/pdf') {
                    const img = await pdfFileToFirstPageImageDataURL(file);
                    if (img) out.push(img);
                }
            } catch (_) {}
        }
        return out;
    }

    async function handleSubmit(event) {
        event.preventDefault();
        const spinner = document.getElementById("spinner");
        const errorMessage = document.getElementById("errorMessage");
        errorMessage.textContent = "";
        const ibanVal = document.getElementById('iban').value;
        if (!isValidAustrianIban(ibanVal)) {
            errorMessage.textContent = 'IBAN geçersiz. Avusturya IBAN\'ı AT ile başlar ve 20 karakterdir.';
            return;
        }
        spinner.style.display = "block";
        try {
            const giderNo = await generatePDF();
            setTimeout(() => {
                spinner.style.display = "none";
                window.location.href = `${HESAPLAMA_BASE}/success.html?gider_no=${giderNo}`;
            }, 700);
        } catch (error) {
            errorMessage.textContent = error.message;
            spinner.style.display = "none";
        }
    }

    async function generatePDF() {
        try {
            if (!window.pdfMake) {
                throw new Error('pdfMake kütüphanesi yüklenemedi. Sayfayı yenileyin.');
            }

            const nameEl = document.getElementById('name');
            const surnameEl = document.getElementById('surname');
            const ibanEl = document.getElementById('iban');
            const totalEl = document.getElementById('total');

            if (!nameEl || !surnameEl || !ibanEl || !totalEl) {
                throw new Error('Form alanları bulunamadı');
            }

            const name = nameEl.value || '';
            const surname = surnameEl.value || '';
            const ibanRaw = ibanEl.value || '';
            const iban = ibanRaw.replace(/\s+/g, '');
            const total = totalEl.value || '0';

            function formatIBAN(iban) {
                if (!iban) return '';
                return iban.replace(/(.{4})/g, '$1 ').trim();
            }

            function formatDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                return date.toLocaleDateString('tr-TR');
            }

            const positions = document.querySelectorAll('.item');
            const giderNo = getAndIncrementGiderNo();
            const itemsForBackend = [];
            const tableBody = [];
            const attachmentImages = [];

            for (const position of Array.from(positions)) {
                const images = await collectItemImages(position);
                if (images.length > 0) {
                    images.forEach((imgData, idx) => {
                        attachmentImages.push({
                            url: imgData,
                            name: `Fatura ${Array.from(positions).indexOf(position) + 1}-${idx + 1}`,
                            itemIndex: Array.from(positions).indexOf(position)
                        });
                    });
                }
            }

            for (const position of Array.from(positions)) {
                const datum = position.querySelector('input[name="position-datum[]"]').value || "-";
                const regionSel = position.querySelector('select[name="region[]"]');
                const region = regionSel ? regionSel.value : "-";
                const birimSel = position.querySelector('select[name="birim[]"]');
                const birim = birimSel ? birimSel.value : "-";
                const birimLabel = birimSel && birimSel.selectedIndex >= 0 ? birimSel.options[birimSel.selectedIndex].text : birim;
                const turSel = position.querySelector('select[name="gider-turu[]"]');
                const giderTuru = turSel ? turSel.value : "-";
                const giderTuruLabel = turSel && turSel.selectedIndex >= 0 ? turSel.options[turSel.selectedIndex].text : giderTuru;
                const giderMiktari = position.querySelector('input[name="gider-miktari[]"]').value || "0";
                const beschreibung = position.querySelector('textarea[name="beschreibung[]"]').value || "-";

                if (giderTuru === 'ulasim') {
                    const route = position.querySelector('input[name="route"]').value || "-";
                    const kilometer = position.querySelector('input[name="kilometer"]').value || "0";
                    itemsForBackend.push({ tarih: datum, region, birim, birim_label: birimLabel, gider_turu: giderTuru, gider_turu_label: giderTuruLabel, tutar: parseFloat(giderMiktari) || 0, aciklama: beschrijving, rota: route, km: parseFloat(kilometer) || 0 });
                } else {
                    itemsForBackend.push({ tarih: datum, region, birim, birim_label: birimLabel, gider_turu: giderTuru, gider_turu_label: giderTuruLabel, tutar: parseFloat(giderMiktari) || 0, aciklama: beschrijving });
                }

                tableBody.push([
                    formatDate(datum),
                    region,
                    birimLabel,
                    giderTuruLabel,
                    `${giderMiktari} €`,
                    beschrijving
                ]);
            }

            const now = new Date();
            const dateStr = now.toLocaleDateString('tr-TR');
            const timeStr = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });

            const docDefinition = {
                pageSize: 'A4',
                pageMargins: [40, 60, 40, 60],
                content: [
                    {
                        columns: [
                            {
                                width: '*',
                                stack: [
                                    { text: 'AİF GİDER FORMU', style: 'header', color: '#009872' },
                                    { text: 'Avusturya İslam Federasyonu', style: 'subheader' }
                                ]
                            },
                            {
                                width: 'auto',
                                stack: [
                                    { text: `${dateStr} - ${timeStr}`, style: 'dateText', alignment: 'right' },
                                    { text: 'Oluşturulma Tarihi', style: 'dateLabel', alignment: 'right' }
                                ]
                            }
                        ],
                        margin: [0, 0, 0, 20]
                    },
                    {
                        table: {
                            widths: ['*'],
                            body: [
                                [{ text: 'BAŞVURAN BİLGİLERİ', style: 'cardHeader', fillColor: '#009872', color: 'white' }],
                                [{
                                    stack: [
                                        { text: [{ text: 'İsim Soyisim: ', bold: true }, `${name} ${surname}`], margin: [0, 5, 0, 5] },
                                        { text: [{ text: 'IBAN: ', bold: true }, formatIBAN(iban)], margin: [0, 0, 0, 5] },
                                        { text: [{ text: 'Toplam Tutar: ', bold: true }, { text: `${total} €`, color: '#28a745', bold: true }], margin: [0, 0, 0, 5] }
                                    ],
                                    fillColor: '#f8f9fa',
                                    margin: 10
                                }]
                            ]
                        },
                        layout: 'noBorders',
                        margin: [0, 0, 0, 20]
                    },
                    { text: 'GİDER DETAYLARI', style: 'sectionHeader', margin: [0, 0, 0, 10] },
                    {
                        table: {
                            headerRows: 1,
                            widths: ['auto', 'auto', '*', 'auto', 'auto', '*'],
                            body: [
                                [
                                    { text: 'Tarih', style: 'tableHeader' },
                                    { text: 'BYK', style: 'tableHeader' },
                                    { text: 'Birim', style: 'tableHeader' },
                                    { text: 'Tür', style: 'tableHeader' },
                                    { text: 'Tutar', style: 'tableHeader' },
                                    { text: 'Açıklama', style: 'tableHeader' }
                                ],
                                ...tableBody
                            ]
                        },
                        layout: {
                            fillColor: function (rowIndex) {
                                return (rowIndex % 2 === 0) ? '#f8f9fa' : null;
                            },
                            hLineWidth: function (i, node) {
                                return (i === 0 || i === 1 || i === node.table.body.length) ? 1 : 0.5;
                            },
                            vLineWidth: function () { return 0; },
                            hLineColor: function (i) {
                                return i === 1 ? '#009872' : '#dee2e6';
                            }
                        },
                        margin: [0, 0, 0, 20]
                    },
                    {
                        table: {
                            widths: ['*'],
                            body: [
                                [{
                                    stack: [
                                        {
                                            columns: [
                                                { text: [{ text: 'ÖZET - ', bold: true }, `Toplam Kalem Sayısı: ${itemsForBackend.length}`], width: '*' },
                                                { text: `GENEL TOPLAM: ${total} €`, style: 'totalAmount', alignment: 'right', width: 'auto' }
                                            ]
                                        }
                                    ],
                                    fillColor: '#e8f5f1',
                                    margin: 10
                                }]
                            ]
                        },
                        layout: 'noBorders',
                        margin: [0, 0, 0, 20]
                    },
                    ...(attachmentImages.length > 0 ? [
                        { text: 'FATURA GÖRSELLERİ', style: 'sectionHeader', pageBreak: 'before', margin: [0, 0, 0, 15] },
                        ...attachmentImages.map((img, idx) => ({
                            stack: [
                                { text: img.name, style: 'imageCaption', margin: [0, 0, 0, 5] },
                                {
                                    image: img.url,
                                    width: 500,
                                    alignment: 'center',
                                    margin: [0, 0, 0, idx < attachmentImages.length - 1 ? 20 : 0]
                                }
                            ]
                        }))
                    ] : [])
                ],
                footer: function(currentPage, pageCount) {
                    return {
                        columns: [
                            { text: 'Bu belge dijital ortamda oluşturulmuştur.', style: 'footer', alignment: 'left' },
                            { text: 'Avusturya İslam Federasyonu - Gider Yönetim Sistemi', style: 'footer', alignment: 'right' }
                        ],
                        margin: [40, 0]
                    };
                },
                styles: {
                    header: { fontSize: 22, bold: true, margin: [0, 0, 0, 5] },
                    subheader: { fontSize: 12, color: '#6c757d' },
                    dateText: { fontSize: 10, bold: true },
                    dateLabel: { fontSize: 8, color: '#6c757d' },
                    cardHeader: { fontSize: 12, bold: true, margin: [10, 10, 10, 10] },
                    sectionHeader: { fontSize: 16, bold: true, color: '#212529' },
                    tableHeader: { bold: true, fontSize: 11, color: 'white', fillColor: '#009872', margin: [5, 8, 5, 8] },
                    totalAmount: { fontSize: 12, bold: true, color: '#28a745' },
                    imageCaption: { fontSize: 11, bold: true, color: '#495057', alignment: 'center' },
                    footer: { fontSize: 8, color: '#6c757d' }
                },
                defaultStyle: { fontSize: 10, font: 'Roboto' }
            };

            const pdfBlob = await new Promise((resolve, reject) => {
                try {
                    const pdfDocGenerator = pdfMake.createPdf(docDefinition);
                    pdfDocGenerator.getBlob((blob) => { resolve(blob); });
                } catch (error) {
                    reject(error);
                }
            });

            const safeName = (name + '_' + surname).replace(/[^A-Za-z0-9_\-]+/g,'_');
            const outName = `gider_${Date.now()}_${safeName}.pdf`;
            const pdfFile = new File([pdfBlob], outName, { type: 'application/pdf' });
            const formData = new FormData();
            formData.append('name', name);
            formData.append('surname', surname);
            formData.append('iban', iban);
            formData.append('total', total);
            const payload = { gider_no: giderNo, items: itemsForBackend };
            formData.append('items_json', JSON.stringify(payload));
            formData.append('pdf', pdfFile);

            const phpUrl = `${HESAPLAMA_BASE}/receive_pdf.php`;
            dlog('Sending PDF to:', phpUrl);
            const response = await fetch(phpUrl, { method: 'POST', body: formData });
            const ct = (response.headers.get('content-type') || '').toLowerCase();

            if (!response.ok) {
                const errText = await response.text().catch(() => '');
                throw new Error(errText || `İstek başarısız: ${response.status}`);
            }

            if (!ct.includes('application/json')) {
                const raw = await response.text().catch(() => '');
                throw new Error(raw ? `Sunucu JSON yerine şunu döndü: ${raw.substring(0,200)}...` : 'Sunucu beklenen JSON yanıtı döndürmedi.');
            }

            const data = await response.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'PDF gönderilemedi');
            }

            return giderNo;
        } catch (error) {
            console.error('generatePDF hatası:', error);
            throw error;
        }
    }
</script>

<?php
include __DIR__ . '/../includes/footer.php';
?>

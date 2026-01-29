<script>
        // pdf.js worker ayarÄ±
    if (window['pdfjsLib']) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    // TÃ¼rkÃ§e karakterler iÃ§in font yÃ¼kleyici (Noto Sans Ã¶nerilir)
    async function useTurkishFont(doc){
            try {
        // Direkt fallback helvetica kullan (font yükleme hatası önleme)
        doc.setFont('helvetica', '');
    console.log('Font yükleme atlandı, helvetica kullanılıyor');
            } catch (e) {
        // Son Ã§are
        doc.setFont('helvetica', '');
    console.log('Font hatasÄ±:', e);
            }
        }
    function arrayBufferToBase64(buffer){
        let binary='';
    const bytes=new Uint8Array(buffer);
    const len=bytes.byteLength;
    for(let i=0;i<len;i++){binary += String.fromCharCode(bytes[i]); }
    return btoa(binary);
        }
    function formatCurrency(value){
            const n = typeof value === 'number' ? value : parseFloat(String(value).replace(/[^0-9.]/g,'')) || 0;
    return new Intl.NumberFormat('tr-TR', {style: 'currency', currency: 'EUR' }).format(n);
        }
    function getAndIncrementGiderNo(){
            const key = 'aif_gider_no';
    let val = parseInt(localStorage.getItem(key) || '92', 10); // Ã¶rnek baÅŸlangÄ±Ã§
    val = isNaN(val) ? 92 : val;
    val += 1;
    localStorage.setItem(key, String(val));
    return val;
        }
    let uploadedImages = [];
    let itemCounter = 0;
    if (typeof ORS_API_KEY === 'undefined') {
            var ORS_API_KEY = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjdiYWRhNGRlODEwNjQ1ZjY4NmI0MmMzZDgwOTExODJlIiwiaCI6Im11cm11cjY0In0=';
        }
    const AUTOCOMPLETE_DEBOUNCE_MS = 250;
    const DEBUG = false; // Konsol çıktısını azalt
    const geocodeCache = { };
    const routeCache = { };

    // Global hata filtreleme - daha kapsamlÄ±
    const originalConsoleError = console.error;
    const originalConsoleWarn = console.warn;

    console.error = function(...args) {
            const message = args.join(' ');
    if (message.includes('dpt.js') ||
    message.includes('updateTimeDifferenceInterval') ||
    message.includes('Cannot read properties of null')) {
                return; // Bu hataları gösterme
            }
    originalConsoleError.apply(console, args);
        };

    console.warn = function(...args) {
            const message = args.join(' ');
    if (message.includes('dpt.js') ||
    message.includes('updateTimeDifferenceInterval')) {
                return; // Bu uyarıları gösterme
            }
    originalConsoleWarn.apply(console, args);
        };

        // Uncaught Exception filtreleme
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

    // Basit log
    function dlog(...args) { if (DEBUG && window && window.console) {console.log('[gider-formu]', ...args); } }

        // Hata yakalama ve temizleme - daha agresif filtreleme
        window.addEventListener('error', (e) => {
            // dpt.js ve diÄŸer harici script hatalarÄ±nÄ± filtrele
            if (e.filename && (e.filename.includes('dpt.js') || e.filename.includes('islamfederasyonu.at'))) {
        e.preventDefault();
    e.stopPropagation();
    return false;
            }
    // Sadece bizim script hatalarÄ±nÄ± gÃ¶ster
    if (e.filename && !e.filename.includes(window.location.hostname)) {
        e.preventDefault();
    return false;
            }
    // Null property hatalarÄ±nÄ± filtrele
    if (e.message && e.message.includes('Cannot read properties of null')) {
        e.preventDefault();
    return false;
            }
    const msg = document.getElementById('errorMessage');
    if (msg) {msg.textContent = e.error && e.error.message ? e.error.message : (e.message || 'Bilinmeyen hata'); }
    dlog('GlobalError:', e.error || e.message);
        });

    async function fetchAddressSuggestions(query) {
            if (!query || query.length < 2) return [];
    const url = `https://api.openrouteservice.org/geocode/autocomplete?api_key=${encodeURIComponent(ORS_API_KEY)}&text=${encodeURIComponent(query)}&size=5&boundary.country=AT,DE,CH&lang=tr`;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.features) return [];
            return data.features.map(f => ({label: f.properties.label || '', lon: f.geometry.coordinates[0], lat: f.geometry.coordinates[1] }));
        }

    function attachAutocomplete(inputEl, suggestionsEl) {
            const render = (items) => {
        suggestionsEl.innerHTML = '';
    if (!items.length) {suggestionsEl.style.display = 'none'; return; }
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

        // IBAN input: 4'lÃ¼ bloklar halinde gÃ¶rsel format
        // Sayfa tamamen yüklendiğinde çalıştır
        window.addEventListener('load', () => {
        // İlk item için default olarak faturasız ayarla
        setTimeout(() => {
            const firstItem = document.querySelector('.item');
            if (firstItem) {
                const odemeSekliSelect = firstItem.querySelector('select[name="odeme-sekli[]"]');
                if (odemeSekliSelect) {
                    odemeSekliSelect.value = 'faturasız';
                    handleOdemeSekliChange(odemeSekliSelect);
                }
            }
        }, 100); // Kısa bir gecikme ile çalıştır
        });

    const ibanEl = document.getElementById('iban');
    if (ibanEl) {
                // BaÅŸta zorunlu 'AT' ve sadece rakam kabul et; toplam 20 karakter (AT + 18 rakam)
                const formatIbanAT = () => {
                    const raw = (ibanEl.value || '').replace(/\s+/g, '').toUpperCase();
    // SayÄ±larÄ± Ã§ek, harfleri (AT dÄ±ÅŸÄ±nda) yok say
    const digits = raw.replace(/^AT/, '').replace(/[^0-9]/g, '').slice(0, 18);
    const compact = 'AT' + digits;
    const groups = compact.match(/.{1, 4}/g);
    ibanEl.value = groups ? groups.join(' ') : compact;
                };
    ibanEl.addEventListener('input', formatIbanAT);
                ibanEl.addEventListener('focus', () => { if (!ibanEl.value.trim()) {ibanEl.value = 'AT '; } else {formatIbanAT(); } });
                ibanEl.addEventListener('blur', () => {
                    const valid = isValidAustrianIban(ibanEl.value);
    const err = document.getElementById('errorMessage');
    if (!valid) {
        err.textContent = 'IBAN geÃ§ersiz. Avusturya IBAN\'Ä± AT ile baÅŸlar ve 20 karakterdir.';
                    } else {
        err.textContent = '';
                    }
                });
    // İlk yüklemede de AT ile başlat
    if (!ibanEl.value.trim()) {ibanEl.value = 'AT '; }
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
                <option value="AT">AT</option>
                <option value="KT">KT</option>
                <option value="GT">GT</option>
                <option value="KGT">KGT</option>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Birim</label>
            <select name="birim[]" required>
                <option value="baskan">BaÅŸkan</option>
                <option value="byk">BYK Üyesi</option>
                <option value="egitim">Eğitim</option>
                <option value="fuar">Fuar</option>
                <option value="gob">Spor/Gezi (GOB)</option>
                <option value="hacumre">Hac/Umre</option>
                <option value="idair">İdari İşler</option>
                <option value="irsad">İrşad</option>
                <option value="kurumsal">Kurumsal Ä°letiÅŸim</option>
                <option value="muhasebe">Muhasebe</option>
                <option value="ortaogretim">Orta Ã–ÄŸretim</option>
                <option value="raggal">Raggal</option>
                <option value="sosyal">Sosyal Hizmetler</option>
                <option value="tanitma">TanÄ±tma</option>
                <option value="teftis">TeftiÅŸ</option>
                <option value="teskilatlanma">TeÅŸkilatlanma</option>
                <option value="universiteler">Ãœniversiteler</option>
                <option value="baska">BaÅŸka</option>
            </select>
        </div>
        <div class="form-group">
            <label>Kategori</label>
            <select name="gider-turu[]" required onchange="handleGiderTuruChange(this)">
                <option value="">LÃ¼tfen seÃ§in</option>
                <option value="genel">Genel</option>
                <option value="ikram">Ä°kram</option>
                <option value="ulasim">UlaÅŸÄ±m</option>
                <option value="mazeme">Mazeme</option>
                <option value="konaklama">Konaklama</option>
                <option value="buro">BÃ¼ro</option>
                <option value="diger">DiÄŸer</option>
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
                <input type="text" class="route-end" placeholder="SterzingerstraÃŸe 6, 6020 Innsbruck" data-item="${itemId}">
                    <div class="suggestions"></div>
            </div>
        </div>
        <button type="button" class="calculate-km-btn" onclick="calculateDistance(${itemId})">ðŸ—ºï¸ Mesafeyi Hesapla</button>
        <div class="km-loading" id="loading-${itemId}">HesaplanÄ±yor...</div>
        <input type="text" name="route" class="route-full" placeholder="Tam rota aÃ§Ä±klamasÄ±" style="margin-top:10px;">
    </div>
    <div class="form-group yakit-fields" style="display:none;">
        <label>Toplam Kilometre</label>
        <input type="number" name="kilometer" class="kilometer-field" placeholder="Toplam km" step="0.01" onchange="calculateYakitAmount(this)" data-item="${itemId}">
    </div>
    <div class="form-group">
        <label>Gider MiktarÄ± (â‚¬)</label>
        <input type="text" name="gider-miktari[]" required onchange="calculateTotal()" oninput="formatToDecimal(this)">
    </div>
    <div class="form-group desc-fields" style="display:none;">
        <label>AÃ§Ä±klama</label>
        <textarea name="beschreibung[]" rows="2" placeholder="Kısa açıklama"></textarea>
    </div>
    <div class="form-group">
        <label>Belgeler (GÃ¶rsel/PDF)</label>
        <input type="file" class="item-documents" accept=".jpg,.jpeg,.png,.webp,.heic,image/webp,.pdf,application/pdf" multiple>
    </div>
    <button type="button" class="remove-item-button" onclick="removeItem(this)">KaldÄ±r</button>
    `;

    container.appendChild(newItem);
            // Autocomplete baÄŸlama
            const [startWrapper, endWrapper] = newItem.querySelectorAll('.route-inputs > div');
    const startInput = startWrapper.querySelector('.route-start');
    const endInput = endWrapper.querySelector('.route-end');
    const startSug = startWrapper.querySelector('.suggestions');
    const endSug = endWrapper.querySelector('.suggestions');
    attachAutocomplete(startInput, startSug);
    attachAutocomplete(endInput, endSug);
    calculateTotal();

    // Yeni item için faturasız seçili yap ve change event'ini tetikle
    const odemeSekliSelect = newItem.querySelector('select[name="odeme-sekli[]"]');
    if (odemeSekliSelect) {
        odemeSekliSelect.value = 'faturasız';
    handleOdemeSekliChange(odemeSekliSelect);
            }
        }

    // ðŸš€ OpenRouteService ile otomatik mesafe hesaplama
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
        alert('LÃ¼tfen baÅŸlangÄ±Ã§ ve bitiÅŸ adreslerini girin.');
    return;
            }

    loading.style.display = 'block';

    try {
                // Geocoding (ORS) -> Rota (OSRM) HÄ±zlÄ± Yol
                const startCoords = await geocodeCity(start);
    const endCoords = await geocodeCity(end);
    if (!startCoords || !endCoords) { throw new Error('Adres bulunamadÄ±. LÃ¼tfen geÃ§erli bir adres girin.'); }
    const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${startCoords.lon},${startCoords.lat};${endCoords.lon},${endCoords.lat}?overview=false`;
    const osrmRes = await fetch(osrmUrl);
    const osrmData = await osrmRes.json();
    if (osrmData.code !== 'Ok') { throw new Error('OSRM rota bulamadÄ±'); }
    const oneWayKm = osrmData.routes[0].distance / 1000;
    const roundTripKm = (oneWayKm * 2).toFixed(2);
    kmField.value = roundTripKm;
    routeField.value = `${start} â†’ ${end}`;
    calculateYakitAmount(kmField);
            } catch (error) {
        alert('Rota hesaplanamadÄ±: ' + (error.message || ''));
            } finally {
        loading.style.display = 'none';
            }
        }

    // Geocoding: Adresi koordinata Ã§evir (OpenRouteService)
    async function geocodeCity(cityName) {
            const url = `https://api.openrouteservice.org/geocode/search?api_key=${encodeURIComponent(ORS_API_KEY)}&text=${encodeURIComponent(cityName)}&size=1&boundary.country=AT,DE,CH&lang=tr`;
    const response = await fetch(url);
    const data = await response.json();
    if (!data.features || data.features.length === 0) return null;
    const coords = data.features[0].geometry.coordinates; // [lon, lat]
    return {lat: parseFloat(coords[1]), lon: parseFloat(coords[0]) };
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
            amounts.forEach(amount => {total += parseFloat(amount.value) || 0; });
    document.getElementById('total').value = total.toFixed(2);
        }

    function handleGiderTuruChange(selectElement) {
            const item = selectElement.closest('.item');
    const yakitFields = item.querySelectorAll('.yakit-fields');
    const giderField = item.querySelector('input[name="gider-miktari[]"]');
    const descWrapper = item.querySelector('.desc-fields');
    const descArea = item.querySelector('textarea[name="beschreibung[]"]');
            yakitFields.forEach(field => field.style.display = 'none');
    giderField.readOnly = false;
    if (selectElement.value === 'ulasim') {
        yakitFields.forEach(field => field.style.display = 'block');
    giderField.readOnly = true;
    giderField.value = "";
    if (descWrapper) {descWrapper.style.display = 'none'; }
    if (descArea) {descArea.required = false; descArea.value = ''; }
            } else if (selectElement.value === 'diger') {
                if (descWrapper) {descWrapper.style.display = 'block'; }
    if (descArea) {descArea.required = true; }
            } else {
        giderField.value = "";
    if (descWrapper) {descWrapper.style.display = 'none'; }
    if (descArea) {descArea.required = false; descArea.value = ''; }
            }
        }

    // Ödeme Şekli değişimi yönetimi
    function handleOdemeSekliChange(selectElement) {
            const item = selectElement.closest('.item');
    const kdvFields = item.querySelectorAll('.kdv-fields');
    const normalAmountField = item.querySelector('.normal-amount-field');
    const giderMiktariField = item.querySelector('input[name="gider-miktari[]"]');


    if (selectElement.value === 'faturalı') {
        // KDV alanlarını göster, normal gider miktarını gizle
        kdvFields.forEach(field => field.style.display = 'block');
    if (normalAmountField) {
        normalAmountField.style.display = 'none';
                }
    giderMiktariField.required = false;
            } else if (selectElement.value === 'faturasız') {
        // KDV alanlarını gizle, normal gider miktarını göster
        kdvFields.forEach(field => field.style.display = 'none');
    if (normalAmountField) {
        normalAmountField.style.display = 'block';
                }
    giderMiktariField.required = true;
            } else {
        // Hiçbir şey seçilmemişse her ikisini de gizle
        kdvFields.forEach(field => field.style.display = 'none');
    if (normalAmountField) {
        normalAmountField.style.display = 'none';
                }
    giderMiktariField.required = false;
            }
        }


    // KDV hesaplamaları
    function calculateBruttoFromNetto(inputElement) {
            const item = inputElement.closest('.item');
    const nettoField = item.querySelector('input[name="gider-netto[]"]');
    const kdvField = item.querySelector('input[name="gider-kdv[]"]');
    const bruttoField = item.querySelector('input[name="gider-brutto[]"]');

    const netto = parseFloat(nettoField.value.replace(/[^0-9.,]/g, '').replace(',', '.')) || 0;
    const kdv = parseFloat(kdvField.value.replace(/[^0-9.,]/g, '').replace(',', '.')) || 0;

    const brutto = netto + kdv;

            if (brutto > 0) {
        bruttoField.value = brutto.toFixed(2);
            }

    calculateTotal();
        }

    // Global resim yükleme kaldırıldı; her kalem için ayrı dosya alanı zaten mevcut

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
        resolve(dataUrl); // fallback: orjinal
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
    const pdf = await pdfjsLib.getDocument({data: buf }).promise;
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({scale});
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = viewport.width; canvas.height = viewport.height;
    await page.render({canvasContext: ctx, viewport }).promise;
    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
    return dataUrl;
            } catch (_) {
                return null;
            }
        }

    async function collectItemImages(itemEl) {
            const input = itemEl ? itemEl.querySelector('.item-documents') : null;
    if (!input) {dlog('Dosya input yok'); return []; }
    if (!input.files) {dlog('input.files yok (null)'); return []; }
    if (input.files.length === 0) {dlog('Dosya seÃ§ilmemiÅŸ'); return []; }
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
                } catch (_) { }
            }
    return out;
        }

    async function handleSubmit(event) {
        event.preventDefault();
    const spinner = document.getElementById("spinner");
    const errorMessage = document.getElementById("errorMessage");
    errorMessage.textContent = "";
    // IBAN doÄŸrulamasÄ±: Avusturya IBAN (AT + 18 rakam = 20 karakter)
    const ibanVal = document.getElementById('iban').value;
    if (!isValidAustrianIban(ibanVal)) {
        errorMessage.textContent = 'IBAN geÃ§ersiz. Avusturya IBAN\'Ä± AT ile baÅŸlar ve 20 karakterdir.';
    return;
            }
    spinner.style.display = "block";
    try {
                const giderNo = await generatePDF();
                // Başarılı! Teşekkür sayfasına yönlendir
                setTimeout(() => {
        spinner.style.display = "none";
    window.location.href = `success.html?gider_no=${giderNo}`;
                }, 700);
            } catch (error) {
        errorMessage.textContent = error.message;
    spinner.style.display = "none";
            }
        }

    async function addImagesToPDF(doc, images, yPos, maxWidth, maxHeight, margin) {
            for (const imgData of images) {
                const img = new Image();
    img.src = imgData;
                await new Promise(resolve => img.onload = resolve);
    let imgWidth = img.width; let imgHeight = img.height;
                // AÅŸÄ±rÄ± bÃ¼yÃ¼k gÃ¶rseli kÃ¼Ã§Ã¼lt
                if (imgWidth > maxWidth) { const s = maxWidth / imgWidth; imgWidth *= s; imgHeight *= s; }
                if (imgHeight > 500) { const s2 = 500 / imgHeight; imgWidth *= s2; imgHeight *= s2; }
                if (yPos + imgHeight > maxHeight) {doc.addPage(); yPos = margin; }
    doc.addImage(imgData, 'JPEG', margin, yPos, imgWidth, imgHeight);
    yPos += imgHeight + 10;
            }
    return yPos;
        }

    // Ä°ki gÃ¶rsel tek sayfaya bÃ¼yÃ¼k sÄ±ÄŸacak ÅŸekilde yerleÅŸtir
    async function addImagesTwoPerPage(doc, images, yPos, pageWidth, pageHeight, margin, labelPrefix = '', startIndex = 1) {
            const gap = 10;
    const contentW = pageWidth - 2 * margin;
    const contentH = pageHeight - 2 * margin;
    const maxHPerImage = Math.floor((contentH - gap) / 2); // iki gÃ¶rsel/ sayfa (Ã¼st + alt)
    let runningIndex = startIndex;
    let indexOnPage = 0;
    for (const imgData of images) {
                if (indexOnPage === 2 || yPos + 10 > pageHeight - margin) {doc.addPage(); yPos = margin; indexOnPage = 0; }
    const img = new Image();
    img.src = imgData;
                await new Promise(resolve => img.onload = resolve);
    let w = img.width; let h = img.height;
    let s = Math.min(contentW / w, maxHPerImage / h, 1);
    w = Math.max(1, Math.floor(w * s));
    h = Math.max(1, Math.floor(h * s));
    const x = margin + Math.floor((contentW - w) / 2); // ortala
                if (yPos + h > pageHeight - margin) {doc.addPage(); yPos = margin; indexOnPage = 0; }
    doc.addImage(imgData, 'JPEG', x, yPos, w, h);
    if (labelPrefix) {
        doc.setFontSize(10);
    doc.setTextColor(60);
    const label = `${labelPrefix}.${runningIndex}`;
    doc.text(label, x + w, yPos + h + 12, {align: 'right' });
    doc.setTextColor(20);
                }
    yPos += h + gap;
    runningIndex++;
    indexOnPage++;
            }
    return {yPos, nextIndex: runningIndex };
        }

    async function generatePDF() {
            // index.html'deki generatePDF ile aynı (pdfmake kullanıyor)
            return await window.parent.generatePDF ? window.parent.generatePDF() : generatePDFLocal();
        }

    async function generatePDFLocal() {
            // Bu fonksiyon index.html'deki ile aynı - pdfmake kullanıyor
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
    const odemeSekliSel = position.querySelector('select[name="odeme-sekli[]"]');
    const odemeSekli = odemeSekliSel ? odemeSekliSel.value : "-";
    const giderMiktari = position.querySelector('input[name="gider-miktari[]"]').value || "0";
    const giderNetto = position.querySelector('input[name="gider-netto[]"]').value || "0";
    const giderKdv = position.querySelector('input[name="gider-kdv[]"]').value || "0";
    const giderBrutto = position.querySelector('input[name="gider-brutto[]"]').value || "0";
    const beschreibung = position.querySelector('textarea[name="beschreibung[]"]').value || "-";

    if (giderTuru === 'ulasim') {
                        const route = position.querySelector('input[name="route"]').value || "-";
    const kilometer = position.querySelector('input[name="kilometer"]').value || "0";
    itemsForBackend.push({
        tarih: datum,
    region,
    birim,
    birim_label: birimLabel,
    odeme_sekli: odemeSekli,
    gider_turu: giderTuru,
    gider_turu_label: giderTuruLabel,
    tutar: parseFloat(giderMiktari) || 0,
    tutar_netto: parseFloat(giderNetto) || 0,
    tutar_kdv: parseFloat(giderKdv) || 0,
    tutar_brutto: parseFloat(giderBrutto) || 0,
    aciklama: beschreibung,
    rota: route,
    km: parseFloat(kilometer) || 0
                    });
                } else {
        itemsForBackend.push({
            tarih: datum,
            region,
            birim,
            birim_label: birimLabel,
            odeme_sekli: odemeSekli,
            gider_turu: giderTuru,
            gider_turu_label: giderTuruLabel,
            tutar: parseFloat(giderMiktari) || 0,
            tutar_netto: parseFloat(giderNetto) || 0,
            tutar_kdv: parseFloat(giderKdv) || 0,
            tutar_brutto: parseFloat(giderBrutto) || 0,
            aciklama: beschreibung
        });
                }

    tableBody.push([
    formatDate(datum),
    region,
    birimLabel,
    giderTuruLabel,
    `${giderMiktari} €`,
    beschreibung
    ]);
                }

    const now = new Date();
    const dateStr = now.toLocaleDateString('tr-TR');
    const timeStr = now.toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit' });

    const docDefinition = {
        pageSize: 'A4',
    pageMargins: [40, 60, 40, 60],
    content: [
    {
        columns: [
    {
        width: '*',
    stack: [
    {text: 'AİF GİDER FORMU', style: 'header', color: '#009872' },
    {text: 'Avusturya İslam Federasyonu', style: 'subheader' }
    ]
                                },
    {
        width: 'auto',
    stack: [
    {text: `${dateStr} - ${timeStr}`, style: 'dateText', alignment: 'right' },
    {text: 'Oluşturulma Tarihi', style: 'dateLabel', alignment: 'right' }
    ]
                                }
    ],
    margin: [0, 0, 0, 20]
                        },
    {
        table: {
        widths: ['*'],
    body: [
    [{text: 'BAŞVURAN BİLGİLERİ', style: 'cardHeader', fillColor: '#009872', color: 'white' }],
    [{
        stack: [
    {text: [{text: 'İsim Soyisim: ', bold: true }, `${name} ${surname}`], margin: [0, 5, 0, 5] },
    {text: [{text: 'IBAN: ', bold: true }, formatIBAN(iban)], margin: [0, 0, 0, 5] },
    {text: [{text: 'Toplam Tutar: ', bold: true }, {text: `${total} €`, color: '#28a745', bold: true }], margin: [0, 0, 0, 5] }
    ],
    fillColor: '#f8f9fa',
    margin: 10
                                    }]
    ]
                            },
    layout: 'noBorders',
    margin: [0, 0, 0, 20]
                        },
    {text: 'GİDER DETAYLARI', style: 'sectionHeader', margin: [0, 0, 0, 10] },
    {
        table: {
        headerRows: 1,
    widths: ['auto', 'auto', '*', 'auto', 'auto', '*'],
    body: [
    [
    {text: 'Tarih', style: 'tableHeader' },
    {text: 'BYK', style: 'tableHeader' },
    {text: 'Birim', style: 'tableHeader' },
    {text: 'Tür', style: 'tableHeader' },
    {text: 'Tutar', style: 'tableHeader' },
    {text: 'Açıklama', style: 'tableHeader' }
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
    vLineWidth: function () {
                                    return 0;
                                },
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
    {text: [{text: 'ÖZET - ', bold: true }, `Toplam Kalem Sayısı: ${itemsForBackend.length}`], width: '*' },
    {text: `GENEL TOPLAM: ${total} €`, style: 'totalAmount', alignment: 'right', width: 'auto' }
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
                        }
    ],
    footer: function(currentPage, pageCount) {
                        return {
        columns: [
    {text: 'Bu belge dijital ortamda oluşturulmuştur.', style: 'footer', alignment: 'left' },
    {text: 'Avusturya İslam Federasyonu - Gider Yönetim Sistemi', style: 'footer', alignment: 'right' }
    ],
    margin: [40, 0]
                        };
                    },
    styles: {
        header: {fontSize: 22, bold: true, margin: [0, 0, 0, 5] },
    subheader: {fontSize: 12, color: '#6c757d' },
    dateText: {fontSize: 10, bold: true },
    dateLabel: {fontSize: 8, color: '#6c757d' },
    cardHeader: {fontSize: 12, bold: true, margin: [10, 10, 10, 10] },
    sectionHeader: {fontSize: 16, bold: true, color: '#212529' },
    tableHeader: {bold: true, fontSize: 11, color: 'white', fillColor: '#009872', margin: [5, 8, 5, 8] },
    totalAmount: {fontSize: 12, bold: true, color: '#28a745' },
    footer: {fontSize: 8, color: '#6c757d' }
                    },
    defaultStyle: {
        fontSize: 10,
    font: 'Roboto'
                    }
                };
                
                const pdfBlob = await new Promise((resolve, reject) => {
                    try {
                        const pdfDocGenerator = pdfMake.createPdf(docDefinition);
                        pdfDocGenerator.getBlob((blob) => {
        resolve(blob);
                        });
                    } catch (error) {
        reject(error);
                    }
                });

    const safeName = (name + '_' + surname).replace(/[^A-Za-z0-9_\-]+/g,'_');
    const outName = `gider_${Date.now()}_${safeName}.pdf`;
    const pdfFile = new File([pdfBlob], outName, {type: 'application/pdf' });
    const formData = new FormData();
    formData.append('name', name);
    formData.append('surname', surname);
    formData.append('iban', iban);
    formData.append('total', total);

    const payload = {gider_no: giderNo, items: itemsForBackend };
    formData.append('items_json', JSON.stringify(payload));
    formData.append('pdf', pdfFile);

    let phpUrl = 'receive_pdf.php';
    if (window.location.pathname.includes('forms-expense')) {
                const origin = window.location.origin;
    const pathParts = window.location.pathname.split('/');
    const formsIndex = pathParts.indexOf('forms-expense');
    if (formsIndex !== -1) {
                    const basePath = pathParts.slice(0, formsIndex + 1).join('/');
    phpUrl = origin + basePath + '/receive_pdf.php';
                }
            }

    dlog('Sending PDF to:', phpUrl);
    const response = await fetch(phpUrl, {method: 'POST', body: formData });
    const ct = (response.headers.get('content-type') || '').toLowerCase();

    if (!response.ok) {
                const errText = await response.text().catch(() => '');
    throw new Error(errText || `İstek başarısız: ${response.status}`);
            }

    if (!ct.includes('application/json')) {
                const raw = await response.text().catch(() => '');
    throw new Error(raw ? `Sunucu JSON yerine şunu döndü: ${raw.substring(0, 200)}...` : 'Sunucu beklenen JSON yanıtı döndürmedi.');
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

    // Test amaÃ§lÄ± otomatik doldurma - WordPress ortamÄ± iÃ§in optimize edilmiÅŸ
    function fillTestData() {
            try {
                // Ã–nce mevcut kalemleri temizle
                const container = document.getElementById('itemsContainer');
    container.innerHTML = '';

    // Ä°simler
    const nameEl = document.getElementById('name');
    const surEl = document.getElementById('surname');
    const ibanEl = document.getElementById('iban');
    if (nameEl) nameEl.value = 'Ahmet';
    if (surEl) surEl.value = 'YÄ±lmaz';
    if (ibanEl) {
        ibanEl.value = 'AT611111111111111111';
    // format kurallarÄ±nÄ± uygula
    const evt = new Event('input', {bubbles: true });
    ibanEl.dispatchEvent(evt);
                }

    // Tarih bugÃ¼n
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth()+1).padStart(2,'0');
    const dd = String(today.getDate()).padStart(2,'0');
    const dateStr = `${yyyy}-${mm}-${dd}`;

    // Kalem 1: Genel gider
    addItem();
                setTimeout(() => {
                    const items = container.querySelectorAll('.item');
    const item1 = items[0];
    if (item1) {
        item1.querySelector('input[name="position-datum[]"]').value = dateStr;
    item1.querySelector('select[name="region[]"]').value = 'AT';
    item1.querySelector('select[name="birim[]"]').value = 'muhasebe';
    const turSel1 = item1.querySelector('select[name="gider-turu[]"]');
    turSel1.value = 'genel';
    turSel1.dispatchEvent(new Event('change', {bubbles:true }));
    item1.querySelector('input[name="gider-miktari[]"]').value = '45.50';
    calculateTotal();
                    }
                }, 100);

                // Kalem 2: Ä°kram
                setTimeout(() => {
        addItem();
                    setTimeout(() => {
                        const items = container.querySelectorAll('.item');
    const item2 = items[1];
    if (item2) {
        item2.querySelector('input[name="position-datum[]"]').value = dateStr;
    item2.querySelector('select[name="region[]"]').value = 'KT';
    item2.querySelector('select[name="birim[]"]').value = 'irsad';
    const turSel2 = item2.querySelector('select[name="gider-turu[]"]');
    turSel2.value = 'ikram';
    turSel2.dispatchEvent(new Event('change', {bubbles:true }));
    item2.querySelector('input[name="gider-miktari[]"]').value = '28.90';
    calculateTotal();
                        }
                    }, 100);
                }, 200);

                // Kalem 3: UlaÅŸÄ±m (otomatik hesaplama)
                setTimeout(() => {
        addItem();
                    setTimeout(() => {
                        const items = container.querySelectorAll('.item');
    const item3 = items[2];
    if (item3) {
        item3.querySelector('input[name="position-datum[]"]').value = dateStr;
    item3.querySelector('select[name="region[]"]').value = 'GT';
    item3.querySelector('select[name="birim[]"]').value = 'teskilatlanma';
    const turSel3 = item3.querySelector('select[name="gider-turu[]"]');
    turSel3.value = 'ulasim';
    turSel3.dispatchEvent(new Event('change', {bubbles:true }));
                            // UlaÅŸÄ±m iÃ§in rota bilgileri
                            setTimeout(() => {
                                const startInput = item3.querySelector('.route-start');
    const endInput = item3.querySelector('.route-end');
    const kmField = item3.querySelector('.kilometer-field');
    const routeField = item3.querySelector('.route-full');
    if (startInput) startInput.value = 'Wien, Ã–sterreich';
    if (endInput) endInput.value = 'Graz, Ã–sterreich';
    if (kmField) kmField.value = '200';
    if (routeField) routeField.value = 'Wien â†’ Graz';
    // YakÄ±t tutarÄ±nÄ± hesapla
    if (kmField) {
        calculateYakitAmount(kmField);
                                }
                            }, 100);
                        }
                    }, 100);
                }, 400);

                // BaÅŸarÄ± mesajÄ± gÃ¶ster
                setTimeout(() => {
                    const msg = document.getElementById('errorMessage');
    if (msg) {
        msg.style.color = '#28a745';
    msg.textContent = 'âœ“ Test verileri dolduruldu! Formu kontrol edip gÃ¶nderebilirsiniz.';
                        setTimeout(() => {msg.textContent = ''; msg.style.color = ''; }, 5000);
                    }
                }, 800);

            } catch (e) {
        dlog('fillTestData error', e);
    const msg = document.getElementById('errorMessage');
    if (msg) {
        msg.style.color = '#dc2626';
    msg.textContent = 'Test verisi doldurulamadÄ±: ' + (e.message || 'Bilinmeyen hata');
                }
            }
        }
</script>

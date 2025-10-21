<?php
require_once 'auth.php';

// Login kontrolü kaldırıldı - direkt erişim
$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AIF Otomasyon - Gider Başvurusu</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        /* Gider Formu Özel Stilleri */
        :root {
            --primary-color: #009872;
            --primary-dark: #007a5e;
            --primary-light: #00b085;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
            --shadow-heavy: 0 8px 30px rgba(0,0,0,0.2);
            --bg: #ffffff;
            --card: #ffffff;
            --muted: #6b7280;
            --text: #000000;
            --border: #e5e7eb;
            --input: #ffffff;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
            background: radial-gradient(1200px 600px at 20% 0%, rgba(0,152,114,.05), transparent 60%),
                        radial-gradient(900px 500px at 80% 0%, rgba(0,152,114,.03), transparent 60%),
                        var(--light-color);
            color: var(--text);
            margin: 0;
            padding: 24px;
        }

        /* Modern Header */
        .modern-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .header-subtitle {
            color: #6c757d;
            font-size: 1rem;
            margin-top: 5px;
        }

        /* Form Container */
        .form-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
            background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9));
            border: 2px solid var(--primary-color);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,152,114,.1), inset 0 1px 0 rgba(255,255,255,.9);
            backdrop-filter: blur(8px);
            margin-bottom: 30px;
        }

        .form-title {
            text-align: center;
            font-weight: 700;
            letter-spacing: .3px;
            color: var(--primary-color);
            font-size: 28px;
            margin-bottom: 16px;
        }

        .form-subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 24px;
            font-size: 14px;
        }

        /* Form Grid */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            background: white;
            color: var(--dark-color);
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            font-size: 14px;
        }

        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0,152,114,.15);
        }

        .form-group textarea { 
            resize: vertical; 
            min-height: 70px; 
        }

        /* Items Container */
        .items-container { 
            margin: 8px 0 16px; 
        }

        .expense-item {
            border: 1px solid var(--primary-color);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            background: linear-gradient(180deg, rgba(255,255,255,.9), rgba(248,250,252,.7));
            position: relative;
        }

        .remove-item-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 10px;
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
        }

        .remove-item-btn:hover { 
            filter: brightness(.95); 
        }

        .add-item-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .add-item-button:hover { 
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Special Fields */
        .yakit-fields {
            display: none;
        }

        .route-inputs { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 10px; 
        }

        .calculate-km-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 6px;
            font-weight: 600;
            font-size: 12px;
        }

        .calculate-km-btn:hover { 
            filter: brightness(1.05); 
        }

        .km-loading { 
            display: none; 
            color: var(--primary-color); 
            font-size: 12px; 
            margin-top: 6px; 
        }

        /* Submit Button */
        .submit-container { 
            text-align: center; 
            margin-top: 30px;
        }

        .submit-btn {
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover { 
            filter: brightness(1.05);
            transform: translateY(-2px);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Loading Spinner */
        #spinner {
            display: none;
            margin: 12px auto 0;
            width: 40px; 
            height: 40px;
            border: 4px solid rgba(255,255,255,.2);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 
            to { transform: rotate(360deg); } 
        }

        .error-message { 
            color: #fca5a5; 
            font-weight: 600; 
            margin-top: 10px; 
            text-align: center;
        }

        .success-message {
            color: var(--success-color);
            font-weight: 600;
            margin-top: 10px;
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .form-container {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .header-title h1 {
                font-size: 1.8rem;
            }

            .route-inputs {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 15px;
            }

            .expense-item {
                padding: 15px;
            }
        }

        /* Status Cards */
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-light);
            border-left: 4px solid var(--primary-color);
        }

        .status-card.pending {
            border-left-color: var(--warning-color);
        }

        .status-card.approved {
            border-left-color: var(--success-color);
        }

        .status-card.rejected {
            border-left-color: var(--danger-color);
        }

        .status-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .status-card p {
            color: #6c757d;
            margin: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Modern Header -->
        <div class="modern-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Gider Başvurusu</h1>
                    <div class="header-subtitle">AIF Otomasyon Sistemi - Gider Talep Formu</div>
                </div>
            </div>
        </div>

        <!-- Status Cards -->
        <div class="status-cards">
            <div class="status-card">
                <h3 style="color: var(--primary-color);">0</h3>
                <p>Toplam Başvuru</p>
            </div>
            <div class="status-card pending">
                <h3 style="color: var(--warning-color);">0</h3>
                <p>Bekleyen</p>
            </div>
            <div class="status-card approved">
                <h3 style="color: var(--success-color);">0</h3>
                <p>Onaylanan</p>
            </div>
            <div class="status-card rejected">
                <h3 style="color: var(--danger-color);">0</h3>
                <p>Reddedilen</p>
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <h2 class="form-title">AİF Gider Formu</h2>
            <div class="form-subtitle">Gider kalemlerinizi ekleyin, belgeleri yükleyin ve gönderin.</div>
            
            <form id="expenseForm" onsubmit="handleSubmit(event)">
                <!-- Kişisel Bilgiler -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">İsim</label>
                        <input type="text" id="name" name="name" placeholder="Adınızı giriniz" required>
                    </div>
                    <div class="form-group">
                        <label for="surname">Soyisim</label>
                        <input type="text" id="surname" name="surname" placeholder="Soyadınızı giriniz" required>
                    </div>
                </div>

                <!-- Gider Kalemleri -->
                <div class="items-container" id="itemsContainer"></div>
                <button type="button" class="add-item-button" onclick="addItem()">
                    <i class="fas fa-plus"></i> Kalem Ekle
                </button>

                <!-- IBAN ve Toplam -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="iban">IBAN</label>
                        <input type="text" id="iban" name="iban" placeholder="TR.., AT.. (4'lü bloklarla)" required>
                    </div>
                    <div class="form-group">
                        <label for="total">Toplam Tutar (€)</label>
                        <input type="number" id="total" name="total" readonly>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="submit-container">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Gideri Bildir
                    </button>
                </div>
            </form>

            <!-- Loading Spinner -->
            <div id="spinner"></div>
            <div id="errorMessage" class="error-message"></div>
            <div id="successMessage" class="success-message"></div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let itemCounter = 0;
        let uploadedImages = [];

        // Sayfa yüklendiğinde ilk kalemi ekle
        document.addEventListener('DOMContentLoaded', function() {
            addItem();
            
            // Kullanıcı bilgilerini otomatik doldur
            const currentUser = <?php echo json_encode($currentUser); ?>;
            if (currentUser.full_name) {
                const nameParts = currentUser.full_name.split(' ');
                document.getElementById('name').value = nameParts[0] || '';
                document.getElementById('surname').value = nameParts.slice(1).join(' ') || '';
            }
        });

        // Kalem ekleme fonksiyonu
        function addItem() {
            itemCounter++;
            const itemId = `item_${itemCounter}`;
            
            const itemHTML = `
                <div class="expense-item" id="${itemId}">
                    <button type="button" class="remove-item-btn" onclick="removeItem('${itemId}')">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tarih</label>
                            <input type="date" name="position-datum[]" required>
                        </div>
                        <div class="form-group">
                            <label>Bölge Yönetim Kurulu</label>
                            <select name="region[]" required>
                                <option value="AT">AT - Ana Teşkilat</option>
                                <option value="KT">KT - Kadınlar Teşkilatı</option>
                                <option value="GT">GT - Gençlik Teşkilatı</option>
                                <option value="KGT">KGT - Kadınlar Gençlik Teşkilatı</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Birim</label>
                            <select name="birim[]" required>
                                <option value="baskan">Başkan</option>
                                <option value="baskan_yardimcisi">Başkan Yardımcısı</option>
                                <option value="genel_sekreter">Genel Sekreter</option>
                                <option value="mali_sekreter">Mali Sekreter</option>
                                <option value="egitim_sekreteri">Eğitim Sekreteri</option>
                                <option value="kadin_sekreteri">Kadın Sekreteri</option>
                                <option value="genclik_sekreteri">Gençlik Sekreteri</option>
                                <option value="basin_sekreteri">Basın Sekreteri</option>
                                <option value="hukuk_sekreteri">Hukuk Sekreteri</option>
                                <option value="sosyal_hizmetler_sekreteri">Sosyal Hizmetler Sekreteri</option>
                                <option value="kultur_sekreteri">Kültür Sekreteri</option>
                                <option value="spor_sekreteri">Spor Sekreteri</option>
                                <option value="teknoloji_sekreteri">Teknoloji Sekreteri</option>
                                <option value="cevre_sekreteri">Çevre Sekreteri</option>
                                <option value="uluslararasi_iliskiler_sekreteri">Uluslararası İlişkiler Sekreteri</option>
                                <option value="danisma_kurulu">Danışma Kurulu</option>
                                <option value="denetim_kurulu">Denetim Kurulu</option>
                                <option value="disiplin_kurulu">Disiplin Kurulu</option>
                                <option value="etik_kurulu">Etik Kurulu</option>
                                <option value="stratejik_planlama_kurulu">Stratejik Planlama Kurulu</option>
                                <option value="kriz_yonetim_kurulu">Kriz Yönetim Kurulu</option>
                                <option value="kalite_yonetim_kurulu">Kalite Yönetim Kurulu</option>
                                <option value="proje_yonetim_kurulu">Proje Yönetim Kurulu</option>
                                <option value="arastirma_gelistirme_kurulu">Araştırma Geliştirme Kurulu</option>
                                <option value="insan_kaynaklari_kurulu">İnsan Kaynakları Kurulu</option>
                                <option value="mali_isler_kurulu">Mali İşler Kurulu</option>
                                <option value="hukuk_isleri_kurulu">Hukuk İşleri Kurulu</option>
                                <option value="basin_yayin_kurulu">Basın Yayın Kurulu</option>
                                <option value="egitim_ogretim_kurulu">Eğitim Öğretim Kurulu</option>
                                <option value="kadin_cocuk_kurulu">Kadın Çocuk Kurulu</option>
                                <option value="genclik_spor_kurulu">Gençlik Spor Kurulu</option>
                                <option value="kultur_sanat_kurulu">Kültür Sanat Kurulu</option>
                                <option value="sosyal_hizmetler_kurulu">Sosyal Hizmetler Kurulu</option>
                                <option value="cevre_saglik_kurulu">Çevre Sağlık Kurulu</option>
                                <option value="teknoloji_bilim_kurulu">Teknoloji Bilim Kurulu</option>
                                <option value="uluslararasi_iliskiler_kurulu">Uluslararası İlişkiler Kurulu</option>
                                <option value="diaspora_iliskiler_kurulu">Diaspora İlişkiler Kurulu</option>
                                <option value="avrupa_iliskiler_kurulu">Avrupa İlişkiler Kurulu</option>
                                <option value="turkiye_iliskiler_kurulu">Türkiye İlişkiler Kurulu</option>
                                <option value="islam_dunyasi_iliskiler_kurulu">İslam Dünyası İlişkiler Kurulu</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="gider-turu[]" required onchange="handleGiderTuruChange(this)">
                                <option value="">Lütfen seçin</option>
                                <option value="yakit">Yakıt</option>
                                <option value="ulasim">Ulaşım</option>
                                <option value="konaklama">Konaklama</option>
                                <option value="yemek">Yemek</option>
                                <option value="malzeme">Malzeme</option>
                                <option value="iletisim">İletişim</option>
                                <option value="egitim">Eğitim</option>
                                <option value="etkinlik">Etkinlik</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                    </div>

                    <!-- Yakıt için özel alanlar -->
                    <div class="form-group yakit-fields" style="display:none;">
                        <label>Rota (Otomatik Mesafe)</label>
                        <div class="route-inputs">
                            <div style="position:relative;">
                                <input type="text" name="from[]" placeholder="Başlangıç" class="route-input" data-item="${itemId}">
                            </div>
                            <div style="position:relative;">
                                <input type="text" name="to[]" placeholder="Varış" class="route-input" data-item="${itemId}">
                            </div>
                        </div>
                        <button type="button" class="calculate-km-btn" onclick="calculateRoute('${itemId}')">
                            <i class="fas fa-route"></i> Mesafe Hesapla
                        </button>
                        <div class="km-loading" id="km-loading-${itemId}">Hesaplanıyor...</div>
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
                </div>
            `;
            
            document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', itemHTML);
            calculateTotal();
        }

        // Kalem silme fonksiyonu
        function removeItem(itemId) {
            if (document.querySelectorAll('.expense-item').length <= 1) {
                alert('En az bir kalem olmalıdır!');
                return;
            }
            document.getElementById(itemId).remove();
            calculateTotal();
        }

        // Gider türü değişikliği
        function handleGiderTuruChange(select) {
            const item = select.closest('.expense-item');
            const yakitFields = item.querySelectorAll('.yakit-fields');
            
            if (select.value === 'yakit') {
                yakitFields.forEach(field => field.style.display = 'block');
            } else {
                yakitFields.forEach(field => field.style.display = 'none');
            }
        }

        // Toplam hesaplama
        function calculateTotal() {
            const amounts = document.querySelectorAll('input[name="gider-miktari[]"]');
            let total = 0;
            
            amounts.forEach(input => {
                const value = parseFloat(input.value.replace(',', '.')) || 0;
                total += value;
            });
            
            document.getElementById('total').value = total.toFixed(2);
        }

        // Sayı formatı
        function formatToDecimal(input) {
            let value = input.value.replace(/[^0-9.,]/g, '');
            value = value.replace(',', '.');
            input.value = value;
        }

        // Yakıt miktarı hesaplama
        function calculateYakitAmount(input) {
            const km = parseFloat(input.value) || 0;
            const rate = 0.30; // €/km oranı
            const amount = km * rate;
            
            const item = input.closest('.expense-item');
            const amountInput = item.querySelector('input[name="gider-miktari[]"]');
            amountInput.value = amount.toFixed(2);
            
            calculateTotal();
        }

        // Rota hesaplama (basit versiyon)
        function calculateRoute(itemId) {
            const item = document.getElementById(itemId);
            const fromInput = item.querySelector('input[name="from[]"]');
            const toInput = item.querySelector('input[name="to[]"]');
            const kmInput = item.querySelector('.kilometer-field');
            const loading = document.getElementById(`km-loading-${itemId}`);
            
            if (!fromInput.value || !toInput.value) {
                alert('Lütfen başlangıç ve varış noktalarını giriniz!');
                return;
            }
            
            loading.style.display = 'block';
            
            // Basit mesafe hesaplama (gerçek uygulamada API kullanılabilir)
            setTimeout(() => {
                const distance = Math.floor(Math.random() * 200) + 10; // 10-210 km arası rastgele
                kmInput.value = distance;
                calculateYakitAmount(kmInput);
                loading.style.display = 'none';
            }, 1500);
        }

        // Form gönderme
        function handleSubmit(event) {
            event.preventDefault();
            
            const submitBtn = document.querySelector('.submit-btn');
            const spinner = document.getElementById('spinner');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            
            // Form verilerini kontrol et
            const formData = new FormData(event.target);
            const name = formData.get('name');
            const surname = formData.get('surname');
            const iban = formData.get('iban');
            const total = formData.get('total');
            
            if (!name || !surname || !iban || !total || total <= 0) {
                showMessage('Lütfen tüm alanları doldurun ve en az bir gider kalemi ekleyin!', 'error');
                return;
            }
            
            // Loading durumu
            submitBtn.disabled = true;
            spinner.style.display = 'block';
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
            
            // Simüle edilmiş gönderme
            setTimeout(() => {
                spinner.style.display = 'none';
                submitBtn.disabled = false;
                
                // Başarı mesajı
                showMessage('Gider başvurunuz başarıyla gönderildi! Onay için bekleyiniz.', 'success');
                
                // Formu temizle
                event.target.reset();
                document.getElementById('itemsContainer').innerHTML = '';
                addItem();
                
            }, 2000);
        }

        // Mesaj gösterme
        function showMessage(message, type) {
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            
            if (type === 'error') {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
            } else {
                successMessage.textContent = message;
                successMessage.style.display = 'block';
                errorMessage.style.display = 'none';
            }
            
            // 5 saniye sonra mesajı gizle
            setTimeout(() => {
                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>
<?php
require_once 'auth.php';

// Login kontrolü kaldırıldı - direkt erişim
$currentUser = SessionManager::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Rezervasyonlar</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .calendar-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .calendar-day-header {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            color: #666;
        }
        
        .calendar-day {
            background: white;
            padding: 10px;
            min-height: 80px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .calendar-day:hover {
            background: #f0f8ff;
        }
        
        .calendar-day.other-month {
            background: #f8f9fa;
            color: #ccc;
        }
        
        .calendar-day.today {
            background: #e3f2fd;
            border: 2px solid #2196f3;
        }
        
        .calendar-day.selected {
            background: #2196f3;
            color: white;
        }
        
        .calendar-day.reserved {
            background: #ffebee;
            border: 2px solid #f44336;
            cursor: not-allowed;
        }
        
        .calendar-day.reserved:hover {
            background: #ffebee;
        }
        
        .day-number {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .day-events {
            font-size: 0.8rem;
            color: #666;
        }
        
        .reservation-form {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h5 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .date-range-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        
        .availability-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .availability-status.available {
            background: #d4edda;
            color: #155724;
        }
        
        .availability-status.reserved {
            background: #f8d7da;
            color: #721c24;
        }
        
        .availability-status.partial {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Rezervasyonlar</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="showReservationForm()">
                        <i class="fas fa-plus"></i> Yeni Rezervasyon
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Reservation Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check text-primary"></i>
                            </div>
                            <h3 id="totalReservations">0</h3>
                            <p class="text-muted">Toplam Rezervasyon</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <h3 id="approvedReservations">0</h3>
                            <p class="text-muted">Onaylanan</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-clock text-warning"></i>
                            </div>
                            <h3 id="pendingReservations">0</h3>
                            <p class="text-muted">Bekleyen</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="page-card">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt text-info"></i>
                            </div>
                            <h3 id="thisMonthReservations">0</h3>
                            <p class="text-muted">Bu Ay</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar and Form Section -->
            <div class="row">
                <!-- Calendar -->
                <div class="col-lg-8">
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <div class="calendar-nav">
                                <button class="btn btn-outline-primary" onclick="previousMonth()">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <h2 class="calendar-title" id="calendarTitle">2025 Yılı Takvimi</h2>
                                <button class="btn btn-outline-primary" onclick="nextMonth()">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="calendar-nav">
                                <button class="btn btn-primary" onclick="showReservationForm()">
                                    <i class="fas fa-plus"></i> Rezervasyon Yap
                                </button>
                            </div>
                        </div>
                        
                        <div class="calendar-grid" id="calendarGrid">
                            <!-- Calendar will be generated here -->
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex gap-3">
                                <div class="d-flex align-items-center">
                                    <div class="availability-status available me-2"></div>
                                    <small>Müsait</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="availability-status reserved me-2"></div>
                                    <small>Rezerve</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="availability-status partial me-2"></div>
                                    <small>Kısmen Dolu</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reservation Form -->
                <div class="col-lg-4">
                    <div class="reservation-form" id="reservationForm" style="display: none;">
                        <h4><i class="fas fa-calendar-plus"></i> Yeni Rezervasyon</h4>
                        
                        <div class="date-range-display" id="dateRangeDisplay">
                            <strong>Seçilen Tarih Aralığı:</strong><br>
                            <span id="selectedDateRange">Tarih seçin</span>
                        </div>
                        
                        <form id="reservationFormData">
                            <!-- Kişisel Bilgiler -->
                            <div class="form-section">
                                <h5><i class="fas fa-user"></i> Kişisel Bilgiler</h5>
                                <div class="mb-3">
                                    <label for="applicantName" class="form-label">Ad Soyad *</label>
                                    <input type="text" class="form-control" id="applicantName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="applicantPhone" class="form-label">Telefon *</label>
                                    <input type="tel" class="form-control" id="applicantPhone" required>
                                </div>
                                <div class="mb-3">
                                    <label for="applicantEmail" class="form-label">E-posta</label>
                                    <input type="email" class="form-control" id="applicantEmail">
                                </div>
                            </div>

                            <!-- Organizasyon Bilgileri -->
                            <div class="form-section">
                                <h5><i class="fas fa-building"></i> Organizasyon Bilgileri</h5>
                                <div class="mb-3">
                                    <label for="region" class="form-label">Bölge *</label>
                                    <select class="form-select" id="region" required>
                                        <option value="">Bölge Seçin</option>
                                        <!-- Dinamik olarak yüklenecek -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="unit" class="form-label">Birim *</label>
                                    <select class="form-select" id="unit" required>
                                        <option value="">Birim Seçin</option>
                                        <!-- Dinamik olarak yüklenecek -->
                                    </select>
                                </div>
                            </div>

                            <!-- Etkinlik Bilgileri -->
                            <div class="form-section">
                                <h5><i class="fas fa-calendar-alt"></i> Etkinlik Bilgileri</h5>
                                <div class="mb-3">
                                    <label for="eventName" class="form-label">Etkinlik Adı *</label>
                                    <input type="text" class="form-control" id="eventName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="eventDescription" class="form-label">Etkinlik Açıklaması</label>
                                    <textarea class="form-control" id="eventDescription" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="expectedParticipants" class="form-label">Beklenen Katılımcı Sayısı</label>
                                    <input type="number" class="form-control" id="expectedParticipants" min="1">
                                </div>
                            </div>

                            <!-- Tarih Bilgileri (Hidden - Calendar'dan gelecek) -->
                            <input type="hidden" id="startDate" name="startDate">
                            <input type="hidden" id="endDate" name="endDate">

                            <!-- Submit Button -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Rezervasyon Başvurusu Yap
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="hideReservationForm()">
                                    <i class="fas fa-times"></i> İptal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reservations List -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="page-card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> Rezervasyonlar</h5>
                            <div class="card-header-actions">
                                <select class="form-select" id="statusFilter" onchange="filterReservations()">
                                    <option value="all">Tüm Durumlar</option>
                                    <option value="pending">Bekleyen</option>
                                    <option value="approved">Onaylanan</option>
                                    <option value="rejected">Reddedilen</option>
                                    <option value="cancelled">İptal</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Rezervasyon</th>
                                            <th>Tarih</th>
                                            <th>Saat</th>
                                            <th>Rezerv Eden</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reservationsTableBody">
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                Henüz rezervasyon bulunmamaktadır.
                                                <br>
                                                <button class="btn btn-primary btn-sm mt-2" onclick="showReservationForm()">
                                                    İlk Rezervasyonu Ekle
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Calendar variables
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();
        let selectedStartDate = null;
        let selectedEndDate = null;
        let reservations = [];
        let events = [];

        // Month names in Turkish
        const monthNames = [
            'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
            'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
        ];

        const dayNames = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

        // Load reservations and events
        async function loadData() {
            await Promise.all([
                loadReservations(),
                loadEvents(),
                loadFormData()
            ]);
            generateCalendar();
            updateStatistics();
        }

        // Load form data (regions and units)
        async function loadFormData() {
            try {
                const response = await fetch('../api/reservations_api.php?action=get_form_data');
                const data = await response.json();
                
                if (data.success) {
                    // Populate regions dropdown
                    const regionSelect = document.getElementById('region');
                    regionSelect.innerHTML = '<option value="">Bölge Seçin</option>';
                    data.regions.forEach(region => {
                        const option = document.createElement('option');
                        option.value = region.value;
                        option.textContent = region.label;
                        regionSelect.appendChild(option);
                    });
                    
                    // Populate units dropdown
                    const unitSelect = document.getElementById('unit');
                    unitSelect.innerHTML = '<option value="">Birim Seçin</option>';
                    data.units.forEach(unit => {
                        const option = document.createElement('option');
                        option.value = unit.value;
                        option.textContent = unit.label;
                        option.title = unit.description; // Tooltip için açıklama
                        unitSelect.appendChild(option);
                    });
                } else {
                    console.error('Form verileri yüklenirken hata:', data.message);
                }
            } catch (error) {
                console.error('Form verileri yüklenirken hata:', error);
            }
        }

        // Load reservations from database
        async function loadReservations() {
            try {
                const response = await fetch('../api/reservations_api.php?action=list');
                const data = await response.json();
                
                if (data.success) {
                    reservations = data.reservations || [];
                } else {
                    console.error('Rezervasyonlar yüklenirken hata:', data.message);
                }
            } catch (error) {
                console.error('Rezervasyonlar yüklenirken hata:', error);
            }
        }

        // Load events from calendar
        async function loadEvents() {
            try {
                const response = await fetch(`../calendar_api.php?action=list&year=${currentYear}&month=${currentMonth + 1}`);
                const data = await response.json();
                
                if (data.success) {
                    events = data.events || [];
                } else {
                    console.error('Etkinlikler yüklenirken hata:', data.message);
                }
            } catch (error) {
                console.error('Etkinlikler yüklenirken hata:', error);
            }
        }

        // Generate calendar
        function generateCalendar() {
            const calendarGrid = document.getElementById('calendarGrid');
            const calendarTitle = document.getElementById('calendarTitle');
            
            // Update title
            calendarTitle.textContent = `${monthNames[currentMonth]} ${currentYear}`;
            
            // Clear grid
            calendarGrid.innerHTML = '';
            
            // Add day headers
            dayNames.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day-header';
                dayHeader.textContent = day;
                calendarGrid.appendChild(dayHeader);
            });
            
            // Get first day of month and number of days
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
            
            // Add previous month days
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                const dayElement = createDayElement(day, true);
                calendarGrid.appendChild(dayElement);
            }
            
            // Add current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = createDayElement(day, false);
                calendarGrid.appendChild(dayElement);
            }
            
            // Add next month days to fill grid
            const totalCells = calendarGrid.children.length;
            const remainingCells = 42 - totalCells; // 6 rows * 7 days
            for (let day = 1; day <= remainingCells; day++) {
                const dayElement = createDayElement(day, true);
                calendarGrid.appendChild(dayElement);
            }
        }

        // Create day element
        function createDayElement(day, isOtherMonth) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            
            if (isOtherMonth) {
                dayElement.classList.add('other-month');
            } else {
                // Check if today
                const today = new Date();
                if (day === today.getDate() && 
                    currentMonth === today.getMonth() && 
                    currentYear === today.getFullYear()) {
                    dayElement.classList.add('today');
                }
                
                // Check if reserved
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayReservations = reservations.filter(res => 
                    res.start_date <= dateStr && res.end_date >= dateStr && 
                    res.status !== 'cancelled'
                );
                
                if (dayReservations.length > 0) {
                    dayElement.classList.add('reserved');
                    dayElement.title = `Rezerve: ${dayReservations.map(r => r.event_name).join(', ')}`;
                } else {
                    dayElement.onclick = () => selectDate(day);
                }
            }
            
            // Day number
            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = day;
            dayElement.appendChild(dayNumber);
            
            // Day events/reservations
            if (!isOtherMonth) {
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayReservations = reservations.filter(res => 
                    res.start_date <= dateStr && res.end_date >= dateStr && 
                    res.status !== 'cancelled'
                );
                
                if (dayReservations.length > 0) {
                    const eventsDiv = document.createElement('div');
                    eventsDiv.className = 'day-events';
                    eventsDiv.textContent = `${dayReservations.length} rezervasyon`;
                    dayElement.appendChild(eventsDiv);
                }
            }
            
            return dayElement;
        }

        // Select date for reservation
        function selectDate(day) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            if (!selectedStartDate) {
                selectedStartDate = dateStr;
                selectedEndDate = dateStr;
            } else if (!selectedEndDate || selectedStartDate === selectedEndDate) {
                if (dateStr < selectedStartDate) {
                    selectedEndDate = selectedStartDate;
                    selectedStartDate = dateStr;
                } else {
                    selectedEndDate = dateStr;
                }
            } else {
                selectedStartDate = dateStr;
                selectedEndDate = dateStr;
            }
            
            updateDateRangeDisplay();
            showReservationForm();
            generateCalendar(); // Refresh to show selection
        }

        // Update date range display
        function updateDateRangeDisplay() {
            const dateRangeElement = document.getElementById('selectedDateRange');
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            
            if (selectedStartDate && selectedEndDate) {
                const startDate = new Date(selectedStartDate);
                const endDate = new Date(selectedEndDate);
                
                if (selectedStartDate === selectedEndDate) {
                    dateRangeElement.textContent = startDate.toLocaleDateString('tr-TR');
                } else {
                    dateRangeElement.textContent = `${startDate.toLocaleDateString('tr-TR')} - ${endDate.toLocaleDateString('tr-TR')}`;
                }
                
                startDateInput.value = selectedStartDate;
                endDateInput.value = selectedEndDate;
            } else {
                dateRangeElement.textContent = 'Tarih seçin';
                startDateInput.value = '';
                endDateInput.value = '';
            }
        }

        // Show reservation form
        function showReservationForm() {
            document.getElementById('reservationForm').style.display = 'block';
        }

        // Hide reservation form
        function hideReservationForm() {
            document.getElementById('reservationForm').style.display = 'none';
            selectedStartDate = null;
            selectedEndDate = null;
            generateCalendar();
        }

        // Submit reservation form
        document.getElementById('reservationFormData').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                applicant_name: document.getElementById('applicantName').value,
                applicant_phone: document.getElementById('applicantPhone').value,
                applicant_email: document.getElementById('applicantEmail').value,
                region: document.getElementById('region').value,
                unit: document.getElementById('unit').value,
                event_name: document.getElementById('eventName').value,
                event_description: document.getElementById('eventDescription').value,
                expected_participants: document.getElementById('expectedParticipants').value,
                start_date: document.getElementById('startDate').value,
                end_date: document.getElementById('endDate').value,
                status: 'pending'
            };
            
            try {
                const response = await fetch('../api/reservations_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'create',
                        ...formData
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Rezervasyon başvurunuz başarıyla gönderildi!');
                    hideReservationForm();
                    document.getElementById('reservationFormData').reset();
                    loadData(); // Reload data
                } else {
                    alert('Hata: ' + data.message);
                }
            } catch (error) {
                console.error('Rezervasyon gönderilirken hata:', error);
                alert('Rezervasyon gönderilirken hata oluştu!');
            }
        });

        // Navigation functions
        function previousMonth() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            loadData();
        }

        function nextMonth() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            loadData();
        }

        // Update statistics
        function updateStatistics() {
            const total = reservations.length;
            const approved = reservations.filter(r => r.status === 'approved').length;
            const pending = reservations.filter(r => r.status === 'pending').length;
            
            const currentMonthStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}`;
            const thisMonth = reservations.filter(r => r.start_date.startsWith(currentMonthStr)).length;
            
            document.getElementById('totalReservations').textContent = total;
            document.getElementById('approvedReservations').textContent = approved;
            document.getElementById('pendingReservations').textContent = pending;
            document.getElementById('thisMonthReservations').textContent = thisMonth;
        }

        // Filter reservations
        function filterReservations() {
            const status = document.getElementById('statusFilter').value;
            // Implementation for filtering reservations table
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
        });
    </script>
</body>
</html>
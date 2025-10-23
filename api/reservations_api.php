<?php
require_once 'admin/includes/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Rezervasyonları listele
                $sql = "SELECT * FROM reservations ORDER BY start_date DESC, created_at DESC";
                $reservations = $db->fetchAll($sql);
                
                echo json_encode([
                    'success' => true,
                    'reservations' => $reservations
                ]);
            } elseif ($action === 'get_form_data') {
                // Form için gerekli verileri çek
                
                // Bölgeleri byk_categories'den çek
                $regions = [];
                try {
                    $regionSql = "SELECT DISTINCT category FROM byk_categories WHERE category IS NOT NULL ORDER BY category";
                    $regionResults = $db->fetchAll($regionSql);
                    foreach ($regionResults as $row) {
                        $regions[] = [
                            'value' => strtolower(str_replace(' ', '_', $row['category'])),
                            'label' => $row['category']
                        ];
                    }
                } catch (Exception $e) {
                    // Fallback: Manuel bölge listesi
                    $regions = [
                        ['value' => 'tirol', 'label' => 'Tirol'],
                        ['value' => 'vorarlberg', 'label' => 'Vorarlberg'],
                        ['value' => 'salzburg', 'label' => 'Salzburg'],
                        ['value' => 'wien', 'label' => 'Wien'],
                        ['value' => 'steyermark', 'label' => 'Steyermark'],
                        ['value' => 'oberoesterreich', 'label' => 'Oberösterreich'],
                        ['value' => 'niederoesterreich', 'label' => 'Niederösterreich'],
                        ['value' => 'burgenland', 'label' => 'Burgenland'],
                        ['value' => 'kaernten', 'label' => 'Kärnten']
                    ];
                }
                
                // Birimleri units tablosundan çek
                $units = [];
                try {
                    $unitSql = "SELECT code, name, description FROM units WHERE is_active = 1 ORDER BY code";
                    $unitResults = $db->fetchAll($unitSql);
                    foreach ($unitResults as $row) {
                        $units[] = [
                            'value' => $row['code'],
                            'label' => $row['code'] . ' - ' . $row['name'],
                            'description' => $row['description']
                        ];
                    }
                } catch (Exception $e) {
                    // Fallback: Manuel birim listesi
                    $units = [
                        ['value' => 'GB', 'label' => 'GB - Genel Başkanlık', 'description' => 'Kuruluşun en üst düzey yönetim birimidir.'],
                        ['value' => 'T', 'label' => 'T - Teşkilatlanma', 'description' => 'Üye ve şube yapılanmasını koordine eder.'],
                        ['value' => 'E', 'label' => 'E - Eğitim', 'description' => 'Eğitim programları ve materyallerini hazırlar.'],
                        ['value' => 'I', 'label' => 'I - İrşad', 'description' => 'Manevi rehberlik ve irşat faaliyetlerinden sorumludur.'],
                        ['value' => 'KI', 'label' => 'KI - Kurumsal İletişim', 'description' => 'Kurumun dış paydaşlarla iletişimini sağlar.'],
                        ['value' => 'SH', 'label' => 'SH - Sosyal Hizmetler', 'description' => 'Sosyal yardım ve hizmet faaliyetlerini yürütür.'],
                        ['value' => 'KT', 'label' => 'KT - Kadınlar Teşkilatı', 'description' => 'Kadınlara yönelik faaliyetler ve organizasyonları düzenler.'],
                        ['value' => 'KGT', 'label' => 'KGT - Kadınlar Gençlik Teşkilatı', 'description' => 'Kadın gençlere özel faaliyetler yürütür.'],
                        ['value' => 'GT', 'label' => 'GT - Gençlik Teşkilatı', 'description' => 'Gençlere yönelik faaliyetler ve organizasyonları koordine eder.']
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'regions' => $regions,
                    'units' => $units
                ]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                // Yeni rezervasyon oluştur
                $applicant_name = $input['applicant_name'] ?? '';
                $applicant_phone = $input['applicant_phone'] ?? '';
                $applicant_email = $input['applicant_email'] ?? '';
                $region = $input['region'] ?? '';
                $unit = $input['unit'] ?? '';
                $event_name = $input['event_name'] ?? '';
                $event_description = $input['event_description'] ?? '';
                $expected_participants = $input['expected_participants'] ?? null;
                $start_date = $input['start_date'] ?? '';
                $end_date = $input['end_date'] ?? $start_date;
                $status = $input['status'] ?? 'pending';
                
                if (empty($applicant_name) || empty($applicant_phone) || empty($region) || 
                    empty($unit) || empty($event_name) || empty($start_date)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Zorunlu alanlar doldurulmalıdır!'
                    ]);
                    exit;
                }
                
                // Tarih çakışması kontrolü
                $conflictSql = "SELECT COUNT(*) FROM reservations 
                               WHERE status NOT IN ('cancelled', 'rejected') 
                               AND ((start_date <= ? AND end_date >= ?) 
                                    OR (start_date <= ? AND end_date >= ?)
                                    OR (start_date >= ? AND end_date <= ?))";
                $conflictCount = $db->fetchOne($conflictSql, [
                    $start_date, $start_date,
                    $end_date, $end_date,
                    $start_date, $end_date
                ])['COUNT(*)'];
                
                if ($conflictCount > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Seçilen tarih aralığında başka bir rezervasyon bulunmaktadır!'
                    ]);
                    exit;
                }
                
                $result = $db->insert('reservations', [
                    'applicant_name' => $applicant_name,
                    'applicant_phone' => $applicant_phone,
                    'applicant_email' => $applicant_email,
                    'region' => $region,
                    'unit' => $unit,
                    'event_name' => $event_name,
                    'event_description' => $event_description,
                    'expected_participants' => $expected_participants,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rezervasyon başarıyla oluşturuldu!',
                        'reservation_id' => $db->lastInsertId()
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Rezervasyon oluşturulurken hata oluştu!'
                    ]);
                }
            } elseif ($action === 'update') {
                // Rezervasyon güncelle
                $reservation_id = $input['id'] ?? null;
                $status = $input['status'] ?? '';
                
                if (!$reservation_id || empty($status)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Rezervasyon ID ve durum gerekli!'
                    ]);
                    exit;
                }
                
                $result = $db->update('reservations', [
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$reservation_id]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rezervasyon başarıyla güncellendi!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Rezervasyon güncellenirken hata oluştu!'
                    ]);
                }
            } elseif ($action === 'delete') {
                // Rezervasyon sil
                $reservation_id = $input['id'] ?? null;
                
                if (!$reservation_id) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Rezervasyon ID gerekli!'
                    ]);
                    exit;
                }
                
                $result = $db->query("DELETE FROM reservations WHERE id = ?", [$reservation_id]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rezervasyon başarıyla silindi!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Rezervasyon silinirken hata oluştu!'
                    ]);
                }
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Geçersiz HTTP metodu!'
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>

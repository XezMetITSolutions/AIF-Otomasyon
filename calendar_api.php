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
                // Etkinlikleri listele
                $year = $_GET['year'] ?? date('Y');
                $month = $_GET['month'] ?? date('n');
                
                $sql = "SELECT * FROM calendar_events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ? ORDER BY start_date";
                $events = $db->fetchAll($sql, [$year, $month]);
                
                echo json_encode([
                    'success' => true,
                    'events' => $events
                ]);
            }
            break;
            
        case 'POST':
            if ($action === 'save') {
                // Etkinlik kaydet/güncelle
                $input = json_decode(file_get_contents('php://input'), true);
                
                $title = $input['title'] ?? '';
                $start_date = $input['start_date'] ?? '';
                $end_date = $input['end_date'] ?? $start_date;
                $byk_category = $input['byk_category'] ?? '';
                $description = $input['description'] ?? '';
                $is_recurring = $input['is_recurring'] ?? 0;
                $recurrence_type = $input['recurrence_type'] ?? 'none';
                $recurrence_pattern = $input['recurrence_pattern'] ?? '';
                $recurrence_end_date = $input['recurrence_end_date'] ?? null;
                $event_id = $input['id'] ?? null;
                
                if (empty($title) || empty($start_date) || empty($byk_category)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Başlık, tarih ve BYK kategorisi zorunludur!'
                    ]);
                    exit;
                }
                
                if ($event_id) {
                    // Güncelle
                    $sql = "UPDATE calendar_events SET title=?, start_date=?, end_date=?, byk_category=?, description=?, is_recurring=?, recurrence_type=?, recurrence_pattern=?, recurrence_end_date=? WHERE id=?";
                    $result = $db->query($sql, [
                        $title, $start_date, $end_date, $byk_category, $description,
                        $is_recurring, $recurrence_type, $recurrence_pattern, $recurrence_end_date, $event_id
                    ]);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Etkinlik başarıyla güncellendi!',
                            'event_id' => $event_id
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Etkinlik güncellenirken hata oluştu!'
                        ]);
                    }
                } else {
                    // Yeni ekle
                    $sql = "INSERT INTO calendar_events (title, start_date, end_date, byk_category, description, is_recurring, recurrence_type, recurrence_pattern, recurrence_end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $result = $db->query($sql, [
                        $title, $start_date, $end_date, $byk_category, $description,
                        $is_recurring, $recurrence_type, $recurrence_pattern, $recurrence_end_date
                    ]);
                    
                    if ($result) {
                        $event_id = $db->lastInsertId();
                        echo json_encode([
                            'success' => true,
                            'message' => 'Etkinlik başarıyla eklendi!',
                            'event_id' => $event_id
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Etkinlik eklenirken hata oluştu!'
                        ]);
                    }
                }
            } elseif ($action === 'delete') {
                // Etkinlik sil
                $input = json_decode(file_get_contents('php://input'), true);
                $event_id = $input['id'] ?? null;
                
                if (!$event_id) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Etkinlik ID gerekli!'
                    ]);
                    exit;
                }
                
                $sql = "DELETE FROM calendar_events WHERE id = ?";
                $result = $db->query($sql, [$event_id]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Etkinlik başarıyla silindi!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Etkinlik silinirken hata oluştu!'
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

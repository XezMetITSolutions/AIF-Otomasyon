<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'admin/includes/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    switch ($action) {
        case 'get_meetings':
            // Toplantıları listele
            $sql = "
                SELECT m.*, 
                       bu.name as byk_name,
                       bu.color as byk_color,
                       COUNT(DISTINCT mp.id) as participant_count,
                       COUNT(DISTINCT ma.id) as agenda_count,
                       COUNT(DISTINCT md.id) as decision_count,
                       COUNT(DISTINCT CASE WHEN md.status = 'pending' THEN md.id END) as pending_decisions
                FROM meetings m
                LEFT JOIN byk_units bu ON m.byk_code = bu.code
                LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id
                LEFT JOIN meeting_agenda ma ON m.id = ma.meeting_id
                LEFT JOIN meeting_decisions md ON m.id = md.meeting_id
                GROUP BY m.id
                ORDER BY m.meeting_date DESC, m.meeting_time DESC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $meetings = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $meetings;
            break;
            
        case 'get_meeting':
            // Tek toplantı detayı
            $id = $_GET['id'] ?? 0;
            
            // Toplantı bilgileri
            $sql = "SELECT m.*, bu.name as byk_name, bu.color as byk_color FROM meetings m 
                    LEFT JOIN byk_units bu ON m.byk_code = bu.code WHERE m.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $meeting = $stmt->fetch();
            
            if ($meeting) {
                // Katılımcılar
                $sql = "SELECT * FROM meeting_participants WHERE meeting_id = ? ORDER BY participant_role, participant_name";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $meeting['participants'] = $stmt->fetchAll();
                
                // Gündem maddeleri
                $sql = "SELECT * FROM meeting_agenda WHERE meeting_id = ? ORDER BY agenda_order";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $meeting['agenda'] = $stmt->fetchAll();
                
                // Kararlar
                $sql = "SELECT * FROM meeting_decisions WHERE meeting_id = ? ORDER BY decision_number";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $meeting['decisions'] = $stmt->fetchAll();
                
                // Dosyalar
                $sql = "SELECT * FROM meeting_files WHERE meeting_id = ? ORDER BY created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $meeting['files'] = $stmt->fetchAll();
            }
            
            $response['success'] = true;
            $response['data'] = $meeting;
            break;
            
        case 'add_meeting':
            // Yeni toplantı ekle
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Debug için log
            error_log('Meeting API - Received data: ' . json_encode($data));
            
            $sql = "INSERT INTO meetings (byk_code, title, meeting_date, meeting_time, end_time, location, chairman, secretary, status, meeting_type, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $data['byk'] ?? '',
                $data['title'] ?? '',
                $data['date'] ?? '',
                $data['time'] ?? '',
                $data['end_time'] ?? null,
                $data['location'] ?? '',
                $data['chairman'] ?? '',
                $data['secretary'] ?? '',
                $data['status'] ?? 'planned',
                $data['meeting_type'] ?? 'regular',
                $data['notes'] ?? ''
            ]);
            
            if ($result) {
                $meetingId = $pdo->lastInsertId();
                
                // Katılımcıları ekle
                if (!empty($data['participants'])) {
                    error_log('Meeting API - Adding participants: ' . json_encode($data['participants']));
                    $sql = "INSERT INTO meeting_participants (meeting_id, participant_name, participant_role, attendance_status) VALUES (?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    
                    foreach ($data['participants'] as $participant) {
                        $stmt->execute([
                            $meetingId,
                            $participant['name'] ?? '',
                            $participant['role'] ?? 'member',
                            $participant['status'] ?? 'invited'
                        ]);
                    }
                } else {
                    error_log('Meeting API - No participants to add');
                }
                
                // Gündem maddelerini ekle
                if (!empty($data['agenda'])) {
                    error_log('Meeting API - Adding agenda: ' . json_encode($data['agenda']));
                    $sql = "INSERT INTO meeting_agenda (meeting_id, agenda_order, title, description, responsible_person, estimated_duration) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    
                    foreach ($data['agenda'] as $index => $item) {
                        $stmt->execute([
                            $meetingId,
                            $index + 1,
                            $item['title'] ?? '',
                            $item['description'] ?? '',
                            $item['responsible'] ?? '',
                            $item['duration'] ?? 15
                        ]);
                    }
                } else {
                    error_log('Meeting API - No agenda to add');
                }
                
                $response['success'] = true;
                $response['message'] = 'Toplantı başarıyla oluşturuldu';
                $response['data'] = ['id' => $meetingId];
            } else {
                $response['message'] = 'Toplantı oluşturulamadı';
            }
            break;
            
        case 'update_meeting':
            // Toplantı güncelle
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            
            $sql = "UPDATE meetings SET byk_code = ?, title = ?, meeting_date = ?, meeting_time = ?, end_time = ?, 
                    location = ?, chairman = ?, secretary = ?, status = ?, meeting_type = ?, notes = ? WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $data['byk'] ?? '',
                $data['title'] ?? '',
                $data['date'] ?? '',
                $data['time'] ?? '',
                $data['end_time'] ?? null,
                $data['location'] ?? '',
                $data['chairman'] ?? '',
                $data['secretary'] ?? '',
                $data['status'] ?? 'planned',
                $data['meeting_type'] ?? 'regular',
                $data['notes'] ?? '',
                $id
            ]);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Toplantı başarıyla güncellendi';
            } else {
                $response['message'] = 'Toplantı güncellenemedi';
            }
            break;
            
        case 'delete_meeting':
            // Toplantı sil
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            
            $sql = "DELETE FROM meetings WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Toplantı başarıyla silindi';
            } else {
                $response['message'] = 'Toplantı silinemedi';
            }
            break;
            
        case 'add_agenda_item':
            // Gündem maddesi ekle
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sql = "INSERT INTO meeting_agenda (meeting_id, agenda_order, title, description, responsible_person, estimated_duration) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $data['meeting_id'] ?? 0,
                $data['order'] ?? 1,
                $data['title'] ?? '',
                $data['description'] ?? '',
                $data['responsible'] ?? '',
                $data['duration'] ?? 15
            ]);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Gündem maddesi eklendi';
                $response['data'] = ['id' => $pdo->lastInsertId()];
            } else {
                $response['message'] = 'Gündem maddesi eklenemedi';
            }
            break;
            
        case 'add_decision':
            // Karar ekle
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Karar numarası oluştur
            $bykCode = $data['byk_code'] ?? 'AT';
            $year = date('Y');
            $sql = "SELECT COUNT(*) + 1 as next_num FROM meeting_decisions WHERE meeting_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['meeting_id'] ?? 0]);
            $nextNum = $stmt->fetch()['next_num'];
            $decisionNumber = $year . '-' . $bykCode . '-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO meeting_decisions (meeting_id, decision_number, decision_text, responsible_person, deadline, priority, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $data['meeting_id'] ?? 0,
                $decisionNumber,
                $data['decision_text'] ?? '',
                $data['responsible'] ?? '',
                $data['deadline'] ?? null,
                $data['priority'] ?? 'medium',
                $data['status'] ?? 'pending'
            ]);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Karar eklendi';
                $response['data'] = ['id' => $pdo->lastInsertId(), 'decision_number' => $decisionNumber];
            } else {
                $response['message'] = 'Karar eklenemedi';
            }
            break;
            
        case 'update_decision_status':
            // Karar durumu güncelle
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            
            $sql = "UPDATE meeting_decisions SET status = ?, progress_notes = ?, completion_date = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            $completionDate = ($data['status'] === 'completed') ? date('Y-m-d') : null;
            
            $result = $stmt->execute([
                $data['status'] ?? 'pending',
                $data['progress_notes'] ?? '',
                $completionDate,
                $id
            ]);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Karar durumu güncellendi';
            } else {
                $response['message'] = 'Karar durumu güncellenemedi';
            }
            break;
            
        case 'get_byk_units':
            // BYK birimlerini getir
            $sql = "SELECT * FROM byk_units WHERE is_active = 1 ORDER BY code";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $units = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $units;
            break;
            
        case 'get_meeting_stats':
            // Toplantı istatistikleri
            error_log('Meeting API - Getting meeting stats');
            
            $sql = "
                SELECT 
                    COUNT(*) as total_meetings,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_meetings,
                    COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing_meetings,
                    COUNT(CASE WHEN status = 'planned' THEN 1 END) as planned_meetings,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_meetings
                FROM meetings
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats = $stmt->fetch();
            
            error_log('Meeting API - Meeting stats query result: ' . json_encode($stats));
            
            // Bekleyen kararlar
            $sql = "SELECT COUNT(*) as pending_decisions FROM meeting_decisions WHERE status IN ('pending', 'in_progress')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $pendingDecisions = $stmt->fetch()['pending_decisions'];
            
            error_log('Meeting API - Pending decisions count: ' . $pendingDecisions);
            
            $stats['pending_decisions'] = $pendingDecisions;
            
            $response['success'] = true;
            $response['data'] = $stats;
            
            error_log('Meeting API - Final stats response: ' . json_encode($response));
            break;
            
        case 'get_pending_decisions':
            // Bekleyen kararları getir
            $sql = "
                SELECT md.*, m.title as meeting_title, m.meeting_date, bu.name as byk_name
                FROM meeting_decisions md
                LEFT JOIN meetings m ON md.meeting_id = m.id
                LEFT JOIN byk_units bu ON m.byk_code = bu.code
                WHERE md.status IN ('pending', 'in_progress')
                ORDER BY md.deadline ASC, md.priority DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $decisions = $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $decisions;
            break;
            
        default:
            $response['message'] = 'Geçersiz işlem';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

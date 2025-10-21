<?php
require_once '../config.php';

// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS request için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Veritabanı bağlantısı
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add_meeting':
        addMeeting($pdo);
        break;
    case 'update_meeting':
        updateMeeting($pdo);
        break;
    case 'delete_meeting':
        deleteMeeting($pdo);
        break;
    case 'get_meeting':
        getMeeting($pdo);
        break;
    case 'get_meetings':
        getMeetings($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}

function addMeeting($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO meetings (title, byk_code, meeting_date, meeting_time, location, unit, status, agenda) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['title'],
            $data['byk'],
            $data['date'],
            $data['time'],
            $data['location'],
            $data['unit'],
            $data['status'] ?? 'planned',
            $data['agenda'] ?? ''
        ]);
        
        if ($result) {
            $meetingId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'message' => 'Toplantı başarıyla eklendi', 'id' => $meetingId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Toplantı eklenemedi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}

function updateMeeting($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "UPDATE meetings SET 
                title = ?, byk_code = ?, meeting_date = ?, meeting_time = ?, 
                location = ?, unit = ?, status = ?, agenda = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['title'],
            $data['byk'],
            $data['date'],
            $data['time'],
            $data['location'],
            $data['unit'],
            $data['status'],
            $data['agenda'] ?? '',
            $data['id']
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Toplantı başarıyla güncellendi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Toplantı güncellenemedi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}

function deleteMeeting($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "DELETE FROM meetings WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$data['id']]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Toplantı başarıyla silindi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Toplantı silinemedi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}

function getMeeting($pdo) {
    try {
        $id = $_GET['id'] ?? 0;
        
        $sql = "SELECT * FROM meetings WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $meeting = $stmt->fetch();
        
        if ($meeting) {
            echo json_encode(['success' => true, 'data' => $meeting]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Toplantı bulunamadı']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}

function getMeetings($pdo) {
    try {
        $sql = "
            SELECT m.*, 
                   COUNT(DISTINCT mp.id) as participants,
                   COUNT(DISTINCT ma.id) as agenda_count,
                   COUNT(DISTINCT md.id) as decisions_count
            FROM meetings m
            LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id
            LEFT JOIN meeting_agenda ma ON m.id = ma.meeting_id
            LEFT JOIN meeting_decisions md ON m.id = md.meeting_id
            GROUP BY m.id
            ORDER BY m.meeting_date DESC, m.meeting_time DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $meetings = $stmt->fetchAll();
        
        // Alan adlarını düzenle
        foreach ($meetings as &$meeting) {
            $meeting['byk'] = $meeting['byk_code'];
            $meeting['date'] = $meeting['meeting_date'];
            $meeting['time'] = $meeting['meeting_time'];
        }
        
        echo json_encode(['success' => true, 'data' => $meetings]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}
?>

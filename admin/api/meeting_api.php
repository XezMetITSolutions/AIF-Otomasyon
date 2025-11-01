<?php
require_once '../config.php';
require_once '../includes/email_helper.php';

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
    case 'send_invitations':
        sendInvitations($pdo);
        break;
    case 'get_participant_response':
        getParticipantResponse($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}

function addMeeting($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Toplantı bilgileri
        $sql = "INSERT INTO meetings (title, byk_code, meeting_date, meeting_time, end_time, location, chairman, secretary, status, meeting_type, agenda) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['title'],
            $data['byk'] ?? $data['byk_code'] ?? '',
            $data['date'] ?? $data['meeting_date'] ?? '',
            $data['time'] ?? $data['meeting_time'] ?? '',
            $data['end_time'] ?? null,
            $data['location'] ?? '',
            $data['chairman'] ?? '',
            $data['secretary'] ?? null,
            $data['status'] ?? 'planned',
            $data['meeting_type'] ?? 'regular',
            $data['agenda'] ?? ''
        ]);
        
        if ($result) {
            $meetingId = $pdo->lastInsertId();
            
            // Katılımcıları ekle
            $emailsSent = 0;
            if (!empty($data['participants']) && is_array($data['participants'])) {
                $emailsSent = addParticipantsAndSendInvitations($pdo, $meetingId, $data['participants'], $data);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Toplantı başarıyla eklendi', 
                'id' => $meetingId,
                'emails_sent' => $emailsSent
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Toplantı eklenemedi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}

/**
 * Katılımcıları ekle ve davetiyeler gönder
 */
function addParticipantsAndSendInvitations($pdo, $meetingId, $participants, $meetingData) {
    $emailsSent = 0;
    
    // Toplantı bilgilerini hazırla
    $meeting = [
        'id' => $meetingId,
        'title' => $meetingData['title'],
        'meeting_date' => $meetingData['date'] ?? $meetingData['meeting_date'] ?? '',
        'meeting_time' => $meetingData['time'] ?? $meetingData['meeting_time'] ?? '',
        'end_time' => $meetingData['end_time'] ?? null,
        'location' => $meetingData['location'] ?? '',
        'agenda' => $meetingData['agenda'] ?? ''
    ];
    
    $insertSql = "INSERT INTO meeting_participants 
                  (meeting_id, participant_name, participant_role, participant_email, user_id, response_token, response_status, attendance_status) 
                  VALUES (?, ?, ?, ?, ?, ?, 'pending', 'invited')";
    $insertStmt = $pdo->prepare($insertSql);
    
    foreach ($participants as $participant) {
        // Email adresini bul
        $email = $participant['email'] ?? $participant['participant_email'] ?? '';
        
        // Eğer user_id varsa, kullanıcı bilgilerini çek
        if (!empty($participant['user_id'])) {
            $userStmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $userStmt->execute([$participant['user_id']]);
            $user = $userStmt->fetch();
            if ($user) {
                $email = $email ?: $user['email'];
                if (empty($participant['participant_name'])) {
                    $participant['participant_name'] = $user['full_name'];
                }
            }
        }
        
        // Benzersiz token oluştur
        $token = bin2hex(random_bytes(32));
        
        // Katılımcıyı ekle
        $insertStmt->execute([
            $meetingId,
            $participant['participant_name'] ?? $participant['name'] ?? '',
            $participant['participant_role'] ?? $participant['role'] ?? 'member',
            $email,
            $participant['user_id'] ?? null,
            $token,
        ]);
        
        // Email gönder
        if (!empty($email)) {
            $participantData = [
                'participant_name' => $participant['participant_name'] ?? $participant['name'] ?? '',
                'participant_email' => $email,
                'email' => $email
            ];
            
            if (EmailHelper::sendMeetingInvitation($participantData, $meeting, $token)) {
                $emailsSent++;
            }
        }
    }
    
    return $emailsSent;
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
                   COUNT(DISTINCT CASE WHEN mp.response_status = 'accepted' THEN mp.id END) as accepted_count,
                   COUNT(DISTINCT CASE WHEN mp.response_status = 'declined' THEN mp.id END) as declined_count,
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

/**
 * Toplantıya katılımcı ekle ve davetiyeler gönder
 */
function sendInvitations($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $meetingId = $data['meeting_id'] ?? 0;
        
        if (empty($meetingId)) {
            echo json_encode(['success' => false, 'message' => 'Toplantı ID gerekli']);
            return;
        }
        
        // Toplantı bilgilerini çek
        $stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ?");
        $stmt->execute([$meetingId]);
        $meeting = $stmt->fetch();
        
        if (!$meeting) {
            echo json_encode(['success' => false, 'message' => 'Toplantı bulunamadı']);
            return;
        }
        
        // Katılımcıları ekle ve email gönder
        if (!empty($data['participants']) && is_array($data['participants'])) {
            $emailsSent = addParticipantsAndSendInvitations($pdo, $meetingId, $data['participants'], $meeting);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Davetiyeler gönderildi',
                'emails_sent' => $emailsSent
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Katılımcı listesi gerekli']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}

/**
 * Katılımcı yanıtını al
 */
function getParticipantResponse($pdo) {
    try {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            echo json_encode(['success' => false, 'message' => 'Token gerekli']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT mp.*, m.title, m.meeting_date, m.meeting_time, m.location
            FROM meeting_participants mp
            JOIN meetings m ON mp.meeting_id = m.id
            WHERE mp.response_token = ?
        ");
        $stmt->execute([$token]);
        $participant = $stmt->fetch();
        
        if (!$participant) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz token']);
            return;
        }
        
        echo json_encode(['success' => true, 'data' => $participant]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    }
}
?>

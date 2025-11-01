<?php
/**
 * Toplantı Katılım Yanıtı Sayfası
 * Email'deki linklerden gelen yanıtları işler
 */
require_once 'includes/database.php';
require_once 'includes/auth.php';

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? ''; // accept veya decline
$excuseReason = $_POST['excuse_reason'] ?? '';

if (empty($token)) {
    die('Geçersiz link!');
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Token ile katılımcıyı bul
    $stmt = $pdo->prepare("
        SELECT mp.*, m.title, m.meeting_date, m.meeting_time, m.location
        FROM meeting_participants mp
        JOIN meetings m ON mp.meeting_id = m.id
        WHERE mp.response_token = ?
    ");
    $stmt->execute([$token]);
    $participant = $stmt->fetch();
    
    if (!$participant) {
        die('Geçersiz token!');
    }
    
    $meeting = [
        'title' => $participant['title'],
        'meeting_date' => $participant['meeting_date'],
        'meeting_time' => $participant['meeting_time'],
        'location' => $participant['location']
    ];
    
    // Form gönderildi mi?
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $excuseReason = $_POST['excuse_reason'] ?? '';
        
        if (!empty($action)) {
            $responseStatus = ($action === 'accept') ? 'accepted' : 'declined';
            $attendanceStatus = ($action === 'accept') ? 'invited' : 'excused';
            
            // Katılımcıyı güncelle
            $updateSql = "
                UPDATE meeting_participants 
                SET response_status = ?,
                    response_date = NOW(),
                    attendance_status = ?,
                    excuse_reason = ?
                WHERE id = ?
            ";
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                $responseStatus,
                $attendanceStatus,
                $excuseReason,
                $participant['id']
            ]);
            
            // Durumu tekrar çek
            $stmt = $pdo->prepare("
                SELECT mp.*, m.title, m.meeting_date, m.meeting_time, m.location
                FROM meeting_participants mp
                JOIN meetings m ON mp.meeting_id = m.id
                WHERE mp.id = ?
            ");
            $stmt->execute([$participant['id']]);
            $participant = $stmt->fetch();
            
            $currentStatus = $responseStatus;
            $success = true;
            $message = ($action === 'accept') 
                ? 'Katılım durumunuz kaydedildi. Toplantıya katılacağınızı bildirdiniz.' 
                : 'Katılım durumunuz kaydedildi. Toplantıya katılamayacağınızı bildirdiniz.';
        }
    }
    
    // Mevcut durumu kontrol et
    $currentStatus = $participant['response_status'] ?? 'pending';
    
} catch (Exception $e) {
    $error = 'Bir hata oluştu: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toplantı Katılım Yanıtı - AIF Otomasyon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .response-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        .response-header {
            background: linear-gradient(135deg, #009872 0%, #007a5e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .response-body {
            padding: 30px;
        }
        .meeting-info {
            background: #f8f9fa;
            border-left: 4px solid #009872;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-row {
            margin: 10px 0;
            display: flex;
        }
        .info-label {
            font-weight: bold;
            min-width: 100px;
            color: #666;
        }
        .btn-group-custom {
            display: flex;
            gap: 15px;
            margin: 30px 0;
        }
        .btn-custom {
            flex: 1;
            padding: 15px;
            font-size: 16px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform 0.2s;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
        }
        .btn-accept {
            background: #28a745;
            color: white;
        }
        .btn-decline {
            background: #dc3545;
            color: white;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status-accepted {
            background: #d4edda;
            color: #155724;
        }
        .status-declined {
            background: #f8d7da;
            color: #721c24;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="response-card">
        <div class="response-header">
            <h2><i class="fas fa-calendar-check"></i> Toplantı Katılım Yanıtı</h2>
        </div>
        
        <div class="response-body">
            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
                
                <?php if ($currentStatus === 'accepted'): ?>
                    <div class="status-badge status-accepted">
                        <i class="fas fa-check"></i> Katılacağım
                    </div>
                <?php elseif ($currentStatus === 'declined'): ?>
                    <div class="status-badge status-declined">
                        <i class="fas fa-times"></i> Katılmayacağım
                    </div>
                <?php endif; ?>
            <?php elseif (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <?php if ($currentStatus !== 'pending'): ?>
                    <div class="alert alert-info">
                        <?php if ($currentStatus === 'accepted'): ?>
                            <i class="fas fa-info-circle"></i> Bu toplantıya <strong>katılacağınızı</strong> bildirdiniz.
                        <?php elseif ($currentStatus === 'declined'): ?>
                            <i class="fas fa-info-circle"></i> Bu toplantıya <strong>katılamayacağınızı</strong> bildirdiniz.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mb-4">
                    <p class="lead">Sayın <strong><?php echo htmlspecialchars($participant['participant_name']); ?></strong>,</p>
                    <p>Aşağıdaki toplantıya katılım durumunuzu bildirir misiniz?</p>
                </div>
                
                <div class="meeting-info">
                    <div class="info-row">
                        <span class="info-label">Toplantı:</span>
                        <span><?php echo htmlspecialchars($meeting['title']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tarih:</span>
                        <span><?php echo date('d.m.Y', strtotime($meeting['meeting_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Saat:</span>
                        <span><?php echo date('H:i', strtotime($meeting['meeting_time'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Yer:</span>
                        <span><?php echo htmlspecialchars($meeting['location']); ?></span>
                    </div>
                </div>
                
                <?php if ($currentStatus === 'pending'): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" id="formAction" value="">
                        
                        <div class="btn-group-custom">
                            <a href="#" class="btn-custom btn-accept" onclick="event.preventDefault(); document.getElementById('formAction').value='accept'; document.forms[0].submit();">
                                <i class="fas fa-check"></i>
                                <span>Katılacağım</span>
                            </a>
                            <a href="#" class="btn-custom btn-decline" onclick="event.preventDefault(); document.getElementById('formAction').value='decline'; showExcuseForm();">
                                <i class="fas fa-times"></i>
                                <span>Katılmayacağım</span>
                            </a>
                        </div>
                        
                        <div id="excuseForm" style="display: none;">
                            <div class="mb-3">
                                <label for="excuse_reason" class="form-label">Mazeret Nedeniniz (Opsiyonel):</label>
                                <textarea class="form-control" id="excuse_reason" name="excuse_reason" rows="3" placeholder="Katılamama nedeninizi belirtir misiniz?"></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-times"></i> Katılamayacağımı Onayla
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showExcuseForm() {
            document.getElementById('excuseForm').style.display = 'block';
            document.getElementById('excuse_reason').focus();
        }
    </script>
</body>
</html>


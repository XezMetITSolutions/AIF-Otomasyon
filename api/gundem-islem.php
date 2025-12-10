<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Middleware.php';
require_once __DIR__ . '/../classes/Database.php';

// Auth Check (Baskan or Admin)
Middleware::requireBaskan();

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $gundem_id = $input['gundem_id'] ?? null;
    $notlar = $input['notlar'] ?? null;
    $sorumlu_id = $input['sorumlu_id'] ?? null; // Can be null if removing assignment

    if (!$gundem_id) {
        throw new Exception('Gündem ID gereklidir');
    }

    try {
        // Attempt Update
        $db->query("
            UPDATE toplanti_gundem SET 
                notlar = ?,
                sorumlu_id = ?
            WHERE gundem_id = ?
        ", [$notlar, $sorumlu_id, $gundem_id]);

    } catch (PDOException $e) {
        // Self-Healing Logic for Missing Columns
        $msg = $e->getMessage();
        $fixed = false;

        if (strpos($msg, 'Unknown column') !== false) {
            if (strpos($msg, 'notlar') !== false) {
                $db->query("ALTER TABLE toplanti_gundem ADD COLUMN notlar TEXT NULL");
                $fixed = true;
            }
            if (strpos($msg, 'sorumlu_id') !== false) {
                // Check foreign key constraint? For now just add column.
                // FK constraints might fail if data is invalid, but initially empty column is fine.
                $db->query("ALTER TABLE toplanti_gundem ADD COLUMN sorumlu_id INT NULL");
                $db->query("ALTER TABLE toplanti_gundem ADD KEY (sorumlu_id)"); // Index
                $fixed = true;
            }
        }

        if ($fixed) {
            // Retry Update
            $db->query("
                UPDATE toplanti_gundem SET 
                    notlar = ?,
                    sorumlu_id = ?
                WHERE gundem_id = ?
            ", [$notlar, $sorumlu_id, $gundem_id]);
        } else {
            throw $e;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Gündem notu güncellendi']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<?php
// Veritabanı kurulum API
header('Content-Type: application/json');

// Gerçek veritabanı bilgileri
$db_config = [
    'host' => 'localhost',
    'name' => 'd0451622',
    'user' => 'd0451622',
    'pass' => '01528797Mb##'
];

function getDBConnection() {
    global $db_config;
    
    try {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Veritabanı bağlantı hatası: " . $e->getMessage());
    }
}

function logMessage($message) {
    error_log("[DB Setup] " . $message);
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'check_tables':
            // Mevcut tabloları kontrol et
            $pdo = getDBConnection();
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            logMessage("Mevcut tablolar kontrol edildi: " . implode(', ', $tables));
            
            echo json_encode([
                'success' => true,
                'message' => 'Tablo kontrolü tamamlandı',
                'tables' => $tables,
                'total_tables' => count($tables)
            ]);
            break;

        case 'test':
            // Bağlantı testi
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT 1");
            
            logMessage("Veritabanı bağlantısı başarılı");
            echo json_encode([
                'success' => true,
                'message' => 'Veritabanı bağlantısı başarılı',
                'database' => $db_config['name']
            ]);
            break;

        case 'create_tables':
            // Tabloları oluştur
            $pdo = getDBConnection();
            
            // Mevcut tabloları kontrol et
            $stmt = $pdo->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Sadece eksik tabloları oluştur
            $tablesToCreate = [];
            $requiredTables = ['users', 'expenses', 'expense_items', 'announcements', 'events', 'inventory', 'projects', 'byk_categories', 'byk_sub_units', 'modules', 'user_permissions', 'meetings', 'meeting_agenda', 'meeting_decisions', 'meeting_participants', 'meeting_notes'];
            
            foreach ($requiredTables as $table) {
                if (!in_array($table, $existingTables)) {
                    $tablesToCreate[] = $table;
                } else {
                    logMessage("Tablo $table zaten mevcut, atlanıyor");
                }
            }
            
            if (empty($tablesToCreate)) {
                logMessage("Tüm tablolar zaten mevcut");
                echo json_encode([
                    'success' => true,
                    'message' => 'Tüm tablolar zaten mevcut',
                    'tables' => $requiredTables,
                    'action' => 'no_action_needed'
                ]);
                break;
            }
            
            // Users tablosu
            if (in_array('users', $tablesToCreate)) {
                $usersTable = "
                    CREATE TABLE users (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        first_name VARCHAR(100) NOT NULL,
                        last_name VARCHAR(100) NOT NULL,
                        full_name VARCHAR(200) NOT NULL,
                        username VARCHAR(100) UNIQUE NOT NULL,
                        email VARCHAR(255) UNIQUE,
                        password_hash VARCHAR(255),
                        phone VARCHAR(20),
                        department VARCHAR(50),
                        unit VARCHAR(50),
                        byk VARCHAR(10),
                        byk_category_id INT,
                        sub_unit_id INT,
                        role ENUM('superadmin', 'manager', 'member') DEFAULT 'member',
                        status ENUM('active', 'inactive') DEFAULT 'active',
                        is_byk_member TINYINT(1) DEFAULT 0,
                        must_change_password TINYINT(1) DEFAULT 0,
                        last_login TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_email (email),
                        INDEX idx_username (username),
                        INDEX idx_department (department),
                        INDEX idx_role (role),
                        INDEX idx_status (status),
                        FOREIGN KEY (byk_category_id) REFERENCES byk_categories(id),
                        FOREIGN KEY (sub_unit_id) REFERENCES byk_sub_units(id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($usersTable);
                logMessage("Users tablosu oluşturuldu");
            } else {
                // Mevcut tabloyu güncelle
                try {
                    // first_name alanını ekle
                    $pdo->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(100) AFTER id");
                    logMessage("first_name alanı eklendi");
                } catch (Exception $e) {
                    logMessage("first_name alanı zaten mevcut veya eklenemedi: " . $e->getMessage());
                }
                
                try {
                    // last_name alanını ekle
                    $pdo->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(100) AFTER first_name");
                    logMessage("last_name alanı eklendi");
                } catch (Exception $e) {
                    logMessage("last_name alanı zaten mevcut veya eklenemedi: " . $e->getMessage());
                }
                
                // Mevcut kullanıcıların first_name ve last_name alanlarını doldur
                try {
                    $pdo->exec("UPDATE users SET first_name = SUBSTRING_INDEX(full_name, ' ', 1), last_name = SUBSTRING_INDEX(full_name, ' ', -1) WHERE first_name IS NULL OR last_name IS NULL");
                    logMessage("Mevcut kullanıcıların ad/soyad bilgileri güncellendi");
                } catch (Exception $e) {
                    logMessage("Kullanıcı ad/soyad güncelleme hatası: " . $e->getMessage());
                }
            }
            
            // Expenses tablosu
            if (in_array('expenses', $tablesToCreate)) {
                $expensesTable = "
                    CREATE TABLE expenses (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        user_id INT NOT NULL,
                        isim VARCHAR(100) NOT NULL,
                        soyisim VARCHAR(100) NOT NULL,
                        iban VARCHAR(34) NOT NULL,
                        total DECIMAL(10,2) NOT NULL DEFAULT 0,
                        status ENUM('pending', 'paid', 'rejected') DEFAULT 'pending',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_status (status),
                        INDEX idx_created_at (created_at),
                        FOREIGN KEY (user_id) REFERENCES users(id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($expensesTable);
                logMessage("Expenses tablosu oluşturuldu");
            }
            
            // Expense_items tablosu
            if (in_array('expense_items', $tablesToCreate)) {
                $expenseItemsTable = "
                    CREATE TABLE expense_items (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        expense_id INT NOT NULL,
                        tarih DATE NOT NULL,
                        region VARCHAR(10),
                        birim VARCHAR(50),
                        birim_label VARCHAR(100),
                        gider_turu VARCHAR(50),
                        gider_turu_label VARCHAR(100),
                        tutar DECIMAL(10,2) NOT NULL DEFAULT 0,
                        aciklama TEXT,
                        attachments JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
                        INDEX idx_expense_id (expense_id),
                        INDEX idx_tarih (tarih),
                        INDEX idx_birim (birim)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($expenseItemsTable);
                logMessage("Expense_items tablosu oluşturuldu");
            }
            
            // Announcements tablosu
            if (in_array('announcements', $tablesToCreate)) {
                $announcementsTable = "
                    CREATE TABLE announcements (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        title VARCHAR(255) NOT NULL,
                        content TEXT NOT NULL,
                        author_id INT NOT NULL,
                        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                        target_audience ENUM('all', 'manager', 'member') DEFAULT 'all',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        published_at TIMESTAMP NULL,
                        INDEX idx_status (status),
                        INDEX idx_priority (priority),
                        INDEX idx_target_audience (target_audience),
                        INDEX idx_created_at (created_at),
                        FOREIGN KEY (author_id) REFERENCES users(id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($announcementsTable);
                logMessage("Announcements tablosu oluşturuldu");
            }
            
            // Events tablosu
            if (in_array('events', $tablesToCreate)) {
                $eventsTable = "
                    CREATE TABLE events (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        title VARCHAR(255) NOT NULL,
                        description TEXT,
                        start_date DATETIME NOT NULL,
                        end_date DATETIME NOT NULL,
                        location VARCHAR(255),
                        organizer_id INT NOT NULL,
                        byk_category_id INT,
                        status ENUM('draft', 'published', 'cancelled') DEFAULT 'draft',
                        max_participants INT DEFAULT NULL,
                        registration_required TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_start_date (start_date),
                        INDEX idx_status (status),
                        INDEX idx_byk_category (byk_category_id),
                        FOREIGN KEY (organizer_id) REFERENCES users(id),
                        FOREIGN KEY (byk_category_id) REFERENCES byk_categories(id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($eventsTable);
                logMessage("Events tablosu oluşturuldu");
            }
            
            // Inventory tablosu
            if (in_array('inventory', $tablesToCreate)) {
                $inventoryTable = "
                    CREATE TABLE inventory (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        category VARCHAR(100),
                        quantity INT NOT NULL DEFAULT 0,
                        unit_price DECIMAL(10,2) DEFAULT 0,
                        total_value DECIMAL(10,2) DEFAULT 0,
                        location VARCHAR(255),
                        status ENUM('available', 'in_use', 'maintenance', 'disposed') DEFAULT 'available',
                        byk_category_id INT,
                        added_by INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_category (category),
                        INDEX idx_status (status),
                        INDEX idx_byk_category (byk_category_id),
                        FOREIGN KEY (byk_category_id) REFERENCES byk_categories(id),
                        FOREIGN KEY (added_by) REFERENCES users(id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($inventoryTable);
                logMessage("Inventory tablosu oluşturuldu");
            }
            
            // Projects tablosu
            if (in_array('projects', $tablesToCreate)) {
                $projectsTable = "
                    CREATE TABLE projects (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        status ENUM('planning', 'active', 'completed', 'cancelled') DEFAULT 'planning',
                        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                        start_date DATE,
                        end_date DATE,
                        project_manager_id INT NOT NULL,
                        byk_category_id INT,
                        budget DECIMAL(12,2) DEFAULT 0,
                        progress INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_status (status),
                        INDEX idx_priority (priority),
                        INDEX idx_byk_category (byk_category_id),
                        FOREIGN KEY (project_manager_id) REFERENCES users(id),
                        FOREIGN KEY (byk_category_id) REFERENCES byk_categories(id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($projectsTable);
                logMessage("Projects tablosu oluşturuldu");
            }
            
            // Meetings tablosu
            if (in_array('meetings', $tablesToCreate)) {
                $meetingsTable = "
                    CREATE TABLE meetings (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        title VARCHAR(255) NOT NULL,
                        byk_code VARCHAR(10) NOT NULL,
                        meeting_date DATE NOT NULL,
                        meeting_time TIME NOT NULL,
                        location TEXT NOT NULL,
                        unit VARCHAR(100) NOT NULL,
                        status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
                        agenda TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($meetingsTable);
                logMessage("Meetings tablosu oluşturuldu");
            }
            
            // Meeting Agenda tablosu
            if (in_array('meeting_agenda', $tablesToCreate)) {
                $meetingAgendaTable = "
                    CREATE TABLE meeting_agenda (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        meeting_id INT NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        responsible VARCHAR(100),
                        notes TEXT,
                        order_index INT DEFAULT 0,
                        is_completed BOOLEAN DEFAULT FALSE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($meetingAgendaTable);
                logMessage("Meeting Agenda tablosu oluşturuldu");
            }
            
            // Meeting Decisions tablosu
            if (in_array('meeting_decisions', $tablesToCreate)) {
                $meetingDecisionsTable = "
                    CREATE TABLE meeting_decisions (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        meeting_id INT NOT NULL,
                        decision_text TEXT NOT NULL,
                        responsible VARCHAR(100),
                        deadline DATE,
                        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($meetingDecisionsTable);
                logMessage("Meeting Decisions tablosu oluşturuldu");
            }
            
            // Meeting Participants tablosu
            if (in_array('meeting_participants', $tablesToCreate)) {
                $meetingParticipantsTable = "
                    CREATE TABLE meeting_participants (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        meeting_id INT NOT NULL,
                        participant_name VARCHAR(100) NOT NULL,
                        role VARCHAR(50),
                        status ENUM('present', 'absent', 'excused') DEFAULT 'present',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($meetingParticipantsTable);
                logMessage("Meeting Participants tablosu oluşturuldu");
            }
            
            // Meeting Notes tablosu
            if (in_array('meeting_notes', $tablesToCreate)) {
                $meetingNotesTable = "
                    CREATE TABLE meeting_notes (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        meeting_id INT NOT NULL,
                        notes TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($meetingNotesTable);
                logMessage("Meeting Notes tablosu oluşturuldu");
            }
            
            // BYK Categories tablosu
            if (in_array('byk_categories', $tablesToCreate)) {
                $bykCategoriesTable = "
                    CREATE TABLE byk_categories (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        code VARCHAR(10) UNIQUE NOT NULL,
                        name VARCHAR(100) NOT NULL,
                        description TEXT,
                        color VARCHAR(7) DEFAULT '#6c757d',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_code (code)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($bykCategoriesTable);
                logMessage("BYK Categories tablosu oluşturuldu");
            }
            
            // BYK Sub Units tablosu
            if (in_array('byk_sub_units', $tablesToCreate)) {
                $bykSubUnitsTable = "
                    CREATE TABLE byk_sub_units (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        byk_category_id INT NOT NULL,
                        name VARCHAR(100) NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_byk_category (byk_category_id),
                        FOREIGN KEY (byk_category_id) REFERENCES byk_categories(id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($bykSubUnitsTable);
                logMessage("BYK Sub Units tablosu oluşturuldu");
            }
            
            // Positions tablosu
            if (in_array('positions', $tablesToCreate)) {
                $positionsTable = "
                    CREATE TABLE positions (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(100) UNIQUE NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_name (name)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($positionsTable);
                logMessage("Positions tablosu oluşturuldu");
            }
            
            // Modules tablosu (Yetki Sistemi)
            if (in_array('modules', $tablesToCreate)) {
                $modulesTable = "
                    CREATE TABLE modules (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(50) UNIQUE NOT NULL,
                        display_name VARCHAR(100) NOT NULL,
                        icon VARCHAR(50) NOT NULL,
                        description TEXT,
                        is_active BOOLEAN DEFAULT TRUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_name (name),
                        INDEX idx_is_active (is_active)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($modulesTable);
                logMessage("Modules tablosu oluşturuldu");
            }
            
            // User Permissions tablosu (Yetki Sistemi)
            if (in_array('user_permissions', $tablesToCreate)) {
                $userPermissionsTable = "
                    CREATE TABLE user_permissions (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        user_id INT NOT NULL,
                        module_id INT NOT NULL,
                        can_read BOOLEAN DEFAULT FALSE,
                        can_write BOOLEAN DEFAULT FALSE,
                        can_admin BOOLEAN DEFAULT FALSE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_user_module (user_id, module_id),
                        INDEX idx_user_id (user_id),
                        INDEX idx_module_id (module_id),
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ";
                $pdo->exec($userPermissionsTable);
                logMessage("User Permissions tablosu oluşturuldu");
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Eksik tablolar başarıyla oluşturuldu',
                'created_tables' => $tablesToCreate,
                'existing_tables' => $existingTables
            ]);
            break;

        case 'insert_sample':
            // Örnek veri ekle
            $pdo = getDBConnection();
            
            // Önce users tablosunun yapısını kontrol et ve eksik kolonları ekle
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredColumns = [
                'username' => 'VARCHAR(100) UNIQUE NOT NULL',
                'password_hash' => 'VARCHAR(255)',
                'byk_category_id' => 'INT',
                'sub_unit_id' => 'INT',
                'role' => "ENUM('superadmin', 'manager', 'member') DEFAULT 'member'",
                'status' => "ENUM('active', 'inactive') DEFAULT 'active'",
                'is_byk_member' => 'TINYINT(1) DEFAULT 0',
                'must_change_password' => 'TINYINT(1) DEFAULT 0',
                'last_login' => 'TIMESTAMP NULL',
                'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            ];
            
            $missingColumns = [];
            
            foreach ($requiredColumns as $column => $definition) {
                if (!in_array($column, $columns)) {
                    $missingColumns[$column] = $definition;
                }
            }
            
            // Eksik kolonları ekle
            if (!empty($missingColumns)) {
                foreach ($missingColumns as $column => $definition) {
                    try {
                        $pdo->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
                        logMessage("Users tablosuna $column kolonu eklendi");
                    } catch (Exception $e) {
                        logMessage("Kolon $column eklenemedi: " . $e->getMessage());
                    }
                }
            }
            
            // Örnek BYK kategorileri ekle
            $bykCategories = [
                ['AT', 'Ana Teşkilat', '#28a745'],
                ['KT', 'Kadınlar Teşkilatı', '#dc3545'],
                ['KGT', 'Kadınlar Gençlik Teşkilatı', '#ffc107'],
                ['GT', 'Gençlik Teşkilatı', '#17a2b8']
            ];
            
            foreach ($bykCategories as $category) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO byk_categories (code, name, color) VALUES (?, ?, ?)");
                    $stmt->execute($category);
                    if ($stmt->rowCount() > 0) {
                        logMessage("BYK kategorisi eklendi: {$category[0]} - {$category[1]}");
                    } else {
                        logMessage("BYK kategorisi zaten mevcut: {$category[0]} - {$category[1]}");
                    }
                } catch (Exception $e) {
                    logMessage("BYK kategorisi ekleme hatası: {$category[0]} - " . $e->getMessage());
                }
            }
            
            // Örnek BYK alt birimleri ekle
            $bykSubUnits = [
                [1, 'Merkez'],
                [1, 'Şube'],
                [2, 'Merkez'],
                [2, 'Şube'],
                [3, 'Merkez'],
                [3, 'Şube'],
                [4, 'Merkez'],
                [4, 'Şube']
            ];
            
            foreach ($bykSubUnits as $unit) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO byk_sub_units (byk_category_id, name) VALUES (?, ?)");
                    $stmt->execute($unit);
                    if ($stmt->rowCount() > 0) {
                        logMessage("BYK alt birimi eklendi: {$unit[1]}");
                    } else {
                        logMessage("BYK alt birimi zaten mevcut: {$unit[1]}");
                    }
                } catch (Exception $e) {
                    logMessage("BYK alt birimi ekleme hatası: {$unit[1]} - " . $e->getMessage());
                }
            }
            
            // Örnek pozisyonlar ekle
            $positions = [
                'Bölge Başkanı',
                'Teşkilatlanma Başkanı',
                'Eğitim Başkanı',
                'İrşad Başkanı',
                'Kurumsal İletişim Başkanı',
                'İnsani Yardım ve Sosyal Hizmetler Başkanı',
                'Bölge Kadınlar Teşkilatı Başkanı',
                'Bölge Gençlik Teşkilatı Başkanı',
                'Bölge Kadınlar Gençlik Teşkilatı Başkanı',
                'Teftiş Başkanı',
                'Muhasebe Başkanı',
                'Sekreter',
                'İdari İşler Başkanı',
                'Hac-Umre Sey. İşleri Başkanı',
                'Emlak Başkanı',
                'Cenaze Hizmetleri Başkanı',
                'Genel Merkez Üyelik Başkanı',
                'Tanıtım Kültür Hizmetleri Başkanı',
                'Raggal Sorumlusu'
            ];
            
            foreach ($positions as $position) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO positions (name) VALUES (?)");
                    $stmt->execute([$position]);
                    if ($stmt->rowCount() > 0) {
                        logMessage("Pozisyon eklendi: $position");
                    } else {
                        logMessage("Pozisyon zaten mevcut: $position");
                    }
                } catch (Exception $e) {
                    logMessage("Pozisyon ekleme hatası: $position - " . $e->getMessage());
                }
            }
            
            // Örnek kullanıcı ekle
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, department, unit, byk, role, status, is_byk_member) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $passwordHash = password_hash('AIF571#', PASSWORD_DEFAULT);
            $stmt->execute(['Semra Yıldız', 'semra.yildiz', 'semra@example.com', $passwordHash, 'İrşad', 'İrşad', 'KT', 'member', 'active', 1]);
            $userId = $pdo->lastInsertId();
            logMessage("Örnek kullanıcı eklendi: ID $userId");
            
            // Örnek gider
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, isim, soyisim, iban, total, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, 'Semra', 'Yıldız', 'AT862060200001095074', 96.98, 'pending']);
            $expenseId = $pdo->lastInsertId();
            logMessage("Örnek gider eklendi: ID $expenseId");
            
            // Örnek gider kalemi
            $stmt = $pdo->prepare("INSERT INTO expense_items (expense_id, tarih, region, birim, birim_label, gider_turu, gider_turu_label, tutar, aciklama) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$expenseId, '2025-10-15', 'KT', 'irsad', 'İrşad', 'diger', 'Diğer', 96.98, 'Çeşitli giderler']);
            logMessage("Örnek gider kalemi eklendi");
            
            // Modülleri ekle (Yetki Sistemi)
            $modules = [
                ['dashboard', 'Dashboard', 'fas fa-tachometer-alt', 'Ana kontrol paneli'],
                ['users', 'Kullanıcılar', 'fas fa-users', 'Kullanıcı yönetimi'],
                ['permissions', 'Yetki Yönetimi', 'fas fa-shield-alt', 'Yetki ve izin yönetimi'],
                ['announcements', 'Duyurular', 'fas fa-bullhorn', 'Duyuru yönetimi'],
                ['events', 'Etkinlikler', 'fas fa-calendar-alt', 'Etkinlik yönetimi'],
                ['calendar', 'Takvim', 'fas fa-calendar', 'Takvim görüntüleme'],
                ['inventory', 'Demirbaş Listesi', 'fas fa-boxes', 'Demirbaş yönetimi'],
                ['meeting_reports', 'Toplantı Raporları', 'fas fa-file-alt', 'Toplantı raporları'],
                ['reservations', 'Rezervasyon', 'fas fa-bookmark', 'Rezervasyon yönetimi'],
                ['expenses', 'Para İadesi', 'fas fa-undo', 'İade talepleri'],
                ['projects', 'Proje Takibi', 'fas fa-project-diagram', 'Proje yönetimi'],
                ['reports', 'Raporlar', 'fas fa-chart-bar', 'Raporlar ve analizler'],
                ['settings', 'Ayarlar', 'fas fa-cog', 'Sistem ayarları']
            ];
            
            foreach ($modules as $module) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO modules (name, display_name, icon, description) VALUES (?, ?, ?, ?)");
                    $stmt->execute($module);
                    if ($stmt->rowCount() > 0) {
                        logMessage("Modül eklendi: {$module[1]}");
                    } else {
                        logMessage("Modül zaten mevcut: {$module[1]}");
                    }
                } catch (Exception $e) {
                    logMessage("Modül ekleme hatası: {$module[1]} - " . $e->getMessage());
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Örnek veri başarıyla eklendi',
                'data' => [
                    'user_id' => $userId,
                    'expense_id' => $expenseId,
                    'added_columns' => $missingColumns
                ]
            ]);
            break;

        case 'complete':
            // Kurulum tamamlama
            $pdo = getDBConnection();
            
            // Tablo sayılarını kontrol et
            $tables = ['users', 'expenses', 'expense_items'];
            $counts = [];
            
            foreach ($tables as $table) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $counts[$table] = $stmt->fetch()['count'];
            }
            
            logMessage("Kurulum tamamlandı. Tablo sayıları: " . json_encode($counts));
            
            echo json_encode([
                'success' => true,
                'message' => 'Kurulum başarıyla tamamlandı',
                'table_counts' => $counts,
                'admin_url' => 'expenses.php'
            ]);
            break;

        default:
            throw new Exception('Geçersiz işlem');
    }

} catch (Exception $e) {
    logMessage("Hata: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
https://aifcrm.metechnik.at/admin/user_permissions.php
-- Toplantı Raporları Modülü - Veritabanı Tabloları
-- AIF Otomasyon Sistemi

-- Toplantılar tablosu
CREATE TABLE IF NOT EXISTS meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    byk_category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    meeting_date DATETIME NOT NULL,
    location VARCHAR(255),
    platform VARCHAR(100), -- Zoom, Teams, fiziksel yer
    chairman_id INT, -- Toplantı başkanı
    secretary_id INT, -- Sekreter
    status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (byk_category_id) REFERENCES byk_categories(id),
    FOREIGN KEY (chairman_id) REFERENCES users(id),
    FOREIGN KEY (secretary_id) REFERENCES users(id)
);

-- Toplantı katılımcıları tablosu
CREATE TABLE IF NOT EXISTS meeting_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    user_id INT NOT NULL,
    attendance_status ENUM('invited', 'attended', 'absent', 'excused') DEFAULT 'invited',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Gündem maddeleri tablosu
CREATE TABLE IF NOT EXISTS meeting_agenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    order_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    responsible_user_id INT,
    description TEXT,
    status ENUM('pending', 'discussed', 'decided', 'postponed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (responsible_user_id) REFERENCES users(id)
);

-- Toplantı kararları tablosu
CREATE TABLE IF NOT EXISTS meeting_decisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    agenda_item_id INT,
    decision_number VARCHAR(50) NOT NULL, -- 2026-AT-01 formatında
    decision_text TEXT NOT NULL,
    responsible_user_id INT NOT NULL,
    deadline_date DATE,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    completion_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (agenda_item_id) REFERENCES meeting_agenda(id),
    FOREIGN KEY (responsible_user_id) REFERENCES users(id)
);

-- Toplantı dosyaları tablosu
CREATE TABLE IF NOT EXISTS meeting_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Toplantı bildirimleri tablosu
CREATE TABLE IF NOT EXISTS meeting_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    user_id INT NOT NULL,
    notification_type ENUM('meeting_reminder', 'decision_deadline', 'task_completion') NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Toplantı raporları tablosu
CREATE TABLE IF NOT EXISTS meeting_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    report_type ENUM('summary', 'decisions', 'attendance', 'full') NOT NULL,
    report_content TEXT NOT NULL,
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Toplantı takip sistemi tablosu
CREATE TABLE IF NOT EXISTS meeting_follow_ups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    decision_id INT NOT NULL,
    follow_up_date DATE NOT NULL,
    status ENUM('pending', 'completed', 'overdue') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (decision_id) REFERENCES meeting_decisions(id) ON DELETE CASCADE
);

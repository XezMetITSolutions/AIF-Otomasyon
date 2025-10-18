-- AIF-Admin kullanıcısını ekle
-- Bu dosya mevcut veritabanına yeni superadmin kullanıcısı ekler

-- Yeni superadmin kullanıcısını ekle
INSERT INTO users (username, email, password_hash, full_name, role, status, created_at) VALUES
('AIF-Admin', 'aif-admin@aif.com', '$2y$10$Plbpl8HWBiilSTVx7tEPauzjqte2rCbN.JOYWJWXSOfyyTXfkEPyS', 'AIF Yöneticisi', 'superadmin', 'active', NOW());

-- Yeni kullanıcının ID'sini al ve tüm modüllere tam yetki ver
INSERT INTO user_permissions (user_id, module_id, can_read, can_write, can_admin)
SELECT 
    (SELECT id FROM users WHERE username = 'AIF-Admin' LIMIT 1),
    id, 
    TRUE, 
    TRUE, 
    TRUE 
FROM modules;

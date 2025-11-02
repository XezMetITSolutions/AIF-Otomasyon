-- ========================================
-- Eski Tabloları Temizleme Scripti
-- Migration sonrası kullanılmayan tabloları siler
-- ========================================
-- 
-- ⚠️ ÖNEMLİ: Bu scripti çalıştırmadan önce:
-- 1. Verilerin başarıyla migrate edildiğinden emin olun
-- 2. Yedek alın
-- 3. Test ortamında deneyin
-- ========================================

-- Foreign Key kontrollerini geçici olarak devre dışı bırak
SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- 1. ANA TABLOLAR (Veriler migrate edildi)
-- ========================================

-- Users → Kullanicilar (eşleşiyor)
DROP TABLE IF EXISTS `users`;

-- Events → Etkinlikler (eşleşiyor)
DROP TABLE IF EXISTS `events`;

-- Announcements → Duyurular (eşleşiyor)
DROP TABLE IF EXISTS `announcements`;

-- Meetings → Toplantilar (eşleşiyor)
DROP TABLE IF EXISTS `meetings`;

-- Expenses → Harcama_talepleri (eşleşiyor)
DROP TABLE IF EXISTS `expenses`;

-- Inventory → Demirbaslar (eşleşiyor)
DROP TABLE IF EXISTS `inventory`;

-- Projects → Projeler (eşleşiyor)
DROP TABLE IF EXISTS `projects`;

-- ========================================
-- 2. İLİŞKİLİ TABLOLAR (Eski tablolara bağlı)
-- ========================================

-- Expense Items → Expenses'e bağlı
DROP TABLE IF EXISTS `expense_items`;

-- Meeting ile ilgili tablolar → Meetings'e bağlı
DROP TABLE IF EXISTS `meeting_agenda`;
DROP TABLE IF EXISTS `meeting_decisions`;
DROP TABLE IF EXISTS `meeting_files`;
DROP TABLE IF EXISTS `meeting_follow_ups`;
DROP TABLE IF EXISTS `meeting_notes`;
DROP TABLE IF EXISTS `meeting_notifications`;
DROP TABLE IF EXISTS `meeting_participants`;
DROP TABLE IF EXISTS `meeting_reports`;

-- User ile ilgili tablolar → Users'a bağlı
DROP TABLE IF EXISTS `user_permissions`;
DROP TABLE IF EXISTS `user_sessions`;

-- ========================================
-- 3. YARDIMCI TABLOLAR (Başka yerlerde kullanılıyor mu kontrol edin)
-- ========================================

-- BYK Kategorileri - YENİ BYK tablosuna migrate edildi, AMA KONTROL EDİN!
-- Eğer byk_categories tablosu başka yerlerde kullanılıyorsa silmeyin
-- DROP TABLE IF EXISTS `byk_categories`;

-- BYK Sub Units - YENİ alt_birimler tablosuna migrate edildi, AMA KONTROL EDİN!
-- DROP TABLE IF EXISTS `byk_sub_units`;

-- BYK Units - YENİ BYK tablosuna migrate edildi, AMA KONTROL EDİN!
-- DROP TABLE IF EXISTS `byk_units`;

-- Event Types - Eğer kullanılmıyorsa silinebilir
-- DROP TABLE IF EXISTS `event_types`;

-- Expense Types - Eğer kullanılmıyorsa silinebilir
-- DROP TABLE IF EXISTS `expense_types`;

-- Announcement Types - Eğer kullanılmıyorsa silinebilir
-- DROP TABLE IF EXISTS `announcement_types`;

-- Calendar Events - Takvim için kullanılıyor mu kontrol edin!
-- DROP TABLE IF EXISTS `calendar_events`;

-- Modules - Sistem modülleri, gerekli olabilir
-- DROP TABLE IF EXISTS `modules`;

-- Positions - Pozisyonlar, gerekli olabilir
-- DROP TABLE IF EXISTS `positions`;

-- Reports - Raporlar, gerekli olabilir
-- DROP TABLE IF EXISTS `reports`;

-- Reservations - Rezervasyonlar, gerekli olabilir
-- DROP TABLE IF EXISTS `reservations`;

-- Sub Units - Alt birimler, kontrol edin!
-- DROP TABLE IF EXISTS `sub_units`;

-- System Settings - Sistem ayarları, GEREKLİ!
-- DROP TABLE IF EXISTS `system_settings`; -- SİLMEYİN!

-- Foreign Key kontrollerini tekrar aktif et
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- SONUÇ: Silinen tablolar
-- ========================================
-- ✅ Silinecek tablolar (güvenli):
--    - users
--    - events
--    - announcements
--    - meetings
--    - expenses
--    - inventory
--    - projects
--    - expense_items
--    - meeting_agenda
--    - meeting_decisions
--    - meeting_files
--    - meeting_follow_ups
--    - meeting_notes
--    - meeting_notifications
--    - meeting_participants
--    - meeting_reports
--    - user_permissions
--    - user_sessions
--
-- ⚠️ Kontrol edilmesi gerekenler:
--    - byk_categories (başka yerde kullanılıyor mu?)
--    - byk_sub_units (başka yerde kullanılıyor mu?)
--    - byk_units (başka yerde kullanılıyor mu?)
--    - calendar_events (takvim için kullanılıyor mu?)
--    - event_types (gerekli mi?)
--    - expense_types (gerekli mi?)
--    - announcement_types (gerekli mi?)
--
-- ❌ SİLİNMEMELİ:
--    - system_settings (sistem ayarları)
--    - modules (modül yönetimi)
--    - positions (pozisyonlar)
--    - reports (raporlar)
--    - reservations (rezervasyonlar)
-- ========================================


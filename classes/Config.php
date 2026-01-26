<?php
/**
 * Sistem Ayarları Yardımcı Sınıfı
 */
class Config
{
    private static $settings = null;

    private static function load()
    {
        if (self::$settings !== null)
            return;

        self::$settings = [];
        try {
            $db = Database::getInstance();
            $rows = $db->fetchAll("SELECT ayar_key, ayar_value FROM sistem_ayarlari");
            foreach ($rows as $row) {
                self::$settings[$row['ayar_key']] = $row['ayar_value'];
            }
        } catch (Exception $e) {
            // Tablo yoksa veya hata oluşursa boş dizi kalır
        }
    }

    public static function get($key, $default = null)
    {
        self::load();
        return self::$settings[$key] ?? $default;
    }
}

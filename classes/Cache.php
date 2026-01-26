<?php
/**
 * Basit dosya tabanli cache sinifi
 * Dashboard gibi tekrar eden sorgulari hizlandirmak icin kullanilir
 */

class Cache {
    private static $cachePath;

    private static function initPath() {
        if (!self::$cachePath) {
            self::$cachePath = realpath(__DIR__ . '/../storage/cache');

            if (!self::$cachePath) {
                // Klasor yoksa olusturmayı dene
                $path = __DIR__ . '/../storage/cache';
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                self::$cachePath = realpath($path);
            }
        }
    }

    private static function filePath($key) {
        self::initPath();
        $safeKey = preg_replace('/[^A-Za-z0-9_\-]/', '_', $key);
        return self::$cachePath . DIRECTORY_SEPARATOR . sha1($safeKey) . '.cache.php';
    }

    public static function remember($key, $ttl, callable $callback) {
        $file = self::filePath($key);

        if (file_exists($file)) {
            $data = include $file;
            if (is_array($data) && isset($data['expires_at']) && $data['expires_at'] >= time()) {
                return $data['value'];
            }
        }

        $value = $callback();
        self::put($key, $value, $ttl);
        return $value;
    }

    public static function put($key, $value, $ttl) {
        $file = self::filePath($key);
        $payload = [
            'expires_at' => time() + (int) $ttl,
            'value' => $value
        ];

        $export = var_export($payload, true);
        file_put_contents($file, "<?php\nreturn {$export};\n");
    }

    public static function forget($key) {
        $file = self::filePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function clear() {
        self::initPath();
        foreach (glob(self::$cachePath . DIRECTORY_SEPARATOR . '*.cache.php') as $file) {
            unlink($file);
        }
    }
}






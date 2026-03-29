<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

$voters = [
    'Rümeysa Demirel',
    'İclal Ulusoy',
    'Sena Akbulut',
    'Simay Akbulut',
    'Züleyha Çimen',
    'Zeynep Sera Çimen',
    'Beyza Altınkaynak',
    'Merve Cihan',
    'Asude Ahsen Turgut',
    'Duygu Bulut',
    'Melissa Koçer',
    'Sever Çimen'
];

$c1 = 'Muhammed Öztürk';
$c2 = 'Murat Zengin';
$c3 = 'Ömer Mermertaş';
$c4 = '';
$c5 = '';
$sube = 'AIF Innsbruck';
$notlar = 'Kızlar beraber oy verdi';

foreach ($voters as $voter) {
    try {
        $existing = $db->fetch("SELECT id FROM istisare_oylama WHERE voter_id = ?", [$voter]);
        if ($existing) {
            $db->query("
                UPDATE istisare_oylama 
                SET sube_ismi=?, secilen_1=?, secilen_2=?, secilen_3=?, secilen_4=?, secilen_5=?, notlar=?, tarih=CURRENT_TIMESTAMP 
                WHERE id=?
            ", [$sube, $c1, $c2, $c3, $c4, $c5, $notlar, $existing['id']]);
            echo "Guncellendi: $voter<br>";
        } else {
            $db->query("
                INSERT INTO istisare_oylama (voter_id, sube_ismi, secilen_1, secilen_2, secilen_3, secilen_4, secilen_5, notlar)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [$voter, $sube, $c1, $c2, $c3, $c4, $c5, $notlar]);
            echo "Eklendi: $voter<br>";
        }
    } catch (Exception $e) {
        echo "Hata ($voter): " . $e->getMessage() . "<br>";
    }
}

echo "Bitti!<br>";

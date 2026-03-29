<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

$data = [
    [
        'voter' => 'Muhammed Öztürk',
        'c1' => 'Harun Yiğit',
        'c2' => 'Atilla Coşkun',
        'c3' => 'Mücahit Şirin',
        'c4' => 'Murat Zengin',
        'c5' => '',
        'note' => 'Eğitim iyi değil Zirlde kalıyorum Vomp\'a götürüyorum.'
    ],
    [
        'voter' => 'Murat Zengin',
        'c1' => 'Harun Yiğit',
        'c2' => 'Mücahit Şirin',
        'c3' => 'Atilla Coşkun',
        'c4' => 'Muhammed Öztürk',
        'c5' => '',
        'note' => 'Murat Zengin başkanlık istemiyor, şu an UKBA sorumlusu, ailevi sorunlardan dolayı başkanlığın altından kalkabileceğini düşünmüyor.'
    ],
    [
        'voter' => 'Nail Koçak',
        'c1' => 'Harun Yiğit',
        'c2' => 'Mücahit Şirin',
        'c3' => 'Atilla Coşkun',
        'c4' => 'Tekin Açıkel',
        'c5' => '',
        'note' => ''
    ],
    [
        'voter' => 'Mustafa Karayun',
        'c1' => 'Atilla Coşkun',
        'c2' => 'Murat Zengin',
        'c3' => 'Harun Yiğit',
        'c4' => '',
        'c5' => '',
        'note' => ''
    ],
    [
        'voter' => 'Mehmet Ali Demirel',
        'c1' => 'Harun Yiğit',
        'c2' => 'Atilla Coşkun',
        'c3' => 'Ömer Mermertaş',
        'c4' => '',
        'c5' => '',
        'note' => ''
    ],
    [
        'voter' => 'Abdullah Çetin',
        'c1' => 'Murat Zengin',
        'c2' => 'Mümtaz Şahbaz',
        'c3' => 'Atilla Coşkun',
        'c4' => '',
        'c5' => '',
        'note' => ''
    ]
];

foreach ($data as $vote) {
    echo "Inserting vote from: " . $vote['voter'] . "<br>";
    $db->query(
        "INSERT INTO istisare_oylama (voter_id, secilen_1, secilen_2, secilen_3, secilen_4, secilen_5, notlar) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$vote['voter'], $vote['c1'], $vote['c2'], $vote['c3'], $vote['c4'], $vote['c5'], $vote['note']]
    );
}

echo "Done!<br>";

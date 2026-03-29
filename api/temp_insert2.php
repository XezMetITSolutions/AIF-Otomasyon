<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

$data = [
    [
        'voter' => 'İbrahim Demirel',
        'c1' => 'Harun Yiğit',
        'c2' => 'Seyid Evkaya',
        'c3' => 'Mümtaz Şahbaz',
        'c4' => '',
        'c5' => '',
        'note' => 'Harun başkanlığı bıraksa bile yönetimde kalmalı'
    ],
    [
        'voter' => 'Hakkı Turgut',
        'c1' => 'Murat Zengin',
        'c2' => 'Atilla Coşkun',
        'c3' => 'Seyit Evkaya',
        'c4' => '',
        'c5' => '',
        'note' => ''
    ],
    [
        'voter' => 'Melik Duyar',
        'c1' => 'Atilla Coşkun',
        'c2' => 'Murat Zengin',
        'c3' => 'Seyit Evkaya',
        'c4' => '',
        'c5' => '',
        'note' => ''
    ],
    [
        'voter' => 'Musab Çetin',
        'c1' => 'Atilla Coşkun',
        'c2' => 'Murat Zengin',
        'c3' => 'Ömer Mermertaş',
        'c4' => 'Seyit Evkaya',
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

echo "Done2!<br>";

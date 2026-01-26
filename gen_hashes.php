<?php
$passwords = [
    "IGMGaif1453!",
    "AIF571#"
];

foreach ($passwords as $p) {
    echo $p . ": " . password_hash($p, PASSWORD_DEFAULT) . "\n";
}

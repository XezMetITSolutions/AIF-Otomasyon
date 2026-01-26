<?php
return [
    'host' => getenv('MAIL_HOST') ?: 'w0072b78.kasserver.com',
    'username' => getenv('MAIL_USER') ?: 'sitzung@islamischefoederation.at',
    'password' => getenv('MAIL_PASS') ?: '01528797Mb##',
    'port' => getenv('MAIL_PORT') ?: 587,
    'secure' => getenv('MAIL_SECURE') ?: 'tls', // tls or ssl
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'sitzung@islamischefoederation.at',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'AİF Otomasyon'
];

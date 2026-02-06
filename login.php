<?php
/**
 * Login Yönlendirmesi
 * 
 * Sistemdeki bazı sayfalar oturum kontrolü için /login.php adresine yönlendirme yapmaktadır.
 * Asıl giriş sayfası index.php olduğu için, login.php isteğini index.php'ye yönlendiriyoruz
 * veya doğrudan index.php'yi çalıştırıyoruz.
 */

// Doğrudan index.php'yi dahil ederek URL'in login.php olarak kalmasını
// ve form işlemlerinin (POST) sorunsuz çalışmasını sağlıyoruz.
require_once __DIR__ . '/index.php';

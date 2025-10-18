<?php
require_once 'auth.php';
require_once 'includes/byk_manager.php';

// Session kontrolü - sadece superadmin giriş yapabilir
SessionManager::requireRole('superadmin');

$currentUser = SessionManager::getCurrentUser();

// 2026 Yılı Program Listesi
$events_2026 = [
    // OCAK 2026
    ['date' => '2026-01-02', 'title' => 'Kış Tatil Kursu (Kızlar)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-01-03', 'title' => 'Kış Tatil Kursu (Kızlar)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-01-04', 'title' => 'Sabah Namazı Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-04', 'title' => 'Kış Tatil Kursu (Kızlar)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-01-05', 'title' => 'Kış Tatil Kursu (Kızlar)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-01-09', 'title' => 'İlk Yardım Kursu (İYSHB) - KGT GES', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-01-10', 'title' => '1. BKT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-10', 'title' => 'KGT GES', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-01-11', 'title' => '1. BKT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-11', 'title' => '1. GT ŞBT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-01-11', 'title' => 'Hac & Umre Okulu', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-15', 'title' => 'Miraç Kandili', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-17', 'title' => '1. KT BBT', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-01-17', 'title' => '1. KGT BBT', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-01-18', 'title' => '6. Meslek Eğitim Fuarı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-18', 'title' => '1. BBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-18', 'title' => 'BHUSBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-18', 'title' => '1. KT BBT', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-01-18', 'title' => '1. KGT BBT', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-01-20', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-23', 'title' => 'İrşad Progr.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-24', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-24', 'title' => 'BHUSBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-24', 'title' => 'Tems. B.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-24', 'title' => '1. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-01-25', 'title' => '1. ŞBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-01-25', 'title' => 'KGT Turnuva', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-01-25', 'title' => '1. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-01-31', 'title' => 'ŞB-YES Güney - GT Sabah Namazı', 'byk' => 'GT', 'color' => '#0d6efd'],

    // ŞUBAT 2026
    ['date' => '2026-02-01', 'title' => 'Hasene Günü ŞB-YES', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-01', 'title' => 'Sabah Namazı Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-01', 'title' => '1. KT ŞBT (T)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-02-01', 'title' => '1. KGT GŞBT', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-02-02', 'title' => 'Berat Kandili', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-02', 'title' => 'GT Gencin Orucu', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-02-07', 'title' => 'KGT Umreciler Buluşması', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-02-07', 'title' => 'ISV StudyDay GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-02-08', 'title' => 'Hutbe Yarışması CYKK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-08', 'title' => 'ŞCHBT Vomp Teftiş', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-08', 'title' => 'KGT Sabah Namazı', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-02-11', 'title' => 'GM İmam Hatipler Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-13', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-14', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-14', 'title' => 'HUÜST', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-14', 'title' => 'KKTY Bölge Elemesi', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-15', 'title' => 'Şube Ziyareti Tirol (GSYK)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-15', 'title' => 'HUÜST', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-15', 'title' => 'KT Annem ve Ben', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-02-15', 'title' => 'KGT Sabah Namazı', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-02-15', 'title' => 'Hac & Umre Okulu', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-17', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-19', 'title' => 'Ramazan Başlangıcı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-21', 'title' => 'Ramazan Resepsiyonu', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-02-22', 'title' => 'KGT Hayırlı Gece', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-02-27', 'title' => 'GT Hayırlı Gece', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-02-28', 'title' => 'KT Annem ve Ben Teravih', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-02-28', 'title' => 'GT Hayırlı Gece', 'byk' => 'GT', 'color' => '#0d6efd'],

    // MART 2026
    ['date' => '2026-03-01', 'title' => 'KT Emektarlar İftarı', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-03-02', 'title' => 'GT OÖ Mescid Kampı - Başl.', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-03-06', 'title' => 'AT Emektarlar İftarı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-07', 'title' => 'KT KİB Ramazan Aksiyonu', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-03-07', 'title' => 'KGT OÖ Mescid Kampı', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-03-07', 'title' => 'ISV Charity Iftar GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-03-08', 'title' => 'KGT OÖ Mescid Kampı', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-03-12', 'title' => 'KT Muhabbet Buluşması (Çevrimiçi)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-03-16', 'title' => 'Kadir Gecesi', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-19', 'title' => 'Arefe', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-19', 'title' => 'Mezarlık Ziyareti İrşad', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-19', 'title' => 'GT OÖ Mescid Kampı Bitiş', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-03-20', 'title' => 'Ramazan Bayramı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-21', 'title' => 'Ramazan Bayramı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-22', 'title' => 'Ramazan Bayramı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-27', 'title' => 'KT Hayrunnisa (V)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-03-27', 'title' => 'GT Ehl-i Sünnet', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-03-28', 'title' => 'Emektarlar Günü', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-28', 'title' => 'KT Hayr-un Nisa (T)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-03-29', 'title' => 'Emektarlar Günü', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-29', 'title' => 'Kuran-ı Kerim Yarışması', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-29', 'title' => 'Şube Ziyareti', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-03-29', 'title' => 'TİES', 'byk' => 'AT', 'color' => '#dc3545'],

    // NİSAN 2026
    ['date' => '2026-04-04', 'title' => '2. BİYÇBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-04', 'title' => '1. Hacc Semineri', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-04', 'title' => '1. BBT (AT)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-04', 'title' => '1. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-04', 'title' => '1. BBT (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-04-05', 'title' => 'Çocuk Şenliği', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-05', 'title' => '1. Hacc Semineri', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-05', 'title' => '1. BBT (AT)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-05', 'title' => '1. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-05', 'title' => '1. BBT (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-04-05', 'title' => 'Hac & Umre Okulu', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-06', 'title' => 'Eğitim Bşk. Top.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-07', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-08', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-09', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-10', 'title' => 'GT 1. OÖ Kampı', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-11', 'title' => 'TİES', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-11', 'title' => 'GT 1. OÖ Kampı', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-12', 'title' => 'TİES', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-12', 'title' => 'Radfeld Teftiş', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-12', 'title' => 'GT 1. OÖ Kampı', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-16', 'title' => 'ISV Uni Tour GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-18', 'title' => 'BGMÜT ve BTEBT+BSBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-18', 'title' => 'KGT YES (Raggal)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-04-19', 'title' => 'Şube Ziyareti VLBG (GSYK)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-19', 'title' => '2. ŞBT (KT) (V)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-04-19', 'title' => 'KGT YES (Raggal)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-04-19', 'title' => '2. ŞBT (GT) (Çevrimiçi)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-23', 'title' => 'ISV Social Week GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-24', 'title' => 'ISV Social Week GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-25', 'title' => 'İDA', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-25', 'title' => 'ISV Social Week GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-04-26', 'title' => '2. ŞBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-04-29', 'title' => 'KGT GOB Toplantısı', 'byk' => 'KGT', 'color' => '#198754'],

    // MAYIS 2026
    ['date' => '2026-05-01', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-01', 'title' => '1. BTBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-01', 'title' => 'KGT Gezi', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-05-01', 'title' => 'GT Abi Kardeş Haftası (Başl.)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-05-02', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-02', 'title' => '4 AHY', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-02', 'title' => '1. BTBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-02', 'title' => '2. Hacc Semineri', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-02', 'title' => '23. KKTY ÇF (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-05-02', 'title' => 'KGT Gezi', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-05-03', 'title' => '2. Hacc Semineri', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-03', 'title' => '23. KKTY ÇF (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-05-03', 'title' => 'KGT Gezi', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-05-03', 'title' => 'GT GOB Toplantısı', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-05-06', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-08', 'title' => 'İYSHB Fuarı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-09', 'title' => 'BEST', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-09', 'title' => 'Bursiyerler Buluşması ISV GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-05-10', 'title' => 'BEST + Panel', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-13', 'title' => '19. Kültür Fuarı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-14', 'title' => '19. Kültür Fuarı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-14', 'title' => 'Maide-i Kur\'an', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-15', 'title' => '19. Kültür Fuarı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-15', 'title' => 'Aile Okulu', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-16', 'title' => '19. Kültür Fuarı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-16', 'title' => 'Aile Okulu', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-16', 'title' => 'GT GOB Gecesi', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-05-17', 'title' => '19. Kültür Fuarı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-17', 'title' => 'Aile Okulu (Konferans)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-22', 'title' => 'KGT Şube Ziyareti', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-05-23', 'title' => 'BKİBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-24', 'title' => 'BKİBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-24', 'title' => 'Hadis Yarışması', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-25', 'title' => 'GT Abi Kardeş Haftası Bitiş', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-05-26', 'title' => 'Arefe', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-26', 'title' => 'Mezarlık Ziyareti İrşad', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-27', 'title' => 'Kurban Bayramı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-28', 'title' => 'Kurban Bayramı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-29', 'title' => 'Kurban Bayramı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-05-30', 'title' => 'Kurban Bayramı', 'byk' => 'AT', 'color' => '#dc3545'],

    // HAZİRAN 2026
    ['date' => '2026-06-06', 'title' => 'KGT OÖ Kampı (Raggal)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-06-06', 'title' => 'GT OÖ Futbol Turnuvası', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-06-07', 'title' => 'Bregenz Teftiş', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-07', 'title' => 'KT YEK Mezuniyet', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-06-07', 'title' => 'KGT OÖ Kampı (Raggal)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-06-09', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-10', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-11', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-12', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-13', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-13', 'title' => '3. BBT (AT)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-13', 'title' => '3. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-06-14', 'title' => '3. BBT (AT)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-14', 'title' => '3. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-06-14', 'title' => '3. BBT (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-06-14', 'title' => '2. BBT (KGT)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-06-16', 'title' => 'Hicri Yılbaşı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-17', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-20', 'title' => 'ISV ADABİ GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-06-21', 'title' => 'Zirl Teftiş', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-21', 'title' => '3. KT ŞBT (Çevrimiçi)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-06-21', 'title' => 'KGT 2. ŞBT', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-06-25', 'title' => 'Aşure Günü', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-27', 'title' => '3. ŞBT ve YES', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-27', 'title' => '23. KKTY YF (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-06-28', 'title' => '3. ŞBT ve YES', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-06-28', 'title' => 'KGT Bitiş Programı', 'byk' => 'KGT', 'color' => '#198754'],

    // TEMMUZ 2026
    ['date' => '2026-07-04', 'title' => 'ISV Uni Tour / Uni Seminer GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-07-05', 'title' => '2. GBYK ve Aile Pikniği', 'byk' => 'AT', 'color' => '#dc3545'],

    // AĞUSTOS 2026
    ['date' => '2026-08-24', 'title' => 'Mevlid Kandili', 'byk' => 'AT', 'color' => '#dc3545'],

    // EYLÜL 2026
    ['date' => '2026-09-06', 'title' => 'Eğitim Bşk. Top. (Çevrimiçi)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-12', 'title' => 'ISV Uni Panel GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-09-13', 'title' => 'Amin Alayı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-13', 'title' => '2. BTBT Çevrimiçi', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-16', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-18', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-19', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-19', 'title' => '4. BBT (AT)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-19', 'title' => '4. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-09-19', 'title' => '4. BBT (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-09-19', 'title' => '3. BBT (KGT)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-09-20', 'title' => '4. BBT (AT)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-20', 'title' => '4. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-09-20', 'title' => '4. BBT (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-09-20', 'title' => '3. BBT (KGT)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-09-26', 'title' => '2. BEBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-26', 'title' => '3. BİBT + BİSBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-26', 'title' => '3. BİYÇBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-27', 'title' => '4. ŞBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-09-27', 'title' => '3. ŞBT ve BET - GT (T)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-09-27', 'title' => '3. ŞBT KGT (T)', 'byk' => 'KGT', 'color' => '#198754'],

    // EKİM 2026
    ['date' => '2026-10-01', 'title' => 'Dünya Yaşlılar Günü', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-01', 'title' => 'KGT Kardeş Şube Günleri Başlangıç', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-10-01', 'title' => 'OÖ Mescid Kampı ve Ev So. Başl.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-02', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-02', 'title' => 'Gönül Sohbeti', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-03', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-03', 'title' => '4. KT GŞBT (Raggal)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-10-03', 'title' => 'KGT Hilal Hitabet Yarışması', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-10-04', 'title' => 'Sabah Namazı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-04', 'title' => 'Wörgl Teftiş', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-04', 'title' => '4. KT GŞBT (Raggal)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-10-10', 'title' => 'TİES HİE Güney', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-11', 'title' => 'GSYK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-11', 'title' => 'Şube Ziyareti V', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-14', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-17', 'title' => '38. AKKTY', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-17', 'title' => '23. KKTY FE (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-10-17', 'title' => 'ISV Panel GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-10-18', 'title' => 'GBYK-GSYK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-18', 'title' => '23. KKTY FE (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-10-23', 'title' => 'GT OÖ Kampı (Raggal)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-10-24', 'title' => 'GT OÖ Kampı (Raggal)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-10-25', 'title' => 'GSYK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-25', 'title' => 'Şube Ziyareti', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-25', 'title' => 'Tirol Dornbirn Teftiş', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-31', 'title' => 'TİES', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-10-31', 'title' => 'GT YHY', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-10-31', 'title' => 'KGT İDAYES (Raggal)', 'byk' => 'KGT', 'color' => '#198754'],

    // KASIM 2026
    ['date' => '2026-11-01', 'title' => 'Sabah Namazı TİES', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-01', 'title' => 'KT Cenaze Eğitim Semineri', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-11-01', 'title' => 'KGT İDAYES (Raggal)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-11-03', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-04', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-05', 'title' => 'IHEK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-07', 'title' => '5. BBT (AT)', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-07', 'title' => '5. BBT (GT)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-11-07', 'title' => '5. BBT (KT)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-11-07', 'title' => '4. BBT (KGT)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-11-08', 'title' => 'Genel Kurul', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-13', 'title' => 'KT Evliliğe Hazırlık Okulu', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-11-13', 'title' => 'KGT GES', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-11-14', 'title' => 'Salon Programı İrşad Çalıştayı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-14', 'title' => '2. BKT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-15', 'title' => 'Salon Programı 2. BKT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-18', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-20', 'title' => 'KT Evliliğe Hazırlık Okulu', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-11-21', 'title' => 'BESBHST', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-21', 'title' => '23. KT KKTY', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-11-21', 'title' => 'KGT OÖ Kampı (Raggal)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-11-21', 'title' => 'ISV Seminer GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-11-22', 'title' => 'BESBHST', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-22', 'title' => '5. KT ŞBT (T)', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-11-22', 'title' => 'KGT OÖ Kampı (Raggal)', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-11-22', 'title' => '4. GT ŞBT (V)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-11-27', 'title' => 'KT Evliliğe Hazırlık Okulu', 'byk' => 'KT', 'color' => '#6f42c1'],
    ['date' => '2026-11-27', 'title' => 'GT GİS', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-11-27', 'title' => 'KIB Orange Day', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-28', 'title' => 'BCHB ve BMBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-29', 'title' => 'ŞCHBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-29', 'title' => 'CYKK', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-11-29', 'title' => 'KGT Şube Ziyareti', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-11-30', 'title' => 'KGT Kardeş Şube Günleri Bitiş', 'byk' => 'KGT', 'color' => '#198754'],

    // ARALIK 2026
    ['date' => '2026-12-03', 'title' => 'Dünya Engelliler Günü', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-04', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-05', 'title' => 'İrşad Prog.', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-06', 'title' => 'Sabah Namazı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-06', 'title' => '3. GBTBT + GBTEBT + GGMÜT + GBST + TKT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-06', 'title' => 'KGT 4. ŞBT', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-12-10', 'title' => '3 Aylar Başlangıcı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-10', 'title' => 'Regaip Kandili', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-12', 'title' => '6. BBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-12', 'title' => 'Umre Semineri', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-12', 'title' => 'GT Medya Atölyesi (Raggal)', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-12-13', 'title' => '6. BBT', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-13', 'title' => 'Umre Semineri', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-13', 'title' => 'KGT Şube Ziyareti', 'byk' => 'KGT', 'color' => '#198754'],
    ['date' => '2026-12-16', 'title' => 'İmamlar Toplantısı', 'byk' => 'AT', 'color' => '#dc3545'],
    ['date' => '2026-12-20', 'title' => 'GT AKH Başlangıcı', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-12-25', 'title' => 'ISV Kültür Gezisi GT', 'byk' => 'GT', 'color' => '#0d6efd'],
    ['date' => '2026-12-31', 'title' => 'GT AKH Bitişi', 'byk' => 'GT', 'color' => '#0d6efd']
];

// FullCalendar için etkinlikleri hazırla
$calendar_events = [];
foreach ($events_2026 as $event) {
    $calendar_events[] = [
        'title' => $event['title'],
        'start' => $event['date'],
        'color' => $event['color'],
        'extendedProps' => [
            'byk' => $event['byk'],
            'description' => $event['title'] . ' - ' . $event['byk']
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIF Otomasyon - Takvim</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        .calendar-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .fc-event {
            border-radius: 5px !important;
            border: none !important;
            font-size: 0.85rem !important;
            padding: 2px 4px !important;
        }
        
        .fc-daygrid-event {
            margin: 1px 0 !important;
        }
        
        .byk-legend {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .byk-legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .byk-legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Takvim</h1>
                </div>
                <div class="header-actions">
                    <div class="d-flex gap-2">
                        <select class="form-select" id="bykFilter" style="width: 200px;">
                            <option value="">Tüm BYK'lar</option>
                            <?php foreach (BYKManager::getBYKCategories() as $code => $name): ?>
                            <option value="<?php echo $code; ?>" data-color="<?php echo BYKManager::getBYKPrimaryColor($code); ?>">
                                <?php echo $code; ?> - <?php echo $name; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                            <i class="fas fa-plus"></i> Etkinlik Ekle
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- BYK Legend -->
            <div class="byk-legend">
                <div class="byk-legend-item">
                    <div class="byk-legend-color" style="background-color: #dc3545;"></div>
                    <span><strong>AT</strong> - Ana Teşkilat (<?php echo count(array_filter($calendar_events, function($e) { return $e['extendedProps']['byk'] === 'AT'; })); ?> etkinlik)</span>
                </div>
                <div class="byk-legend-item">
                    <div class="byk-legend-color" style="background-color: #6f42c1;"></div>
                    <span><strong>KT</strong> - Kadınlar Teşkilatı (<?php echo count(array_filter($calendar_events, function($e) { return $e['extendedProps']['byk'] === 'KT'; })); ?> etkinlik)</span>
                </div>
                <div class="byk-legend-item">
                    <div class="byk-legend-color" style="background-color: #198754;"></div>
                    <span><strong>KGT</strong> - Kadınlar Gençlik Teşkilatı (<?php echo count(array_filter($calendar_events, function($e) { return $e['extendedProps']['byk'] === 'KGT'; })); ?> etkinlik)</span>
                </div>
                <div class="byk-legend-item">
                    <div class="byk-legend-color" style="background-color: #0d6efd;"></div>
                    <span><strong>GT</strong> - Gençlik Teşkilatı (<?php echo count(array_filter($calendar_events, function($e) { return $e['extendedProps']['byk'] === 'GT'; })); ?> etkinlik)</span>
                </div>
            </div>

            <!-- Calendar -->
            <div class="calendar-container">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Etkinlik Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEventForm">
                        <div class="mb-3">
                            <label for="eventTitle" class="form-label">Etkinlik Adı</label>
                            <input type="text" class="form-control" id="eventTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="eventDate" class="form-label">Tarih</label>
                            <input type="date" class="form-control" id="eventDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="eventBYK" class="form-label">BYK</label>
                            <select class="form-select" id="eventBYK" required>
                                <option value="">BYK Seçin</option>
                                <?php foreach (BYKManager::getBYKCategories() as $code => $name): ?>
                                <option value="<?php echo $code; ?>"><?php echo $code; ?> - <?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="eventDescription" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="eventDescription" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="addEvent()">Etkinlik Ekle</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'tr',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listWeek'
                },
                events: <?php echo json_encode($calendar_events); ?>,
                eventClick: function(info) {
                    alert('Etkinlik: ' + info.event.title + '\nBYK: ' + info.event.extendedProps.byk + '\nTarih: ' + info.event.start.toLocaleDateString('tr-TR'));
                },
                eventDidMount: function(info) {
                    // Tooltip ekle
                    info.el.title = info.event.title + ' - ' + info.event.extendedProps.byk;
                }
            });
            
            calendar.render();
            
            // BYK Filter
            $('#bykFilter').on('change', function() {
                const selectedBYK = $(this).val();
                if (selectedBYK === '') {
                    calendar.removeAllEventSources();
                    calendar.addEventSource(<?php echo json_encode($calendar_events); ?>);
                } else {
                    const filteredEvents = <?php echo json_encode($calendar_events); ?>.filter(event => 
                        event.extendedProps.byk === selectedBYK
                    );
                    calendar.removeAllEventSources();
                    calendar.addEventSource(filteredEvents);
                }
            });
        });
        
        function addEvent() {
            const title = document.getElementById('eventTitle').value;
            const date = document.getElementById('eventDate').value;
            const byk = document.getElementById('eventBYK').value;
            const description = document.getElementById('eventDescription').value;
            
            if (!title || !date || !byk) {
                alert('Lütfen tüm zorunlu alanları doldurun.');
                return;
            }
            
            // BYK (Bölge Yönetim Kurulu) renklerini al
            const bykColors = {
                'AT': '#dc3545',
                'KT': '#6f42c1',
                'KGT': '#198754',
                'GT': '#0d6efd'
            };
            
            const newEvent = {
                title: title,
                start: date,
                color: bykColors[byk],
                extendedProps: {
                    byk: byk,
                    description: description
                }
            };
            
            // Etkinliği takvime ekle
            const calendar = FullCalendar.getApi();
            calendar.addEvent(newEvent);
            
            // Modal'ı kapat ve formu temizle
            const modal = bootstrap.Modal.getInstance(document.getElementById('addEventModal'));
            modal.hide();
            document.getElementById('addEventForm').reset();
            
            alert('Etkinlik başarıyla eklendi!');
        }
    </script>
</body>
</html>
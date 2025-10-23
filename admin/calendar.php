<?php
require_once 'auth.php';

// Login kontrolü kaldırıldı - direkt erişim
$currentUser = SessionManager::getCurrentUser();

// 2026 Yılı Program Listesi - Backup'tan alınan tam veri
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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AIF Otomasyon - Takvim</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
        <?php include 'includes/styles.php'; ?>
        
        /* Calendar specific styles */
        :root {
            --primary-color: #009872;
            --primary-dark: #007a5e;
            --primary-light: #00b085;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --shadow-light: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-medium: 0 4px 20px rgba(0,0,0,0.15);
        }

        /* Calendar Styles */
        .calendar-header {
            background: white;
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .calendar-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .calendar-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-calendar {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-calendar:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
        }

        .main-calendar-container {
            display: grid;
            grid-template-columns: 1fr 0.3fr;
            gap: 20px;
        }

        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow-light);
        }

        .event-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow-light);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #dee2e6;
            border-radius: 10px;
            overflow: hidden;
        }

        .calendar-day-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .calendar-day {
            background: white;
            padding: 15px 10px;
            min-height: 120px;
            border: none;
            position: relative;
            transition: all 0.3s ease;
        }

        .calendar-day:hover {
            background: #f8f9fa;
        }

        .calendar-day.other-month {
            background: #f8f9fa;
            color: #6c757d;
        }

        .calendar-day.today {
            background: #e3f2fd;
            border: 2px solid var(--primary-color);
        }

        .day-number {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .event-item {
            background: var(--primary-color);
            color: white;
            padding: 2px 6px;
            margin: 1px 0;
            border-radius: 3px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .event-item:hover {
            transform: scale(1.05);
            z-index: 10;
            position: relative;
        }

        .event-item.at { background: var(--danger-color); }
        .event-item.kt { background: #6f42c1; }
        .event-item.gt { background: var(--primary-color); }
        .event-item.kgt { background: var(--success-color); }

        .event-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow-light);
        }

        .event-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .event-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .event-card:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-medium);
        }

        .event-card.at { border-left-color: var(--danger-color); }
        .event-card.kt { border-left-color: #6f42c1; }
        .event-card.gt { border-left-color: var(--primary-color); }
        .event-card.kgt { border-left-color: var(--success-color); }

        .event-date {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .event-title {
            font-weight: 500;
            margin: 5px 0;
        }

        .event-byk {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* Bugünün etkinlikleri için özel stil */
        .today-event {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%) !important;
            border-left: 4px solid #2196f3 !important;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
            transform: scale(1.02);
        }

        .today-event:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(33, 150, 243, 0.4);
        }

        /* Mobil Responsive Tasarım */
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }

            .calendar-header {
                padding: 15px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .calendar-title {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }

            .calendar-controls {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 20px;
            }

            .btn-calendar {
                padding: 12px 16px;
                font-size: 1.1rem;
                min-width: 50px;
                min-height: 50px;
            }

            #currentMonth {
                font-size: 1.2rem;
                font-weight: 600;
            }

            .main-calendar-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .calendar-container {
                padding: 15px;
            }

            .event-details {
                padding: 15px;
            }

            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 1px;
            }

            .calendar-day-header {
                padding: 8px 4px;
                font-size: 0.8rem;
                font-weight: 600;
            }

            .calendar-day {
                padding: 8px 4px;
                min-height: 80px;
                font-size: 0.8rem;
            }

            .day-number {
                font-size: 0.9rem;
                margin-bottom: 3px;
            }

            .event-item {
                padding: 1px 4px;
                margin: 1px 0;
                font-size: 0.7rem;
                border-radius: 2px;
            }

            .event-details {
                padding: 15px;
            }

            .event-details h4 {
                font-size: 1.2rem;
                margin-bottom: 15px;
            }

            .event-card {
                padding: 12px 15px;
                margin-bottom: 8px;
            }

            .event-date {
                font-size: 0.9rem;
                min-width: 50px;
            }

            .event-title {
                font-size: 0.9rem;
                margin: 3px 0;
            }

            .event-byk {
                font-size: 0.7rem;
                padding: 2px 6px;
            }

            .filter-section {
                padding: 15px;
                margin-bottom: 15px;
            }

            .filter-title {
                font-size: 1rem;
                margin-bottom: 10px;
            }

            .filter-buttons {
                gap: 8px;
            }

            .filter-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
                border-radius: 15px;
            }

            .legend {
                flex-direction: column;
                gap: 10px;
                margin-top: 15px;
            }

            .legend-item {
                font-size: 0.8rem;
            }

            .legend-color {
                width: 16px;
                height: 16px;
                margin-right: 8px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 5px;
            }

            .calendar-header {
                padding: 10px;
            }

            .calendar-title {
                font-size: 1.3rem;
            }

            .calendar-day {
                padding: 6px 2px;
                min-height: 70px;
                font-size: 0.7rem;
            }

            .calendar-day-header {
                padding: 6px 2px;
                font-size: 0.7rem;
            }

            .day-number {
                font-size: 0.8rem;
            }

            .event-item {
                padding: 1px 3px;
                font-size: 0.6rem;
            }

            .event-card {
                padding: 10px 12px;
                margin-bottom: 6px;
            }

            .event-date {
                font-size: 0.8rem;
                min-width: 45px;
            }

            .event-title {
                font-size: 0.8rem;
            }

            .event-byk {
                font-size: 0.6rem;
                padding: 1px 4px;
            }

            .filter-btn {
                padding: 5px 10px;
                font-size: 0.7rem;
            }

            .btn-calendar {
                padding: 10px 14px;
                font-size: 1rem;
                min-width: 45px;
                min-height: 45px;
            }

            .calendar-container {
                padding: 10px;
            }

            .event-details {
                padding: 10px;
            }
        }

        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        /* Filter Styles */
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-light);
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid transparent;
            border-radius: 20px;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .filter-btn.at.active {
            background: var(--danger-color);
            border-color: var(--danger-color);
        }

        .filter-btn.kt.active {
            background: #6f42c1;
            border-color: #6f42c1;
        }

        .filter-btn.gt.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .filter-btn.kgt.active {
            background: var(--success-color);
            border-color: var(--success-color);
        }

        .filter-btn .filter-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .filter-btn.at .filter-dot {
            background: var(--danger-color);
        }

        .filter-btn.kt .filter-dot {
            background: #6f42c1;
        }

        .filter-btn.gt .filter-dot {
            background: var(--primary-color);
        }

        .filter-btn.kgt .filter-dot {
            background: var(--success-color);
        }

        .filter-btn.active .filter-dot {
            background: white;
        }

        .events-count {
            font-size: 0.8rem;
            color: #6c757d;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Calendar Header -->
        <div class="calendar-header">
            <h1 class="calendar-title" id="calendarTitle">2026 Yılı Takvimi</h1>
            <div class="calendar-controls">
                <button class="btn btn-calendar" onclick="previousMonth()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span id="currentMonth" class="fw-bold fs-5">Ocak 2026</span>
                <button class="btn btn-calendar" onclick="nextMonth()">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                BYK Kategorilerine Göre Filtrele
                <span class="events-count" id="eventsCount"></span>
            </div>
            <div class="filter-buttons">
                <button class="filter-btn all active" onclick="filterEvents('all')">
                    <div class="filter-dot"></div>
                    Tümü
                </button>
                <button class="filter-btn at" onclick="filterEvents('AT')">
                    <div class="filter-dot"></div>
                    AT - Ana Teşkilat
                </button>
                <button class="filter-btn kt" onclick="filterEvents('KT')">
                    <div class="filter-dot"></div>
                    KT - Kadınlar Teşkilatı
                </button>
                <button class="filter-btn gt" onclick="filterEvents('GT')">
                    <div class="filter-dot"></div>
                    GT - Gençlik Teşkilatı
                </button>
                <button class="filter-btn kgt" onclick="filterEvents('KGT')">
                    <div class="filter-dot"></div>
                    KGT - Kadınlar Gençlik Teşkilatı
                </button>
            </div>
        </div>
        
        <!-- Ana Container - Desktop'ta yan yana, mobilde alt alta -->
        <div class="main-calendar-container">
            <div class="calendar-container">
                <div class="calendar-grid" id="calendarGrid">
                    <!-- Calendar will be generated by JavaScript -->
                </div>
                
                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color at"></div>
                        <span>AT - Ana Teşkilat</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color kt"></div>
                        <span>KT - Kadınlar Teşkilatı</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color gt"></div>
                        <span>GT - Gençlik Teşkilatı</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color kgt"></div>
                        <span>KGT - Kadınlar Gençlik Teşkilatı</span>
                    </div>
                </div>
            </div>

            <!-- Event Details -->
            <div class="event-details">
                <h4 class="mb-4">
                    <i class="fas fa-list"></i>
                    Bu Ayın Gelecek Etkinlikleri
                    <span id="eventsCount" class="badge bg-primary ms-2">(0 gelecek etkinlik)</span>
                </h4>
                <div class="event-list" id="eventList">
                    <!-- Events will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Event Edit Modal -->
    <div class="modal fade" id="eventEditModal" tabindex="-1" aria-labelledby="eventEditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventEditModalLabel">Etkinlik Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="eventEditForm">
                        <div class="mb-3">
                            <label for="editEventTitle" class="form-label">Etkinlik Adı</label>
                            <input type="text" class="form-control" id="editEventTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEventDate" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="editEventDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEventEndDate" class="form-label">Bitiş Tarihi (Opsiyonel)</label>
                            <input type="date" class="form-control" id="editEventEndDate">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editEventRecurring" onchange="toggleRecurrenceOptions()">
                                <label class="form-check-label" for="editEventRecurring">
                                    Tekrarlayan Etkinlik
                                </label>
                            </div>
                        </div>
                        <div id="recurrenceOptions" style="display: none;">
                            <div class="mb-3">
                                <label for="editRecurrenceType" class="form-label">Tekrar Tipi</label>
                                <select class="form-select" id="editRecurrenceType" onchange="updateRecurrencePattern()">
                                    <option value="none">Tekrar Yok</option>
                                    <option value="daily">Günlük</option>
                                    <option value="weekly">Haftalık</option>
                                    <option value="monthly">Aylık</option>
                                    <option value="yearly">Yıllık</option>
                                </select>
                            </div>
                            <div class="mb-3" id="recurrencePatternDiv" style="display: none;">
                                <label for="editRecurrencePattern" class="form-label">Tekrar Deseni</label>
                                <select class="form-select" id="editRecurrencePattern">
                                    <option value="">Seçin...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editRecurrenceEndDate" class="form-label">Tekrar Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="editRecurrenceEndDate">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editEventBYK" class="form-label">BYK Kategorisi</label>
                            <select class="form-select" id="editEventBYK" required>
                                <option value="AT">AT - Ana Teşkilat</option>
                                <option value="KT">KT - Kadınlar Teşkilatı</option>
                                <option value="GT">GT - Gençlik Teşkilatı</option>
                                <option value="KGT">KGT - Kadınlar Gençlik Teşkilatı</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editEventDescription" class="form-label">Açıklama (Opsiyonel)</label>
                            <textarea class="form-control" id="editEventDescription" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" onclick="deleteEvent()">Sil</button>
                    <button type="button" class="btn btn-primary" onclick="saveEvent()">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calendar data - will be loaded from database
        let events = [];
        
        // Takvim bugünden başlasın
        const today = new Date();
        let currentMonth = today.getMonth(); // Mevcut ay
        let currentYear = today.getFullYear(); // Mevcut yıl
        let currentFilter = 'all'; // Current filter state
        let selectedEvent = null; // Seçili etkinlik
        
        const monthNames = [
            'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
            'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
        ];
        
        const dayNames = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
        
        // Load events from database
        async function loadEvents() {
            try {
                const response = await fetch(`../calendar_api.php?action=list&year=${currentYear}&month=${currentMonth + 1}`);
                const data = await response.json();
                
                if (data.success) {
                    events = data.events.map(event => ({
                        id: event.id,
                        title: event.title,
                        date: event.start_date,
                        end_date: event.end_date,
                        byk: event.byk_category,
                        description: event.description || '',
                        is_recurring: event.is_recurring,
                        recurrence_type: event.recurrence_type,
                        recurrence_pattern: event.recurrence_pattern,
                        recurrence_end_date: event.recurrence_end_date
                    }));
                    
                    // Generate calendar with loaded events
                    generateCalendar();
                } else {
                    console.error('Etkinlikler yüklenirken hata:', data.message);
                }
            } catch (error) {
                console.error('Etkinlikler yüklenirken hata:', error);
            }
        }
        
        // Filter events based on BYK category
        function filterEvents(bykCategory) {
            currentFilter = bykCategory;
            
            // Update filter button states
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            if (bykCategory === 'all') {
                document.querySelector('.filter-btn.all').classList.add('active');
            } else {
                document.querySelector(`.filter-btn.${bykCategory.toLowerCase()}`).classList.add('active');
            }
            
            // Regenerate calendar with filtered events
            generateCalendar();
        }
        
        // Get filtered events for current month
        function getFilteredEvents() {
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Bugünün başlangıcı
            
            let monthEvents = events.filter(event => {
                const eventDate = new Date(event.date);
                return eventDate.getMonth() === currentMonth && 
                       eventDate.getFullYear() === currentYear &&
                       eventDate >= today; // Sadece bugün ve sonrasındaki etkinlikler
            });
            
            if (currentFilter !== 'all') {
                monthEvents = monthEvents.filter(event => event.byk === currentFilter);
            }
            
            return monthEvents;
        }
        
        // Update events count display
        function updateEventsCount() {
            const filteredEvents = getFilteredEvents();
            const countElement = document.getElementById('eventsCount');
            
            if (currentFilter === 'all') {
                countElement.textContent = `(${filteredEvents.length} gelecek etkinlik)`;
            } else {
                countElement.textContent = `(${filteredEvents.length} ${currentFilter} gelecek etkinliği)`;
            }
        }
        
        function generateCalendar() {
            const calendarGrid = document.getElementById('calendarGrid');
            const eventList = document.getElementById('eventList');
            
            // Clear previous content
            calendarGrid.innerHTML = '';
            eventList.innerHTML = '';
            
            // Add day headers
            dayNames.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day-header';
                dayHeader.textContent = day;
                calendarGrid.appendChild(dayHeader);
            });
            
            // Get first day of month and number of days
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
            
            // Previous month days
            for (let i = firstDay - 1; i >= 0; i--) {
                const dayElement = createDayElement(daysInPrevMonth - i, true);
                calendarGrid.appendChild(dayElement);
            }
            
            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = createDayElement(day, false);
                calendarGrid.appendChild(dayElement);
            }
            
            // Next month days
            const totalCells = calendarGrid.children.length - 7; // Subtract day headers
            const remainingCells = 42 - totalCells; // 6 weeks * 7 days
            for (let day = 1; day <= remainingCells; day++) {
                const dayElement = createDayElement(day, true);
                calendarGrid.appendChild(dayElement);
            }
            
            // Update month display
            document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;
            
            // Update calendar title
            document.getElementById('calendarTitle').textContent = `${currentYear} Yılı Takvimi`;
            
            // Update event list
            updateEventList();
            
            // Update events count
            updateEventsCount();
        }
        
        function createDayElement(day, isOtherMonth) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            
            if (isOtherMonth) {
                dayElement.classList.add('other-month');
            }
            
            // Check if today
            const today = new Date();
            if (!isOtherMonth && 
                day === today.getDate() && 
                currentMonth === today.getMonth() && 
                currentYear === today.getFullYear()) {
                dayElement.classList.add('today');
            }
            
            // Day number
            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = day;
            dayElement.appendChild(dayNumber);
            
            // Add events for this day
            if (!isOtherMonth) {
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                let dayEvents = events.filter(event => event.date === dateStr);
                
                // Apply current filter
                if (currentFilter !== 'all') {
                    dayEvents = dayEvents.filter(event => event.byk === currentFilter);
                }
                
                dayEvents.forEach(event => {
                    const eventElement = document.createElement('div');
                    eventElement.className = `event-item ${event.byk.toLowerCase()}`;
                    eventElement.textContent = event.title;
                    eventElement.title = event.title;
                    eventElement.onclick = () => showEventDetails(event);
                    dayElement.appendChild(eventElement);
                });
            }
            
            return dayElement;
        }
        
        function updateEventList() {
            const eventList = document.getElementById('eventList');
            const monthEvents = getFilteredEvents();
            
            // Event listesini temizle
            eventList.innerHTML = '';
            
            if (monthEvents.length === 0) {
                if (currentFilter === 'all') {
                    eventList.innerHTML = '<p class="text-muted text-center">Bu ay için gelecek etkinlik bulunmuyor.</p>';
                } else {
                    eventList.innerHTML = `<p class="text-muted text-center">Bu ay için ${currentFilter} kategorisinde gelecek etkinlik bulunmuyor.</p>`;
                }
                return;
            }
            
            // Sort events by date
            monthEvents.sort((a, b) => new Date(a.date) - new Date(b.date));
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            monthEvents.forEach(event => {
                const eventCard = document.createElement('div');
                const eventDate = new Date(event.date);
                const isToday = eventDate.getTime() === today.getTime();
                
                eventCard.className = `event-card ${event.byk.toLowerCase()} ${isToday ? 'today-event' : ''}`;
                
                const dateStr = `${eventDate.getDate()} ${monthNames[eventDate.getMonth()]}${isToday ? ' (Bugün)' : ''}`;
                
                eventCard.innerHTML = `
                    <div class="event-date">${dateStr}</div>
                    <div class="event-title">${event.title}</div>
                    <div class="event-byk">${event.byk}</div>
                `;
                
                eventCard.onclick = () => showEventDetails(event);
                eventList.appendChild(eventCard);
            });
            
            // Update events count
            updateEventsCount();
        }
        
        function showEventDetails(event) {
            selectedEvent = event;
            
            // Modal formunu doldur
            document.getElementById('editEventTitle').value = event.title;
            document.getElementById('editEventDate').value = event.date;
            document.getElementById('editEventEndDate').value = event.end_date || event.date;
            document.getElementById('editEventBYK').value = event.byk;
            document.getElementById('editEventDescription').value = event.description || '';
            document.getElementById('editEventRecurring').checked = event.is_recurring || false;
            document.getElementById('editRecurrenceType').value = event.recurrence_type || 'none';
            document.getElementById('editRecurrencePattern').value = event.recurrence_pattern || '';
            document.getElementById('editRecurrenceEndDate').value = event.recurrence_end_date || '';
            
            // Tekrar seçeneklerini göster/gizle
            toggleRecurrenceOptions();
            
            // Modal'ı göster
            const modal = new bootstrap.Modal(document.getElementById('eventEditModal'));
            modal.show();
        }
        
        function toggleRecurrenceOptions() {
            const isRecurring = document.getElementById('editEventRecurring').checked;
            const recurrenceOptions = document.getElementById('recurrenceOptions');
            const recurrencePatternDiv = document.getElementById('recurrencePatternDiv');
            
            if (isRecurring) {
                recurrenceOptions.style.display = 'block';
                updateRecurrencePattern();
            } else {
                recurrenceOptions.style.display = 'none';
                recurrencePatternDiv.style.display = 'none';
            }
        }
        
        function updateRecurrencePattern() {
            const recurrenceType = document.getElementById('editRecurrenceType').value;
            const patternSelect = document.getElementById('editRecurrencePattern');
            const patternDiv = document.getElementById('recurrencePatternDiv');
            
            patternSelect.innerHTML = '<option value="">Seçin...</option>';
            
            if (recurrenceType === 'weekly') {
                patternDiv.style.display = 'block';
                patternSelect.innerHTML = `
                    <option value="1">Her Pazartesi</option>
                    <option value="2">Her Salı</option>
                    <option value="3">Her Çarşamba</option>
                    <option value="4">Her Perşembe</option>
                    <option value="5">Her Cuma</option>
                    <option value="6">Her Cumartesi</option>
                    <option value="7">Her Pazar</option>
                `;
            } else if (recurrenceType === 'monthly') {
                patternDiv.style.display = 'block';
                patternSelect.innerHTML = `
                    <option value="1">Her ayın 1'i</option>
                    <option value="2">Her ayın 2'si</option>
                    <option value="3">Her ayın 3'ü</option>
                    <option value="4">Her ayın 4'ü</option>
                    <option value="5">Her ayın 5'i</option>
                    <option value="10">Her ayın 10'u</option>
                    <option value="15">Her ayın 15'i</option>
                    <option value="20">Her ayın 20'si</option>
                    <option value="25">Her ayın 25'i</option>
                    <option value="last">Her ayın son günü</option>
                    <option value="first_monday">Her ayın ilk Pazartesi</option>
                    <option value="first_tuesday">Her ayın ilk Salı</option>
                    <option value="first_wednesday">Her ayın ilk Çarşamba</option>
                    <option value="first_thursday">Her ayın ilk Perşembe</option>
                    <option value="first_friday">Her ayın ilk Cuma</option>
                    <option value="first_saturday">Her ayın ilk Cumartesi</option>
                    <option value="first_sunday">Her ayın ilk Pazar</option>
                    <option value="last_monday">Her ayın son Pazartesi</option>
                    <option value="last_tuesday">Her ayın son Salı</option>
                    <option value="last_wednesday">Her ayın son Çarşamba</option>
                    <option value="last_thursday">Her ayın son Perşembe</option>
                    <option value="last_friday">Her ayın son Cuma</option>
                    <option value="last_saturday">Her ayın son Cumartesi</option>
                    <option value="last_sunday">Her ayın son Pazar</option>
                `;
            } else if (recurrenceType === 'yearly') {
                patternDiv.style.display = 'block';
                patternSelect.innerHTML = `
                    <option value="1-1">Her yıl 1 Ocak</option>
                    <option value="1-15">Her yıl 15 Ocak</option>
                    <option value="2-14">Her yıl 14 Şubat</option>
                    <option value="3-21">Her yıl 21 Mart</option>
                    <option value="4-23">Her yıl 23 Nisan</option>
                    <option value="5-1">Her yıl 1 Mayıs</option>
                    <option value="5-19">Her yıl 19 Mayıs</option>
                    <option value="6-1">Her yıl 1 Haziran</option>
                    <option value="7-15">Her yıl 15 Temmuz</option>
                    <option value="8-30">Her yıl 30 Ağustos</option>
                    <option value="9-1">Her yıl 1 Eylül</option>
                    <option value="10-29">Her yıl 29 Ekim</option>
                    <option value="11-10">Her yıl 10 Kasım</option>
                    <option value="12-31">Her yıl 31 Aralık</option>
                `;
            } else {
                patternDiv.style.display = 'none';
            }
        }
        
        async function saveEvent() {
            if (!selectedEvent) return;
            
            // Form verilerini al
            const title = document.getElementById('editEventTitle').value;
            const startDate = document.getElementById('editEventDate').value;
            const endDate = document.getElementById('editEventEndDate').value || startDate;
            const byk = document.getElementById('editEventBYK').value;
            const description = document.getElementById('editEventDescription').value;
            const isRecurring = document.getElementById('editEventRecurring').checked;
            const recurrenceType = document.getElementById('editRecurrenceType').value;
            const recurrencePattern = document.getElementById('editRecurrencePattern').value;
            const recurrenceEndDate = document.getElementById('editRecurrenceEndDate').value;
            
            if (!title || !startDate || !byk) {
                alert('Lütfen tüm zorunlu alanları doldurun!');
                return;
            }
            
            try {
                const response = await fetch('../calendar_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'save',
                        id: selectedEvent.id,
                        title: title,
                        start_date: startDate,
                        end_date: endDate,
                        byk_category: byk,
                        description: description,
                        is_recurring: isRecurring ? 1 : 0,
                        recurrence_type: recurrenceType,
                        recurrence_pattern: recurrencePattern,
                        recurrence_end_date: recurrenceEndDate
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Modal'ı kapat
                    const modal = bootstrap.Modal.getInstance(document.getElementById('eventEditModal'));
                    modal.hide();
                    
                    // Etkinlikleri yeniden yükle
                    await loadEvents();
                    
                    alert('Etkinlik başarıyla kaydedildi!');
                } else {
                    alert('Hata: ' + data.message);
                }
            } catch (error) {
                console.error('Kaydetme hatası:', error);
                alert('Etkinlik kaydedilirken hata oluştu!');
            }
        }
        
        async function deleteEvent() {
            if (!selectedEvent) return;
            
            if (confirm('Bu etkinliği silmek istediğinizden emin misiniz?')) {
                try {
                    const response = await fetch('../calendar_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            id: selectedEvent.id
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Modal'ı kapat
                        const modal = bootstrap.Modal.getInstance(document.getElementById('eventEditModal'));
                        modal.hide();
                        
                        // Etkinlikleri yeniden yükle
                        await loadEvents();
                        
                        alert('Etkinlik başarıyla silindi!');
                    } else {
                        alert('Hata: ' + data.message);
                    }
                } catch (error) {
                    console.error('Silme hatası:', error);
                    alert('Etkinlik silinirken hata oluştu!');
                }
            }
        }
        
        function previousMonth() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            loadEvents();
        }
        
        function nextMonth() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            loadEvents();
        }
        
        // Sayfa yüklendiğinde takvimi başlat
        document.addEventListener('DOMContentLoaded', function() {
            loadEvents();
            
            // Bugünün tarihini vurgula
            highlightToday();
            
            // Mobil cihazlar için dokunma olayları
            addTouchEvents();
        });

        // Mobil cihazlar için dokunma olayları
        function addTouchEvents() {
            // Takvim günlerine dokunma olayı
            const calendarDays = document.querySelectorAll('.calendar-day');
            calendarDays.forEach(day => {
                day.addEventListener('touchstart', function(e) {
                    this.style.transform = 'scale(0.95)';
                });
                
                day.addEventListener('touchend', function(e) {
                    this.style.transform = 'scale(1)';
                });
            });

            // Filtre butonlarına dokunma olayı
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(btn => {
                btn.addEventListener('touchstart', function(e) {
                    this.style.transform = 'scale(0.95)';
                });
                
                btn.addEventListener('touchend', function(e) {
                    this.style.transform = 'scale(1)';
                });
            });

            // Takvim navigasyon butonlarına dokunma olayı
            const navButtons = document.querySelectorAll('.btn-calendar');
            navButtons.forEach(btn => {
                btn.addEventListener('touchstart', function(e) {
                    this.style.transform = 'scale(0.95)';
                });
                
                btn.addEventListener('touchend', function(e) {
                    this.style.transform = 'scale(1)';
                });
            });
        }
        
        // Bugünün tarihini vurgulama fonksiyonu
        function highlightToday() {
            const today = new Date();
            const todayString = today.getDate().toString();
            
            // Bugünün gününü bul ve vurgula
            const calendarDays = document.querySelectorAll('.calendar-day');
            calendarDays.forEach(day => {
                const dayNumber = day.querySelector('.day-number');
                if (dayNumber && dayNumber.textContent === todayString) {
                    // Bugünün ayında mı kontrol et
                    const dayDate = new Date(currentYear, currentMonth, parseInt(todayString));
                    if (dayDate.getMonth() === today.getMonth() && dayDate.getFullYear() === today.getFullYear()) {
                        day.style.backgroundColor = '#e3f2fd';
                        day.style.border = '2px solid #2196f3';
                        day.style.borderRadius = '8px';
                        
                        // Bugün yazısı ekle
                        const todayLabel = document.createElement('div');
                        todayLabel.textContent = 'Bugün';
                        todayLabel.style.fontSize = '0.7rem';
                        todayLabel.style.color = '#2196f3';
                        todayLabel.style.fontWeight = 'bold';
                        todayLabel.style.marginTop = '5px';
                        day.appendChild(todayLabel);
                    }
                }
            });
        }
        
    </script>
</body>
</html>
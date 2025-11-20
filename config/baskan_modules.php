<?php
/**
 * Başkan modül yetkileri tanımları
 * Her modül benzersiz bir anahtar ile temsil edilir.
 */
return [
    // Başkan paneli modülleri (varsayılan: açık)
    'baskan_dashboard' => [
        'label' => 'Kontrol Paneli',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],
    'baskan_uyeler' => [
        'label' => 'Üye Yönetimi',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],
    'baskan_etkinlikler' => [
        'label' => 'Etkinlikler',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],
    'baskan_toplantilar' => [
        'label' => 'Toplantılar',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],
    'baskan_duyurular' => [
        'label' => 'Duyurular',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],
    'baskan_izin_talepleri' => [
        'label' => 'İzin Talepleri',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],
    'baskan_harcama_talepleri' => [
        'label' => 'Harcama Talepleri',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],
    'baskan_iade_formlari' => [
        'label' => 'İade Formları',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],
    'baskan_raporlar' => [
        'label' => 'Raporlar',
        'group' => 'Baskan Paneli',
        'category' => 'baskan',
        'default' => true,
    ],

    // Üye paneli modülleri (varsayılan: kapalı)
    'uye_dashboard' => [
        'label' => 'Üye Kontrol Paneli',
        'group' => 'Üye Modülleri',
        'category' => 'uye',
        'default' => false,
    ],
    'uye_duyurular' => [
        'label' => 'Üye Duyuruları',
        'group' => 'Üye Modülleri',
        'category' => 'uye',
        'default' => false,
    ],
    'uye_etkinlikler' => [
        'label' => 'Üye Etkinlikleri',
        'group' => 'Üye Modülleri',
        'category' => 'uye',
        'default' => false,
    ],
    'uye_toplantilar' => [
        'label' => 'Üye Toplantıları',
        'group' => 'Üye Modülleri',
        'category' => 'uye',
        'default' => false,
    ],
    'uye_izin_talepleri' => [
        'label' => 'Üye İzin Talepleri',
        'group' => 'Üye Modülleri',
        'category' => 'uye',
        'default' => false,
    ],
    'uye_harcama_talepleri' => [
        'label' => 'Üye Harcama Talepleri',
        'group' => 'Üye Modülleri',
        'category' => 'uye',
        'default' => false,
    ],
    'uye_iade_formu' => [
        'label' => 'Üye İade Formu',
        'group' => 'Üye Modülleri',
        'category' => 'uye',
        'default' => false,
    ],
];


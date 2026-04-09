<?php
/**
 * Onay Akışı Konfigürasyonu
 * 
 * Bu dosya sistemdeki onay akışlarını tanımlar.
 */

return [
    'approval_workflow' => [
        // İzin Talepleri: Tek Seviye Onay - Yasin Çakmak
        'izin_talepleri' => [
            'type' => 'single_level', // Tek seviye onay
            'approver_user_id' => null, // Migration script tarafından doldurulacak
            'approver_name' => 'Yasin Çakmak',
            'notify_on_request' => true,
            'notify_on_decision' => true,
        ],

        // Rezervasyon Talepleri: Tek Seviye Onay - İbrahim Çetin
        'rezervasyon_talepleri' => [
            'type' => 'single_level', // Tek seviye onay
            'approver_user_id' => null, // Manuel olarak İbrahim Çetin'in ID'si girilmeli
            'approver_name' => 'İbrahim Çetin',
            'notify_on_request' => true,
            'notify_on_decision' => true,
        ]
    ]
];
?>
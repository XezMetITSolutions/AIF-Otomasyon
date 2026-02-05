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

        // Harcama Talepleri: İki Seviye Onay - Yasin Çakmak → Muhammed Enes Sivrikaya
        'harcama_talepleri' => [
            'type' => 'two_level', // İki seviye onay
            'first_approver_user_id' => null, // Migration script tarafından doldurulacak
            'first_approver_name' => 'Yasin Çakmak',
            'second_approver_user_id' => null, // Migration script tarafından doldurulacak
            'second_approver_name' => 'Muhammed Enes Sivrikaya (AT Muhasebe Başkanı)',
            'notify_on_request' => true,
            'notify_on_first_approval' => true,
            'notify_on_final_decision' => true,
        ]
    ]
];
?>
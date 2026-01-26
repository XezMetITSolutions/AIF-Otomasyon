/**
 * Sistem Ayarları - SMTP Testi JS
 */
$(document).ready(function() {
    initSmtpTest();
});

// SPA (Ajax) geçişleri için tekrar başlatma
$(document).on('page:loaded', function() {
    initSmtpTest();
});

function initSmtpTest() {
    const btn = $('#btnTestMail');
    if (btn.length === 0) return;

    // Önceki event listener'ları temizle (tekrar eklenmesin)
    btn.off('click').on('click', function() {
        const email = $('#testEmailAddr').val();
        const statusDiv = $('#testMailStatus');

        if (!email) {
            statusDiv.html('<span class="text-danger">Lütfen bir e-posta adresi girin.</span>').show();
            return;
        }

        const formData = {
            test_email: email,
            smtp_host: $('input[name="smtp_host"]').val(),
            smtp_port: $('input[name="smtp_port"]').val(),
            smtp_user: $('input[name="smtp_user"]').val(),
            smtp_pass: $('input[name="smtp_pass"]').val(),
            smtp_secure: $('select[name="smtp_secure"]').val(),
            smtp_from_email: $('input[name="smtp_from_email"]').val(),
            smtp_from_name: $('input[name="smtp_from_name"]').val()
        };

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Gönderiliyor...');
        statusDiv.html('<span class="text-info">Bağlantı kuruluyor...</span>').show();

        $.ajax({
            url: '/admin/ajax_test_mail.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    statusDiv.html('<span class="text-success"><i class="fas fa-check-circle me-1"></i> ' + response.message + '</span>');
                } else {
                    statusDiv.html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> ' + response.message + '</span>');
                }
            },
            error: function() {
                statusDiv.html('<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Sistem hatası!</span>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Test Gönder');
            }
        });
    });
}

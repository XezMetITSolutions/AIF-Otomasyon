/**
 * AIF Otomasyon Sistemi - Ana JavaScript Dosyası
 * jQuery 3.7.1 ile uyumlu
 */

$(document).ready(function() {
    
    // CSRF Token'ı tüm formlara ekle
    $('form').each(function() {
        if (!$(this).find('input[name="csrf_token"]').length) {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            if (csrfToken) {
                $(this).append('<input type="hidden" name="csrf_token" value="' + csrfToken + '">');
            }
        }
    });
    
    // Tooltip'leri aktifleştir
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Popover'ları aktifleştir
    $('[data-bs-toggle="popover"]').popover();
    
    // Dropdown menüleri için click dışı kapatma
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
        }
    });
    
    // Form doğrulama
    $('form').on('submit', function(e) {
        const form = $(this);
        if (form.find('.is-invalid').length > 0) {
            e.preventDefault();
            return false;
        }
    });
    
    // Input değer değişikliğinde hata mesajlarını temizle
    $('.form-control, .form-select').on('input change', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    });
    
    // Tarih seçici formatı
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'dd.mm.yyyy',
            language: 'tr',
            autoclose: true,
            todayHighlight: true
        });
    }
    
    // Saat seçici formatı
    if ($.fn.timepicker) {
        $('.timepicker').timepicker({
            timeFormat: 'HH:mm',
            interval: 15,
            minTime: '00:00',
            maxTime: '23:59',
            defaultTime: '09:00',
            startTime: '00:00',
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });
    }
    
    // Otomatik kaybolan alert'ler
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
    
    // Confirm dialog'ları
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Bu işlemi gerçekleştirmek istediğinizden emin misiniz?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Data tablosu için özel işlevler (eğer DataTables kullanılıyorsa)
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
            },
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
    
    // Ajax form gönderimi
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const url = form.attr('action') || window.location.href;
        const method = form.attr('method') || 'POST';
        const formData = form.serialize();
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Loading durumu
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>İşleniyor...');
        
        $.ajax({
            url: url,
            method: method,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.message) {
                        showAlert('success', response.message);
                    }
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        form[0].reset();
                    }
                } else {
                    showAlert('danger', response.message || 'Bir hata oluştu.');
                    if (response.errors) {
                        displayFormErrors(form, response.errors);
                    }
                }
            },
            error: function(xhr) {
                showAlert('danger', 'Sunucu hatası. Lütfen tekrar deneyin.');
                console.error('AJAX Error:', xhr);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Form hata mesajlarını göster
    function displayFormErrors(form, errors) {
        $.each(errors, function(field, messages) {
            const input = form.find('[name="' + field + '"]');
            input.addClass('is-invalid');
            const errorMsg = Array.isArray(messages) ? messages[0] : messages;
            input.after('<div class="invalid-feedback">' + errorMsg + '</div>');
        });
    }
    
    // Alert göster
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('body').prepend(alertHtml);
        
        // Otomatik kaldır
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Global alert fonksiyonu
    window.showAlert = showAlert;
    
    // Sayfa yüklendiğinde fade-in animasyonu
    $('.fade-in').hide().fadeIn(500);
    
});

// Genel yardımcı fonksiyonlar
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}.${month}.${year} ${hours}:${minutes}`;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
}


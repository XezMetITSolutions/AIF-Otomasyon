<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'UserB') {
    die('Yetkisiz erişim');
}

$dataFile = 'submissions.json';

// Mevcut submissions'ı oku
$submissions = [];
if (file_exists($dataFile)) {
    $submissions = json_decode(file_get_contents($dataFile), true);
    if (!is_array($submissions)) {
        $submissions = [];
    }
}

echo "<h2>PDF Yeniden Oluşturma</h2>";
echo "<p>Toplam " . count($submissions) . " submission bulundu.</p>";

foreach ($submissions as $submission) {
    $id = $submission['id'] ?? '';
    $oldPdfPath = $submission['pdf_link'] ?? '';
    
    if ($oldPdfPath) {
        echo "<p>🔄 Submission ID: " . htmlspecialchars($id) . "</p>";
        echo "<p>📄 Eski PDF: " . htmlspecialchars($oldPdfPath) . "</p>";
        
        // Yeni PDF oluşturma için JavaScript çağrısı
        echo "<button onclick='regeneratePDF(\"$id\")'>Yeni PDF Oluştur</button><br><br>";
    }
}

echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js'></script>
<script>
async function regeneratePDF(submissionId) {
    if (confirm('Bu submission için yeni PDF oluşturulsun mu?')) {
        try {
            // Önce submission verisini al
            const response = await fetch('get_submission_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: submissionId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Yeni PDF oluştur
                const newPdfBlob = await generateNewPDF(data.submission);
                
                // PDF'i sunucuya gönder
                const formData = new FormData();
                formData.append('submission_id', submissionId);
                formData.append('pdf', newPdfBlob, 'new_gider_formu.pdf');
                
                const uploadResponse = await fetch('update_pdf.php', {
                    method: 'POST',
                    body: formData
                });
                
                const uploadResult = await uploadResponse.json();
                
                if (uploadResult.success) {
                    alert('✅ Yeni PDF başarıyla oluşturuldu!');
                    location.reload();
                } else {
                    alert('❌ PDF güncelleme hatası: ' + uploadResult.message);
                }
            } else {
                alert('❌ Veri alma hatası: ' + data.message);
            }
        } catch (error) {
            alert('❌ Bağlantı hatası!');
            console.error(error);
        }
    }
}

async function generateNewPDF(submission) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ compress: true, unit: 'pt', format: 'a4' });
    
    // Türkçe font ayarla
    doc.setFont('helvetica', 'normal');
    
    const margin = 20;
    const pageWidth = doc.internal.pageSize.getWidth();
    let yPos = margin;
    
    // Başlık
    doc.setFillColor(0, 152, 114);
    doc.rect(0, 0, pageWidth, 60, 'F');
    doc.setTextColor(255,255,255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(18);
    doc.text('AİF Gider Formu', margin, 35);
    
    // Üst bilgiler
    doc.setTextColor(20,20,20);
    yPos = 80;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(12);
    doc.text(`İsim: ${submission.isim || ''} ${submission.soyisim || ''}`, margin, yPos); yPos += 16;
    doc.text(`IBAN: ${submission.iban || ''}`, margin, yPos); yPos += 16;
    doc.text(`Toplam: ${submission.total || ''} €`, margin, yPos); yPos += 20;
    
    // Tablo
    const tableRows = [];
    if (submission.items) {
        submission.items.forEach(item => {
            tableRows.push([
                item.tarih || '-',
                item.region || '-',
                item.birim_label || item.birim || '-',
                item.gider_turu_label || item.gider_turu || '-',
                (item.tutar || 0) + ' €',
                item.aciklama || '-'
            ]);
        });
    }
    
    doc.autoTable({
        startY: yPos,
        head: [['Tarih','BYK','Birim','Tür','Tutar','Açıklama']],
        body: tableRows,
        styles: { font: 'helvetica', fontSize: 10 },
        headStyles: { fillColor: [0,152,114], textColor: 255 },
        margin: { left: margin, right: margin }
    });
    
    return doc.output('blob');
}
</script>";
?>

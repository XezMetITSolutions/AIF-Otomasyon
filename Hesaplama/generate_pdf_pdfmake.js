// pdfmake ile PDF oluşturma fonksiyonu
async function generateNewPDFFromDashboard(submission) {
    // pdfmake kontrolü
    if (!window.pdfMake) {
        throw new Error('pdfMake kütüphanesi yüklenemedi. Sayfayı yenileyin ve tekrar deneyin.');
    }
    
    // IBAN formatı
    function formatIBAN(iban) {
        if (!iban) return '';
        return iban.replace(/(.{4})/g, '$1 ').trim();
    }
    
    // Tarih formatı
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('tr-TR');
    }
    
    // Görsel URL'lerini topla (varsa)
    const attachmentImages = [];
    if (submission.items && submission.items.length > 0) {
        submission.items.forEach((item, index) => {
            if (item.attachments && item.attachments.length > 0) {
                item.attachments.forEach((attachment, attIndex) => {
                    // Sadece görselleri al
                    if (attachment.type && attachment.type.includes('image')) {
                        attachmentImages.push({
                            url: attachment.url,
                            name: attachment.name || `Fatura ${index + 1}-${attIndex + 1}`,
                            itemIndex: index
                        });
                    }
                });
            }
        });
    }
    
    // Tablo verilerini hazırla
    const tableBody = [];
    if (submission.items && submission.items.length > 0) {
        submission.items.forEach((item) => {
            tableBody.push([
                formatDate(item.tarih),
                item.region || '-',
                item.birim_label || item.birim || '-',
                item.gider_turu_label || item.gider_turu || '-',
                `${item.tutar || 0} €`,
                item.aciklama || '-'
            ]);
        });
    } else {
        tableBody.push(['-', '-', '-', '-', '-', 'Veri bulunamadı']);
    }
    
    const now = new Date();
    const dateStr = now.toLocaleDateString('tr-TR');
    const timeStr = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
    
    // pdfmake döküman tanımı
    const docDefinition = {
        pageSize: 'A4',
        pageMargins: [40, 60, 40, 60],
        content: [
            // Başlık
            {
                columns: [
                    {
                        width: '*',
                        stack: [
                            { text: 'AİF GİDER FORMU', style: 'header', color: '#009872' },
                            { text: 'Avusturya İslam Federasyonu', style: 'subheader' }
                        ]
                    },
                    {
                        width: 'auto',
                        stack: [
                            { text: `${dateStr} - ${timeStr}`, style: 'dateText', alignment: 'right' },
                            { text: 'Oluşturulma Tarihi', style: 'dateLabel', alignment: 'right' }
                        ]
                    }
                ],
                margin: [0, 0, 0, 20]
            },
            
            // Kişisel Bilgiler Kartı
            {
                table: {
                    widths: ['*'],
                    body: [
                        [{ text: 'BAŞVURAN BİLGİLERİ', style: 'cardHeader', fillColor: '#009872', color: 'white' }],
                        [{
                            stack: [
                                { text: [{ text: 'İsim Soyisim: ', bold: true }, `${submission.isim || ''} ${submission.soyisim || ''}`], margin: [0, 5, 0, 5] },
                                { text: [{ text: 'IBAN: ', bold: true }, formatIBAN(submission.iban || '')], margin: [0, 0, 0, 5] },
                                { text: [{ text: 'Toplam Tutar: ', bold: true }, { text: `${submission.total || '0'} €`, color: '#28a745', bold: true }], margin: [0, 0, 0, 5] }
                            ],
                            fillColor: '#f8f9fa',
                            margin: 10
                        }]
                    ]
                },
                layout: 'noBorders',
                margin: [0, 0, 0, 20]
            },
            
            // Gider Detayları Başlığı
            { text: 'GİDER DETAYLARI', style: 'sectionHeader', margin: [0, 0, 0, 10] },
            
            // Tablo
            {
                table: {
                    headerRows: 1,
                    widths: ['auto', 'auto', '*', 'auto', 'auto', '*'],
                    body: [
                        [
                            { text: 'Tarih', style: 'tableHeader' },
                            { text: 'BYK', style: 'tableHeader' },
                            { text: 'Birim', style: 'tableHeader' },
                            { text: 'Tür', style: 'tableHeader' },
                            { text: 'Tutar', style: 'tableHeader' },
                            { text: 'Açıklama', style: 'tableHeader' }
                        ],
                        ...tableBody
                    ]
                },
                layout: {
                    fillColor: function (rowIndex) {
                        return (rowIndex % 2 === 0) ? '#f8f9fa' : null;
                    },
                    hLineWidth: function (i, node) {
                        return (i === 0 || i === 1 || i === node.table.body.length) ? 1 : 0.5;
                    },
                    vLineWidth: function () {
                        return 0;
                    },
                    hLineColor: function (i) {
                        return i === 1 ? '#009872' : '#dee2e6';
                    }
                },
                margin: [0, 0, 0, 20]
            },
            
            // Özet
            {
                table: {
                    widths: ['*'],
                    body: [
                        [{
                            stack: [
                                {
                                    columns: [
                                        { text: [{ text: 'ÖZET - ', bold: true }, `Toplam Kalem Sayısı: ${submission.items ? submission.items.length : 0}`], width: '*' },
                                        { text: `GENEL TOPLAM: ${submission.total || '0'} €`, style: 'totalAmount', alignment: 'right', width: 'auto' }
                                    ]
                                }
                            ],
                            fillColor: '#e8f5f1',
                            margin: 10
                        }]
                    ]
                },
                layout: 'noBorders',
                margin: [0, 0, 0, 20]
            },
            
            // Fatura Görselleri (varsa)
            ...(attachmentImages.length > 0 ? [
                { text: 'FATURA GÖRSELLERİ', style: 'sectionHeader', pageBreak: 'before', margin: [0, 0, 0, 15] },
                ...attachmentImages.map((img, idx) => ({
                    stack: [
                        { text: img.name, style: 'imageCaption', margin: [0, 0, 0, 5] },
                        { 
                            image: img.url, 
                            width: 500, 
                            alignment: 'center',
                            margin: [0, 0, 0, idx < attachmentImages.length - 1 ? 20 : 0]
                        }
                    ]
                }))
            ] : [])
        ],
        footer: function(currentPage, pageCount) {
            return {
                columns: [
                    { text: 'Bu belge dijital ortamda oluşturulmuştur.', style: 'footer', alignment: 'left' },
                    { text: 'Avusturya İslam Federasyonu - Gider Yönetim Sistemi', style: 'footer', alignment: 'right' }
                ],
                margin: [40, 0]
            };
        },
        styles: {
            header: {
                fontSize: 22,
                bold: true,
                margin: [0, 0, 0, 5]
            },
            subheader: {
                fontSize: 12,
                color: '#6c757d'
            },
            dateText: {
                fontSize: 10,
                bold: true
            },
            dateLabel: {
                fontSize: 8,
                color: '#6c757d'
            },
            cardHeader: {
                fontSize: 12,
                bold: true,
                margin: [10, 10, 10, 10]
            },
            sectionHeader: {
                fontSize: 16,
                bold: true,
                color: '#212529'
            },
            tableHeader: {
                bold: true,
                fontSize: 11,
                color: 'white',
                fillColor: '#009872',
                margin: [5, 8, 5, 8]
            },
            totalAmount: {
                fontSize: 12,
                bold: true,
                color: '#28a745'
            },
            imageCaption: {
                fontSize: 11,
                bold: true,
                color: '#495057',
                alignment: 'center'
            },
            footer: {
                fontSize: 8,
                color: '#6c757d'
            }
        },
        defaultStyle: {
            fontSize: 10,
            font: 'Roboto'
        }
    };
    
    return new Promise((resolve, reject) => {
        try {
            const pdfDocGenerator = pdfMake.createPdf(docDefinition);
            pdfDocGenerator.getBlob((blob) => {
                resolve(blob);
            });
        } catch (error) {
            reject(error);
        }
    });
}


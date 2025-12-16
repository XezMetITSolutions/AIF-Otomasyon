# AIF Otomasyon Sistemi - FTP Upload Script
# PowerShell Script for Manual FTP Deployment

# FTP Bilgileri (Değiştirin)
$FTP_SERVER = "aifcrm.metechnik.at"
$FTP_USERNAME = "d0451622"

Write-Host "🚀 FTP Upload Başlatılıyor..." -ForegroundColor Green
Write-Host "📍 FTP Server: $FTP_SERVER" -ForegroundColor Cyan
Write-Host "👤 Username: $FTP_USERNAME" -ForegroundColor Cyan
Write-Host ""

# Şifreyi güvenli şekilde al
$securePassword = Read-Host "FTP Şifresini Girin" -AsSecureString
$BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
$FTP_PASSWORD = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)

# Yüklenecek dosyalar
$LOCAL_DIR = Get-Location
$REMOTE_DIR = "/"

# Exclude listesi
$EXCLUDE_PATTERNS = @(".git", ".github", "node_modules", ".env", "README.md", "DEPLOYMENT.md", "KONTROL_LISTESI.md", "database\schema.sql", ".gitignore", "*.log", ".vscode", ".idea")

Write-Host "📂 Local Directory: $LOCAL_DIR" -ForegroundColor Cyan
Write-Host ""

# FTP bağlantısı testi
$FTP_URI = "ftp://$FTP_SERVER"

try {
    $testRequest = [System.Net.FtpWebRequest]::Create($FTP_URI)
    $testRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USERNAME, $FTP_PASSWORD)
    $testRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $testRequest.UsePassive = $true
    
    $testResponse = $testRequest.GetResponse()
    $testResponse.Close()
    
    Write-Host "✅ FTP bağlantısı başarılı!" -ForegroundColor Green
    Write-Host ""
    
    # Dosyaları yükle
    $files = Get-ChildItem -Path $LOCAL_DIR -Recurse -File
    
    $uploaded = 0
    $skipped = 0
    
    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring($LOCAL_DIR.Path.Length + 1).Replace('\', '/')
        $shouldExclude = $false
        
        # Exclude kontrolü
        foreach ($pattern in $EXCLUDE_PATTERNS) {
            if ($relativePath -like "*$pattern*" -or $relativePath -match $pattern) {
                $shouldExclude = $true
                break
            }
        }
        
        if ($shouldExclude) {
            Write-Host "⏭️  Atlandı: $relativePath" -ForegroundColor Yellow
            $skipped++
            continue
        }
        
        try {
            $remotePath = $REMOTE_DIR.TrimEnd('/') + '/' + $relativePath
            $remoteDir = $remotePath.Substring(0, $remotePath.LastIndexOf('/'))
            
            # Dizini oluştur (gerekirse)
            try {
                $dirRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_SERVER$remoteDir")
                $dirRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USERNAME, $FTP_PASSWORD)
                $dirRequest.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
                $dirRequest.UsePassive = $true
                $dirResponse = $dirRequest.GetResponse()
                $dirResponse.Close()
            } catch {
                # Dizin zaten varsa hata vermez
            }
            
            # Dosyayı yükle
            $fileRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_SERVER$remotePath")
            $fileRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USERNAME, $FTP_PASSWORD)
            $fileRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $fileRequest.UseBinary = $true
            $fileRequest.UsePassive = $true
            
            $fileContent = [System.IO.File]::ReadAllBytes($file.FullName)
            $fileRequest.ContentLength = $fileContent.Length
            
            $requestStream = $fileRequest.GetRequestStream()
            $requestStream.Write($fileContent, 0, $fileContent.Length)
            $requestStream.Close()
            
            $response = $fileRequest.GetResponse()
            $response.Close()
            
            Write-Host "✅ Yüklendi: $relativePath" -ForegroundColor Green
            $uploaded++
        } catch {
            Write-Host "❌ Hata: $relativePath - $($_.Exception.Message)" -ForegroundColor Red
        }
    }
    
    Write-Host ""
    Write-Host "📊 Özet:" -ForegroundColor Cyan
    Write-Host "   ✅ Yüklenen: $uploaded dosya" -ForegroundColor Green
    Write-Host "   ⏭️  Atlanan: $skipped dosya" -ForegroundColor Yellow
    Write-Host "🎉 Upload tamamlandı!" -ForegroundColor Green
    
} catch {
    Write-Host "❌ FTP bağlantı hatası: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

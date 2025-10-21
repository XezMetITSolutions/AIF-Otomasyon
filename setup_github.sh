#!/bin/bash
# GitHub Repository Kurulum Scripti

echo "🚀 AIF Otomasyon - GitHub Repository Kurulumu"
echo "=============================================="

# Git repository başlat
echo "📁 Git repository başlatılıyor..."
git init

# .gitignore dosyası oluştur
echo "📝 .gitignore dosyası oluşturuluyor..."
cat > .gitignore << EOF
# Environment files
.env
.env.local
.env.production

# Debug files
admin/debug_login.php
admin/test_database.php
admin/install.php

# SQL files
admin/update_password.sql
admin/webhosting_*.sql
admin/database_*.sql

# Logs
logs/
*.log

# Uploads (opsiyonel)
uploads/

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Composer
vendor/
composer.lock

# Node modules
node_modules/
package-lock.json
yarn.lock

# Cache
cache/
tmp/
EOF

# İlk commit
echo "💾 İlk commit yapılıyor..."
git add .
git commit -m "Initial commit: AIF Otomasyon sistemi"

# Main branch'e geç
echo "🌿 Main branch oluşturuluyor..."
git branch -M main

echo ""
echo "✅ Git repository hazır!"
echo ""
echo "📋 Sonraki adımlar:"
echo "1. GitHub'da yeni repository oluştur"
echo "2. Repository URL'ini al"
echo "3. Aşağıdaki komutları çalıştır:"
echo ""
echo "git remote add origin https://github.com/KULLANICI_ADI/REPO_ADI.git"
echo "git push -u origin main"
echo ""
echo "4. GitHub Repository Settings → Secrets → Actions'a şunları ekle:"
echo "   FTP_SERVER: aifcrm.metechnik.at"
echo "   FTP_USERNAME: d0451622"
echo "   FTP_PASSWORD: 01528797Mb##"
echo ""
echo "5. Push ettiğinde otomatik deployment başlayacak!"
echo ""

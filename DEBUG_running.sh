#!/bin/bash
# ✅ Debug & Fix Script untuk WhatsApp Integration

echo "🔧 Debug Script untuk WhatsApp Integration"
echo "=========================================="

# Project paths
LARAVEL_PATH="c:/Users/Hype GLK/OneDrive/Desktop/Buket_cute/buketcute"
GATEWAY_PATH="c:/Users/Hype GLK/OneDrive/Desktop/Buket_cute/whatsapp-gateway"

echo ""
echo "📋 Step 1: Check Node.js Gateway"
echo "=================================="
echo "Gateway running? Check port 3000"
echo "Command: node index.js"
echo ""

echo "📋 Step 2: Prepare Laravel"
echo "=========================="
cd "$LARAVEL_PATH"

echo "🔄 Clearing Laravel Cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo ""
echo "🗄️ Running Database Migration..."
php artisan migrate

echo ""
echo "✅ Done! Now run:"
echo ""
echo "Terminal 1 (Node.js Gateway):"
echo "  cd whatsapp-gateway"
echo "  node index.js"
echo ""
echo "Terminal 2 (Laravel):"
echo "  cd buketcute"
echo "  php artisan serve"
echo ""
echo "Terminal 3 (Test):"
echo "  curl -X POST http://localhost:8000/api/whatsapp/receive \\"
echo "    -H 'Content-Type: application/json' \\"
echo "    -H 'X-API-Token: rahasia123' \\"
echo "    -d '{\"from\":\"6281234567890\",\"message\":\"test\",\"type\":\"text\",\"timestamp\":1703000000000}'"
echo ""

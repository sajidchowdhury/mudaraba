#!/bin/bash
# Quick refresh script: pull latest + reseed database
# Usage: bash refresh.sh
set -e

cd "$(dirname "$0")"

echo "=== Pulling latest code from GitHub ==="
git pull origin main

echo ""
echo "=== Running migrations + seeder in Docker ==="
docker compose exec app php artisan migrate:fresh --seed

echo ""
echo "=== Done! ==="
echo "App URL: http://localhost:8080"
echo "Login: E0001 / Mudaraba@2026"
echo ""
echo "Expected seeder output (now loads BOTH January + July 2026):"
echo "  Investors: 150"
echo "  Sectors: 16"
echo "  Total Investment (D181): 137,022,000  (from Jan 2026 data)"
echo ""
echo "  January 2026:"
echo "    Z2 (estimated): 1,535,000"
echo "    X2 (actual):    696,600"
echo ""
echo "  July 2026 (canonical reference — matches the Excel 'For Sajid' sheet):"
echo "    Z2 (estimated): 1,765,000"
echo "    X2 (actual):    1,635,000"
echo "    Y2 (variance):  130,000"
echo ""
echo "Try: /profit/investor?month=2026-07-01"

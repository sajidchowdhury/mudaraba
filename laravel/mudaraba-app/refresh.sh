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
echo "Expected seeder output:"
echo "  Investors: 158"
echo "  Sectors: 16"
echo "  Total Investment: 137,022,000"
echo "  Estimated Profit (D181): 1,535,000"
echo "  Actual Profit (Z2): 696,600"

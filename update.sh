#!/bin/bash
# Script update aplikasi setelah git push dari laptop
set -e
cd ~/dengue-ta
echo ">> Pull kode terbaru..."
git pull origin main
echo ">> Rebuild containers..."
docker compose up -d --build
echo ">> Jalankan migration..."
docker compose exec -T backend php artisan migrate --force
echo ">> Cache config..."
docker compose exec -T backend php artisan config:cache
echo ">> Update selesai!"
docker compose ps

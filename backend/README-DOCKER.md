# Docker Setup

## Start
```bash
cp .env.example .env
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan filament:assets
```

The backend will be available at http://localhost:6156
Admin panel: http://localhost:6156/admin

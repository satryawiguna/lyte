# lyte-webapi

## Project setup
```
cd into root project folder
run docker-compose up -d
composer install
docker exec -t -i lyte_app php artisan migrate
docker exec -t -i lyte_app php artisan passport:install
docker exec -t -i lyte_app php artisan db:seed
```

## Run unit test
```
php artisan test
```

## Documentation Api
```
Import file documentation on root project directory "Lyte.postman_collection.json" into POSTMAN
```

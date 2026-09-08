FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpq-dev libpng-dev libjpeg-dev libxml2-dev \
    libonig-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --from=node:20 /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20 /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

EXPOSE 8080
CMD php artisan config:cache && php artisan view:cache && php artisan migrate --force && php artisan db:seed --class=ProductionBaselineSeeder --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}

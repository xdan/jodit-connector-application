# Test runtime for the connector — PHP CLI + the extensions composer.json
# requires (gd/exif/mbstring/curl) + Composer. Lets you run the suite with no
# PHP installed on the host: `docker compose run --rm test`.
FROM php:8.2-cli

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git curl \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j"$(nproc)" gd exif zip \
	&& rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

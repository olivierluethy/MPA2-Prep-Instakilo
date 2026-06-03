# syntax=docker/dockerfile:1

###############################################################################
# Stage 1 — build frontend assets (Tailwind CSS + vendored Quill)
###############################################################################
FROM node:20-alpine AS assets

WORKDIR /build

# Install dependencies first for better layer caching.
COPY package.json package-lock.json* ./
RUN npm install

# Bring in the sources Tailwind needs to scan, then build.
COPY tailwind.config.js ./
COPY resources ./resources
COPY app ./app
COPY public ./public
COPY scripts ./scripts
RUN npm run build

###############################################################################
# Stage 2 — PHP + Apache runtime
###############################################################################
FROM php:8.2-apache AS app

# Extensions:
#   pdo_mysql — centralized database layer
#   mbstring  — multibyte string handling (validation, sanitizer excerpts)
#   dom is bundled in the official image and powers the HTML sanitizer.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Serve from public/ so application source is never web-accessible.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Reasonable upload limits (kept in sync with config/uploads.*).
RUN { \
      echo 'upload_max_filesize=8M'; \
      echo 'post_max_size=64M'; \
      echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

# Application source.
COPY app ./app
COPY config ./config
COPY public ./public
COPY resources ./resources

# Built assets from stage 1 (compiled CSS + vendored Quill).
COPY --from=assets /build/public/css/app.css ./public/css/app.css
COPY --from=assets /build/public/vendor ./public/vendor

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

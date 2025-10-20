###############################################
# EasyAppointments Dockerfile (Multi-stage)
# - Builds PHP deps (Composer)
# - Compiles assets (Node + Gulp)
# - Serves via Nginx + PHP-FPM (webdevops/php-nginx)
###############################################

# --- Composer stage -----------------------------------------------------------
FROM composer:2 AS composer
WORKDIR /app

# Install only PHP dependencies
COPY composer.json composer.lock ./
# Ignore only ext-gd during composer install in build stage.
# The runtime image provides the required PHP extension.
RUN composer install \
    --no-interaction \
    --no-dev \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader \
    --ignore-platform-req=ext-gd

# --- Assets stage -------------------------------------------------------------
FROM node:18 AS assets
WORKDIR /app

# Install front-end deps and compile assets
COPY package.json package-lock.json gulpfile.js ./
COPY assets ./assets
RUN npm ci && npx gulp compile

# --- Runtime stage ------------------------------------------------------------
# Contains Nginx + PHP-FPM in one container
FROM webdevops/php-nginx:8.2

# Document root is the repo root (index.php lives here)
ENV WEB_DOCUMENT_ROOT=/app
WORKDIR /app

# Copy application source
COPY . /app

# Bring in built vendor (PHP deps) and compiled assets
COPY --from=composer /app/vendor /app/vendor
COPY --from=assets /app/assets /app/assets

# Provide entrypoint to create config.php from env vars and fix permissions
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && mkdir -p /app/storage \
    && chmod -R 777 /app/storage

# Expose HTTP port (Nginx)
EXPOSE 80

# Use our entrypoint, then hand off to the base entrypoint
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord"]


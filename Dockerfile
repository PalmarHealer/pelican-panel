# syntax=docker.io/docker/dockerfile:1.13-labs
# Pelican Production Dockerfile

##
#  If you want to build this locally you want to run `docker build -f Dockerfile.dev`
##

# ================================
# Stage 1-1: Composer Install
# ================================
FROM --platform=$TARGETOS/$TARGETARCH localhost:5000/base-php:$TARGETARCH AS composer

WORKDIR /build

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy bare minimum to install Composer dependencies
COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts

# ================================
# Stage 1-2: Yarn Install
# ================================
FROM --platform=$TARGETOS/$TARGETARCH node:20-alpine AS yarn

WORKDIR /build

# Copy bare minimum to install Yarn dependencies
COPY package.json yarn.lock ./

RUN yarn config set network-timeout 300000 \
    && yarn install --frozen-lockfile

# ================================
# Stage 2-1: Composer Optimize
# ================================
FROM --platform=$TARGETOS/$TARGETARCH composer AS composerbuild

# Copy full code to optimize autoload
COPY --exclude=Caddyfile --exclude=docker/ . ./

RUN composer dump-autoload --optimize

# ================================
# Stage 2-2: Build Frontend Assets
# ================================
FROM --platform=$TARGETOS/$TARGETARCH yarn AS yarnbuild

WORKDIR /build

# Copy full code
COPY --exclude=Caddyfile --exclude=docker/ . ./
COPY --from=composer /build .

RUN yarn run build

# ================================
# Stage 5: Build Final Application Image
# ================================
FROM --platform=$TARGETOS/$TARGETARCH localhost:5000/base-php:$TARGETARCH AS final

WORKDIR /var/www/html

# Install additional required libraries
RUN apk add --no-cache \
    caddy ca-certificates supervisor supercronic fcgi

COPY --chown=root:www-data --chmod=640 --from=composerbuild /build .
COPY --chown=root:www-data --chmod=640 --from=yarnbuild /build/public ./public

# Set permissions
# First ensure all files are owned by root and restrict www-data to read access
RUN chown root:www-data ./ \
    && chmod 750 ./ \
    # Files should not have execute set, but directories need it
    && find ./ -type d -exec chmod 750 {} \; \
    # Create necessary directories
    && mkdir -p /pelican-data/storage /var/www/html/storage/app/public /var/run/supervisord /etc/supercronic \
    # Symlinks for env, database, and avatars
    && ln -s /pelican-data/.env ./.env \
    && ln -s /pelican-data/database/database.sqlite ./database/database.sqlite \
    && ln -sf /var/www/html/storage/app/public /var/www/html/public/storage \
    && ln -s  /pelican-data/storage/avatars /var/www/html/storage/app/public/avatars \
    && ln -s  /pelican-data/storage/fonts /var/www/html/storage/app/public/fonts \
    # Allow www-data write permissions where necessary
    && chown -R www-data:www-data /pelican-data ./storage ./bootstrap/cache /var/run/supervisord /var/www/html/public/storage \
    && chmod -R u+rwX,g+rwX,o-rwx /pelican-data ./storage ./bootstrap/cache /var/run/supervisord \
    && chown -R www-data: /usr/local/etc/php/ \
    # Create directories for extension system
    && mkdir -p /pelican-data/extensions \
    && ln -s /pelican-data/extensions ./extensions \
    && mkdir -p ./resources/views/extensions \
    && mkdir -p ./resources/css/themes \
    && mkdir -p ./public/extensions \
    && mkdir -p ./app/Filament/Admin/Pages/Extensions \
    && mkdir -p ./app/Filament/Admin/Resources/Extensions \
    && mkdir -p ./app/Filament/Admin/Widgets/Extensions \
    && mkdir -p ./app/Filament/App/Pages/Extensions \
    && mkdir -p ./app/Filament/App/Resources/Extensions \
    && mkdir -p ./app/Filament/App/Widgets/Extensions \
    && mkdir -p ./app/Filament/Server/Pages/Extensions \
    && mkdir -p ./app/Filament/Server/Resources/Extensions \
    && mkdir -p ./app/Filament/Server/Widgets/Extensions \
    && chown -R www-data:www-data ./extensions \
    && chown -R www-data:www-data ./resources/views/extensions ./resources/css/themes ./public/extensions ./lang \
    && chown -R www-data:www-data ./app/Filament/Admin/Pages/Extensions ./app/Filament/Admin/Resources/Extensions ./app/Filament/Admin/Widgets/Extensions \
    && chown -R www-data:www-data ./app/Filament/App/Pages/Extensions ./app/Filament/App/Resources/Extensions ./app/Filament/App/Widgets/Extensions \
    && chown -R www-data:www-data ./app/Filament/Server/Pages/Extensions ./app/Filament/Server/Resources/Extensions ./app/Filament/Server/Widgets/Extensions \
    && chmod -R u+rwX,g+rwX,o-rwx ./extensions \
    && chmod -R u+rwX,g+rwX,o-rwx ./resources/views/extensions ./resources/css/themes ./public/extensions ./lang \
    && chmod -R u+rwX,g+rwX,o-rwx ./app/Filament/Admin/Pages/Extensions ./app/Filament/Admin/Resources/Extensions ./app/Filament/Admin/Widgets/Extensions \
    && chmod -R u+rwX,g+rwX,o-rwx ./app/Filament/App/Pages/Extensions ./app/Filament/App/Resources/Extensions ./app/Filament/App/Widgets/Extensions \
    && chmod -R u+rwX,g+rwX,o-rwx ./app/Filament/Server/Pages/Extensions ./app/Filament/Server/Resources/Extensions ./app/Filament/Server/Widgets/Extensions

# Configure Supervisor
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/Caddyfile /etc/caddy/Caddyfile
# Add Laravel scheduler to crontab
COPY docker/crontab /etc/supercronic/crontab

COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/healthcheck.sh /healthcheck.sh

HEALTHCHECK --interval=5m --timeout=10s --start-period=5s --retries=3 \
  CMD /bin/ash /healthcheck.sh

EXPOSE 80 443

VOLUME /pelican-data

USER www-data

ENTRYPOINT [ "/bin/ash", "/entrypoint.sh" ]
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]

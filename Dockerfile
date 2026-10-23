# syntax=docker/dockerfile:1
# ---------------------------------------------------------------------------
# Ludex en production : FrankenPHP (Caddy + PHP 8.4) derrière le proxy de Coolify.
#
# Au démarrage (docker/entrypoint.sh) : attente de MySQL, migrations, catalogue de démo au premier
# lancement, clés JWT, puis serveur web sur le port 80. Réglages : variables d'environnement de Coolify.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4

WORKDIR /app

# Extensions PHP : MySQL, traductions, cache d'opcodes (sodium et openssl sont déjà dans l'image)
RUN install-php-extensions pdo_mysql intl opcache zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-ludex.ini"
COPY docker/Caddyfile /etc/caddy/Caddyfile

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    TRUSTED_PROXIES=private_ranges

# Dépendances d'abord : cette couche reste en cache tant que composer.lock ne change pas
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

COPY . .

# APP_SECRET factice le temps du build : le vrai n'est lu qu'à l'exécution, jamais écrit dans l'image
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && APP_SECRET=build composer run-script --no-dev post-install-cmd \
    && mkdir -p var/share config/jwt \
    && chmod +x docker/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

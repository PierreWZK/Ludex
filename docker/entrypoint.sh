#!/bin/sh
# ---------------------------------------------------------------------------
# Démarrage du conteneur Ludex : prépare l'appli puis lance le serveur web (CMD du Dockerfile).
# Une commande passée au conteneur est exécutée telle quelle.
# ---------------------------------------------------------------------------
set -e

if [ "$1" = 'frankenphp' ]; then
    mkdir -p var/share config/jwt

    # MySQL peut démarrer après l'appli : on l'attend jusqu'à une minute
    tries=30
    until php bin/console dbal:run-sql -q 'SELECT 1' > /dev/null 2>&1; do
        tries=$((tries - 1))
        if [ "$tries" -le 0 ]; then
            echo 'MySQL ne répond pas : vérifiez DATABASE_URL.' >&2
            php bin/console dbal:run-sql 'SELECT 1'
            exit 1
        fi
        echo 'En attente de MySQL…'
        sleep 2
    done

    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

    # Premier démarrage : catalogue vide, on charge le catalogue et le compte de démo (jamais par-dessus des données)
    if php bin/console dbal:run-sql 'SELECT COUNT(*) AS n FROM game' | grep -qE '^\s*0\s*$'; then
        echo 'Chargement du catalogue et du compte de démo.'
        php bin/console doctrine:fixtures:load --append --no-interaction
    fi

    # Clés de signature des jetons, chiffrées par JWT_PASSPHRASE : créées au premier démarrage du conteneur.
    # Un redéploiement en crée de nouvelles ; les jetons en cours expirent alors, il suffit de se reconnecter.
    php bin/console lexik:jwt:generate-keypair --skip-if-exists

    php bin/console cache:warmup

    # Worker des synchronisations Steam, à côté du serveur web : il se relance toutes les heures
    # (ou après 50 synchros) pour libérer la mémoire, et redémarre s'il s'arrête sur une erreur
    (while true; do
        php bin/console messenger:consume async --time-limit=3600 --limit=50 --memory-limit=192M --no-interaction -q || sleep 10
    done) &
fi

exec "$@"

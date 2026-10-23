# Ludex · L'index de votre ludothèque, en API

> Jour 11 du défi **30 jours, 30 projets** · Symfony · API Platform · MySQL · 🚧 **V1 en cours**

Une **API REST** pour gérer sa collection de jeux vidéo : plateformes, état, temps de jeu, note, liste d'envies. **Ludex** est documentée (OpenAPI), sécurisée par jeton, et livrée avec une page de démo qui l'appelle en direct. Elle se branchera ensuite sur Steam, puis sur d'autres plateformes.

📄 Les règles détaillées sont dans le [cahier des charges](CAHIER_DES_CHARGES.md).

![La page de démo de Ludex](docs/preview.png)

## Feuille de route

### V1 · l'API de collection (🚧 en cours)

- Catalogue commun en lecture libre : `Platform`, `Genre`, `Game` (54 jeux de démo).
- Par joueur : `CollectionItem` (statut : à faire, en cours, fini, abandonné ; temps de jeu ; note sur 10) et `WishlistItem` (priorité 1 à 3).
- CRUD complet, pagination de 20, filtres combinables (statut, plateforme, genre, titre) et tri.
- Inscription et connexion par jeton (JWT) ; chacun ne voit que sa collection (404 sur celle d'un autre).
- Statistiques : nombre de jeux, temps total, note moyenne, répartition par statut et par plateforme.
- Erreurs au format Problem Details, en français.
- Documentation interactive : Swagger sur `/api/docs`.
- Page de démo sur `/`, branchée sur le compte de démo.

### V2 · Steam (prévue)

- Connexion du compte par **« Se connecter via Steam »** (OpenID) : aucun partage de données à accepter.
- Import des jeux, du temps de jeu et des succès (profil et « Détails des jeux » publics).
- Liste des succès à faire par jeu, triés par rareté.
- **Platines** : 100 % des succès d'un jeu.
- **Récompenses** : badges, cadres et titres débloqués par paliers (succès, platines, jeux, heures).

### V3 · autres plateformes (à étudier)

Xbox, PlayStation, RetroAchievements… seulement celles dont les données se récupèrent de façon fiable et autorisée.

## Les routes

| Méthode | Route | Accès |
| --- | --- | --- |
| `POST` | `/api/register` | libre (5 inscriptions par heure et par IP) |
| `POST` | `/api/auth` | libre (5 essais par minute) : renvoie `{"token"}` |
| `GET` | `/api/me`, `/api/stats` | jeton |
| `GET` | `/api/games?title=&platform=&genre=&order[title]=`, `/api/platforms`, `/api/genres` | libre |
| `POST` `PATCH` `DELETE` | `/api/games`, `/api/platforms` | administrateur |
| `GET` `POST` | `/api/collection_items?status=&platform=&genre=&title=&order[playtimeMinutes]=desc` | jeton |
| `GET` `PATCH` `DELETE` | `/api/collection_items/{id}` | jeton, propriétaire |
| `GET` `POST` | `/api/wishlist_items?priority=&order[addedAt]=` | jeton |
| `GET` `PATCH` `DELETE` | `/api/wishlist_items/{id}` | jeton, propriétaire |

Compte de démo : `demo@ludex.fr` / `ludex-demo`.

```bash
curl -s -X POST http://127.0.0.1:8000/api/auth -H 'Content-Type: application/json' \
     -d '{"email":"demo@ludex.fr","password":"ludex-demo"}'
curl -s 'http://127.0.0.1:8000/api/collection_items?status=finished&platform=ps5' -H "Authorization: Bearer $TOKEN"
```

## Lancer en local

PHP 8.4 avec les extensions `pdo_mysql` (ou `pdo_sqlite`), `intl`, `openssl` et `sodium`.

```bash
composer install
# .env.local (jamais versionné) : DATABASE_URL vers MySQL, ou SQLite pour essayer :
#   DATABASE_URL="sqlite:///%kernel.project_dir%/var/dev.db"
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate      # MySQL ; avec SQLite : doctrine:schema:create
php bin/console doctrine:fixtures:load
symfony serve -d                                 # http://127.0.0.1:8000, Swagger sur /api/docs
```

Sous Windows, si la génération des clés échoue (`error:80000003`), indiquez à OpenSSL son fichier de configuration :
`OPENSSL_CONF=C:/php/extras/ssl/openssl.cnf`.

## Tests

```bash
php bin/phpunit
```

12 tests fonctionnels (ApiTestCase) sur une base SQLite recréée à chaque test : catalogue public, jeton obligatoire, isolation des collections (lecture, modification, suppression), filtres combinés, validation et Problem Details, doublons, statistiques, inscription, droits d'administration.

## Mise en ligne

Prévue sur Coolify : **https://ludex.projectp.fr**, avec le `Dockerfile` (FrankenPHP) et une base MySQL 8.4.

Variables d'environnement :

| Variable | Valeur |
| --- | --- |
| `DATABASE_URL` | `mysql://…@…:3306/ludex?serverVersion=8.4.0&charset=utf8mb4` |
| `APP_SECRET` | une chaîne aléatoire |
| `JWT_PASSPHRASE` | une phrase secrète, qui chiffre les clés de signature |

Au démarrage, le conteneur attend MySQL, joue les migrations, charge le catalogue et le compte de démo si la base est vide, puis crée les clés JWT s'il n'en a pas.

## Stack

Symfony 8.1 · API Platform 5 · Doctrine ORM · MySQL 8.4 · lexik/jwt-authentication-bundle · FrankenPHP. Steam Web API en V2.

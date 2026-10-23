# Jour 11/30 — Ludex, l'index de votre ludothèque, en API

> Stack : **Symfony 8**, **API Platform 5**, **MySQL** · hébergement **Coolify**, sous-domaine `ludex.projectp.fr`.
> Durée visée : **2 h 45** pour la V1 ; la V2 (Steam) et la V3 (autres plateformes) viennent ensuite.

Ce document est notre contrat : on le suit étape par étape, et toute décision qui s'en écarte est d'abord notée ici. Il s'ajuste au début de chaque version.

## 1. L'idée

Une **API REST** pour gérer sa collection de jeux vidéo : plateformes, état, temps de jeu, note, liste d'envies. **Ludex** est documentée (OpenAPI), sécurisée par jeton, et livrée avec une petite page de démo.

Ensuite, Ludex se branche sur les comptes de jeu : Steam d'abord (jeux joués, temps de jeu, succès, platines, récompenses), puis d'autres plateformes.

## 2. Feuille de route

| Version | Contenu | État |
| --- | --- | --- |
| **V1** | L'API de collection : jeux, plateformes, collection, liste d'envies, statistiques, JWT, Swagger, démo | 🚧 En cours |
| **V2** | Steam : connexion du compte, synchronisation, succès à faire, platines, récompenses | 🚧 Codée, en attente de la clé Steam |
| **V3** | Autres plateformes : étude puis connexion de celles qui le permettent | À étudier |

---

## 3. V1 — l'API de collection

### 3.1 Ce qui doit fonctionner à la fin

1. Ressources `Platform`, `Game`, `CollectionItem` (statut : à faire, en cours, fini, abandonné) et `WishlistItem`.
2. CRUD complet, pagination, filtres (plateforme, statut, genre) et tri.
3. Authentification par jeton (JWT) : chaque utilisateur ne voit que sa collection et sa liste d'envies.
4. Documentation interactive (Swagger UI d'API Platform).
5. Statistiques : nombre de jeux, temps total, répartition par plateforme et par statut.
6. Une page de démo qui se connecte avec le compte de démo et appelle l'API.
7. En ligne sur Coolify, avec un README à jour et un aperçu de lien (Open Graph).

### 3.2 Modèle de données

- **User** : e-mail, mot de passe haché, rôles.
- **Platform** : nom, constructeur, slug. Catalogue commun, en lecture pour tous.
- **Game** : titre, année de sortie, genres, plateformes disponibles, image. Catalogue commun, en lecture pour tous ; écriture réservée à l'administrateur.
- **CollectionItem** : propriétaire, jeu, plateforme, statut (énumération PHP), temps de jeu (minutes), note sur 10, notes libres, dates d'ajout et de fin.
- **WishlistItem** : propriétaire, jeu, priorité (1 à 3), note libre. Ressource à part, pas un statut de la collection.

### 3.3 Règles fonctionnelles

**Validation**

- Note de 0 à 10, temps de jeu positif ou nul.
- La plateforme choisie doit exister et faire partie des plateformes du jeu.
- Un même jeu ne peut figurer qu'une fois par plateforme dans une collection, et une seule fois dans la liste d'envies.
- Erreurs au format standard (Problem Details, `application/problem+json`).

**Sécurité**

- Les listes sont filtrées sur l'utilisateur connecté (extension Doctrine) ; le propriétaire est posé automatiquement à la création.
- Un voter par ressource : lire, modifier ou supprimer l'élément d'un autre renvoie 404 (on ne révèle pas qu'il existe).
- Limite de débit sur la connexion (5 essais par minute).
- Clés JWT fournies par l'environnement, jamais versionnées.

### 3.4 Cahier des charges technique

- API Platform 5 : ressources déclarées par attributs, groupes de sérialisation.
- Filtres par paramètres (`ExactFilter`, `PartialSearchFilter`, `SortFilter`), pagination de 20 par défaut.
- `GET /api/stats` : opération dédiée, calculée en quelques requêtes groupées.
- `POST /api/auth` : connexion, renvoie le jeton (lexik/jwt-authentication-bundle).
- Tests fonctionnels de l'API (ApiTestCase).
- Fixtures : un compte de démo et une cinquantaine de jeux sur les plateformes courantes.
- Dockerfile FrankenPHP et base MySQL sur Coolify (comme le jour 9), mise en ligne à chaque push sur la branche principale.

### 3.5 Hors V1

- Aucune connexion à un compte de jeu (V2 et V3).
- Pas d'interface complète : la page de démo appelle l'API.

### 3.6 Critères de réussite

- [x] Chaque route est documentée dans Swagger (connexion comprise).
- [x] Un utilisateur ne peut ni lire ni modifier la collection ou la liste d'envies d'un autre (testé).
- [x] Les filtres se combinent (testé).
- [x] Une note hors de 0 à 10 ou une plateforme invalide renvoie une erreur Problem Details (testé).
- [x] La page de démo affiche la collection et les statistiques du compte de démo.

---

## 4. V2 — Steam

### 4.1 Objectif

Lier son compte Steam pour importer ses jeux, son temps de jeu et ses succès, suivre les succès qui restent à faire, décrocher des platines et débloquer des récompenses.

### 4.2 Connexion du compte

- Bouton **« Se connecter via Steam »** (OpenID 2.0 de Steam) : le joueur se connecte chez Steam et revient avec son SteamID64 prouvé. Aucun écran de partage de données, aucune application à faire valider.
- On ne lie un compte Steam qu'une fois prouvé : une simple adresse de profil permettrait d'importer le profil de quelqu'un d'autre et de tricher sur les récompenses.
- Le profil doit être **public**, ainsi que les **« Détails des jeux »** (réglage séparé, « Amis uniquement » par défaut). Le temps de jeu peut aussi être masqué à part : Ludex explique quoi cocher quand une donnée manque.

### 4.3 Données récupérées (Steam Web API)

Une clé d'API gratuite, liée au compte Steam du projet, gardée côté serveur (`STEAM_API_KEY`).

| Appel | Pour quoi |
| --- | --- |
| `ISteamUser/GetPlayerSummaries` | Pseudo, avatar, visibilité du profil |
| `IPlayerService/GetOwnedGames` | Jeux possédés, temps de jeu total et récent, dernière partie |
| `ISteamUserStats/GetSchemaForGame` | Liste des succès d'un jeu : nom, description, icônes, succès cachés |
| `ISteamUserStats/GetPlayerAchievements` | Succès obtenus par le joueur et leur date (`l=french`) |
| `ISteamUserStats/GetGlobalAchievementPercentagesForApp` | Rareté de chaque succès |

- Limite Steam : 100 000 appels par jour et par clé.
- Il faut un appel `GetPlayerAchievements` **par jeu** : la synchronisation tourne en tâche de fond (Symfony Messenger), seulement sur les jeux réellement joués, et le résultat est gardé en base. Resynchronisation à la demande, pas plus d'une fois par heure.
- Les jeux Steam alimentent le catalogue (`appid`, nom, image d'en-tête).

### 4.4 Succès à faire et platines

- **Succès à faire** d'un jeu : tous ses succès moins ceux obtenus, triés par rareté (du plus courant au plus rare).
- **Platine** (définition Ludex, Steam n'en a pas) : 100 % des succès d'un jeu, datée du dernier succès obtenu.
- Cas particuliers :
  - les jeux sans succès ne comptent pas pour les platines ;
  - la rareté de chaque succès est renvoyée (`rarity`, en %) : un succès à 0 % se repère tout de suite ;
  - la date de la première platine est gardée.
- Limite connue : le schéma des succès d'un jeu n'est chargé qu'une fois. Si un jeu ajoute des succès plus tard, Ludex ne le voit pas encore (à faire : recharger le schéma périodiquement).

### 4.5 Récompenses

- Paliers définis dans le code (`App\Reward\Rewards`) : 1, 10, 100 jeux ; 100 h sur un même jeu ; 1 000 h au total ; 1, 100, 1 000 succès ; 1, 5, 25 platines ; un succès obtenu par moins de 1 % des joueurs.
- Chaque déblocage est enregistré avec sa date et donne un cosmétique : badge, cadre d'avatar ou titre de profil.
- Les récompenses de succès et de platines ne viennent que de comptes Steam prouvés par OpenID ; celles de collection (jeux, heures) comptent aussi les jeux saisis à la main.

### 4.6 Critères de réussite

- [x] Un compte Steam se lie par OpenID et ne peut pas être lié à deux comptes Ludex.
- [x] Un profil privé ou aux détails masqués renvoie un message qui dit quoi changer.
- [x] La liste des succès à faire et le pourcentage par jeu sont justes (testé avec des réponses Steam enregistrées).
- [x] Une platine et une récompense se débloquent au bon moment (testé).

---

## 5. V3 — autres plateformes (à étudier)

À vérifier avant de s'engager : chaque plateforme a ses propres règles, et certaines n'ont pas d'API publique.

| Plateforme | Piste | Point d'attention |
| --- | --- | --- |
| Xbox | API officielle réservée aux partenaires ; service tiers OpenXBL | Dépendance à un tiers, connexion du joueur nécessaire |
| PlayStation | Pas d'API publique ; bibliothèques non officielles (jeton NPSSO) | Fragile, conditions d'utilisation à vérifier |
| RetroAchievements | API publique officielle avec clé | Succès et maîtrises (équivalent des platines) |
| GOG, Epic, Nintendo | Pas d'API publique utilisable pour les succès | Saisie manuelle dans la collection |

Une plateforme n'entre dans Ludex que si ses données se récupèrent de façon fiable et autorisée.

---

## 6. Bonus facultatifs

- Import du catalogue depuis RAWG.
- GraphQL.
- Export CSV de la collection.

## 7. Définition du « terminé »

**V1** : le MVP fonctionne, les critères de la section 3.6 sont cochés, le site est en ligne sur `ludex.projectp.fr`, le README a son lien et sa capture, et la carte du projet passe à « terminé » sur la vitrine.

**V2** : les critères de la section 4.6 sont cochés et le README décrit la connexion Steam.

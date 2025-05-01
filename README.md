# Football Application Backend

Ce projet utilise Symfony pour fournir une API RESTful pour la gestion des joueurs de football.

## Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL/MariaDB
- Symfony CLI (recommandé pour le développement)

## Installation

1. Clonez le dépôt et accédez au répertoire du backend :

```bash
git clone <url-du-repo>
cd backend
```

2. Installez les dépendances avec Composer :

```bash
composer install
```

3. Configurez votre fichier `.env` avec vos paramètres de base de données :

```bash
# .env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/football?serverVersion=8.0&charset=utf8mb4"

```

4. Créez la base de données :

```bash
php bin/console doctrine:database:create
```

5. Exécutez les migrations :

```bash
php bin/console doctrine:migrations:migrate
```

6. Generation de la clé public et privée ([LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/3.x/Resources/doc/index.rst#installation/))
```bash
php bin/console lexik:jwt:generate-keypair
```

## Création d'utilisateurs via la ligne de commande

Pour créer un utilisateur administrateur, utilisez la commande suivante :

```bash
php bin/console app:create-user <email> <password> <roles>
```

Exemples :

```bash
# Créer un administrateur
php bin/console app:create-user admin@example.com monMotDePasse ROLE_ADMIN

# Créer un utilisateur standard
php bin/console app:create-user user@example.com monMotDePasse ROLE_USER

# Créer un super admin (avec plusieurs rôles)
php bin/console app:create-user superadmin@example.com monMotDePasse "ROLE_SUPER_ADMIN,ROLE_ADMIN"
```

## Lancement du serveur en développement avec HTTPS

```bash
symfony server:start --allow-http --https

```

Le serveur sera accessible à l'adresse `https://localhost:8000`.

## Endpoints API

### Authentification

- `POST /api/login_check` - Obtenir un token JWT

### Joueurs

- `GET /api/players` - Liste de tous les joueurs
- `GET /api/players/{id}` - Détails d'un joueur spécifique
- `POST /api/players` - Créer un nouveau joueur
- `PUT /api/players/{id}` - Mettre à jour un joueur existant
- `DELETE /api/players/{id}` - Supprimer un joueur
- `POST /api/players/import` - Importer des joueurs depuis un fichier Excel

## Importation de joueurs

Pour importer des joueurs depuis un fichier Excel (.xlsx), utilisez l'endpoint `/api/players/import` avec un fichier ayant les colonnes suivantes :

1. Prénom
2. Nom
3. Poste
4. Équipe
5. Âge

Options d'importation :
- `persistInDatabase=true` - Enregistrer les joueurs valides en base de données
- `persistInDatabase=false` - Valider seulement sans enregistrer (comportement par défaut)

## Screenshots

Voici quelques captures d'écran des fonctionnalités de l'API:

### Authentification

![API Login](./screenshots/api-login.png)

### Liste des joueurs

![API Liste](./screenshots/api-liste.png)

### Détails d'un joueur

![API Detail](./screenshots/api-detail.png)

### Création d'un joueur

![API Create](./screenshots/api-create.png)

### Mise à jour d'un joueur

![API Update](./screenshots/api-update.png)

### Suppression d'un joueur

![API Delete](./screenshots/api-delete.png)

### Importation de joueurs via Excel

![API Import](./screenshots/api-import.png)



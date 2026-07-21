# Omano

Plateforme SaaS Omano — API Laravel + Frontend Next.js

## Prérequis

- PHP 8.2+
- Composer 2
- Node.js 20+
- npm 10+
- PostgreSQL 17+

## Installation de PostgreSQL

### Windows

1. Télécharger l'installateur auprès de https://www.postgresql.org/download/windows/
2. Installer PostgreSQL en version stable (17+)
3. Le serveur tourne par défaut sur `localhost:5432`

### macOS

```bash
brew install postgresql@17
brew services start postgresql@17
```

### Linux (Debian/Ubuntu)

```bash
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
sudo apt-get update
sudo apt-get install -y postgresql-17
```

## Création de la base de données

```bash
# Se connecter en tant que superuser postgres
psql -U postgres

# Créer l'utilisateur et la base
CREATE USER omano WITH PASSWORD 'secret';
CREATE DATABASE omano OWNER omano;
\q
```

## Configuration

### API (Laravel)

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Web (Next.js)

```bash
cd web
npm install
cp .env.local.example .env.local
```

## Lancement en développement

### API Laravel

```bash
cd api
php artisan serve
```

L'API sera disponible sur `http://localhost:8000`

### Frontend Next.js

```bash
cd web
npm run dev
```

Le frontend sera disponible sur `http://localhost:3000`

## Endpoints

- `GET /api/v1/health` — Vérification de l'état du déploiement

## Tests

### Laravel

```bash
cd api
php artisan test
```

### Next.js

```bash
cd web
npm run build
```

## Structure du projet

```
.
├── api/                    # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/
│   │   ├── Models/
│   │   └── Modules/
│   │       ├── Auth/
│   │       └── Providers/
│   ├── config/
│   ├── database/
│   ├── routes/
│   │   └── api.php         # Routes API versionnées
│   └── tests/
├── web/                    # Next.js Frontend
│   ├── src/
│   │   ├── app/            # App Router
│   │   └── lib/
│   │       └── api.ts      # Client API typé
│   └── package.json
└── README.md
```

## Environnement de développement

- Cache et files d'attente : driver `database` (pas de Redis)
- Authentification : Laravel Sanctum
- Base de test : SQLite en mémoire
- CI : GitHub Actions (tests Laravel + build Next.js)

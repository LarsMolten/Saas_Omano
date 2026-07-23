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

### Public
- `GET /api/v1/health` — Health check
- `GET /api/v1/providers/{id}/services` — List provider services
- `GET /api/v1/providers/{id}/portfolio` — List provider portfolio
- `GET /api/v1/search` — Search providers (full-text, filters, pagination)

### Auth (JWT)
- `POST /api/v1/auth/register` — Register (rate limited 5/min)
- `POST /api/v1/auth/login` — Login (rate limited 5/min)
- `POST /api/v1/auth/logout` — Logout
- `POST /api/v1/auth/refresh` — Refresh token
- `POST /api/v1/auth/forgot-password` — Request password reset
- `POST /api/v1/auth/reset-password` — Reset password
- `POST /api/v1/auth/verify-email` — Verify email
- `POST /api/v1/auth/send-email-verification` — Send verification email
- `POST /api/v1/auth/verify-phone` — Verify phone
- `POST /api/v1/auth/send-phone-verification` — Send SMS code

### Prestataire only (JWT + role:prestataire)
- `POST /api/v1/providers/{id}/services` — Create service
- `POST /api/v1/providers/{id}/portfolio` — Create portfolio item
- `PATCH /api/v1/services/{id}` — Update service
- `DELETE /api/v1/services/{id}` — Delete service
- `PATCH /api/v1/portfolio/{id}` — Update portfolio item
- `DELETE /api/v1/portfolio/{id}` — Delete portfolio item

### Favorites (JWT)
- `GET /api/v1/favorites` — List user favorites
- `POST /api/v1/favorites` — Add provider to favorites
- `DELETE /api/v1/favorites/{provider_id}` — Remove from favorites

### Quotes (JWT)
- `GET /api/v1/quotes` — List quotes (clients: own requests, providers: received requests)
- `POST /api/v1/quotes` — Create quote request (client only, rate limited 10/day)
- `PATCH /api/v1/quotes/{id}/respond` — Respond to quote (provider only, status: accepted/declined/answered)

### Search parameters
- `q` — Free text search (name, bio, city, category via pg_trgm + tsvector)
- `category` — Filter by category (partial match)
- `city` — Filter by city name
- `lat`/`lng`/`radius` — Haversine radius search (km, default 25)
- `price_min`/`price_max` — Price range on provider services
- `rating_min` — Minimum average rating
- `verified` — true/false filter on email_verified_at
- `page`/`per_page` — Pagination (default 20)

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
│   │   │   ├── AuthController.php
│   │   │   ├── FavoriteController.php
│   │   │   ├── HealthController.php
│   │   │   ├── PortfolioController.php
│   │   │   ├── QuoteController.php
│   │   │   ├── SearchController.php
│   │   │   └── ServiceController.php
│   │   ├── Http/Middleware/
│   │   │   ├── CheckRole.php
│   │   │   └── JwtAuthenticate.php
│   │   ├── Jobs/
│   │   │   └── ProcessPortfolioImage.php
│   │   ├── Models/
│   │   │   ├── Favorite.php
│   │   │   ├── PortfolioItem.php
│   │   │   ├── PortfolioMedia.php
│   │   │   ├── QuoteRequest.php
│   │   │   ├── Service.php
│   │   │   ├── ServiceOption.php
│   │   │   └── User.php
│   │   └── Modules/
│   │       ├── Auth/
│   │       └── Providers/
│   ├── config/
│   ├── database/
│   │   ├── factories/
│   │   └── migrations/
│   ├── routes/
│   │   └── api.php
│   └── tests/Feature/Api/V1/
│       ├── Auth*.php (6 test files)
│       ├── CheckRoleTest.php
│       ├── FavoriteTest.php
│       ├── PortfolioCrudTest.php
│       ├── QuoteTest.php
│       ├── SearchTest.php
│       └── ServiceCrudTest.php
├── web/                    # Next.js Frontend
│   ├── src/
│   │   ├── app/
│   │   │   ├── api/auth/    # Auth API routes
│   │   │   ├── api/v1/search/ # Search proxy
│   │   │   ├── login/
│   │   │   ├── register/
│   │   │   ├── recherche/   # Search page (SSR)
│   │   │   └── ...
│   │   ├── components/
│   │   │   ├── portfolio/   # PortfolioForm, PortfolioList, PortfolioSection
│   │   │   ├── search/      # SearchPage, SearchFiltersBar, ProviderCard
│   │   │   └── services/    # ServiceForm, ServiceList
│   │   └── lib/
│   │       ├── api.ts
│   │       └── types/
│   │           ├── portfolio.ts
│   │           ├── search.ts
│   │           └── service.ts
│   └── package.json
└── README.md
```

## Environnement de développement

- Cache et files d'attente : driver `database` (pas de Redis)
- Authentification : JWT (tymon/jwt-auth)
- Base de test : PostgreSQL (omano_test)
- CI : GitHub Actions (tests Laravel + build Next.js)

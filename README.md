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

### Reviews
- `GET /api/v1/providers/{id}/reviews` — List provider reviews (public, published only)
- `POST /api/v1/reviews` — Create review (client only, one per provider, rate limited)
- `PATCH /api/v1/reviews/{id}` — Edit own review (within 48h)
- `POST /api/v1/reviews/{id}/report` — Report a review (any authenticated user)

### Notifications (JWT)
- `GET /api/v1/notifications` — List user notifications (paginated)
- `PATCH /api/v1/notifications/{id}/read` — Mark notification as read
- `PATCH /api/v1/notifications/read-all` — Mark all as read

### Subscriptions (JWT)
- `GET /api/v1/subscriptions/plans` — List available plans (public)
- `POST /api/v1/subscriptions/checkout` — Create pending subscription (checkout flow)
- `GET /api/v1/subscriptions/current` — Get current active subscription

### Payments (JWT)
- `POST /api/v1/payments/initiate` — Initiate payment (Mvola, Orange Money, Airtel Money)
- `POST /api/v1/payments/webhook/{operator}` — Payment webhook (public, signature-verified)
- `GET /api/v1/payments/{id}/status` — Check payment status

### Provider Stats (JWT + prestataire)
- `GET /api/v1/my/stats` — Get own stats (basic 7d free, 30d/12m requires Pro/Premium)
- `GET /api/v1/providers/{id}/stats` — Get provider stats (owner only)

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
│   │   │   ├── NotificationController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── PortfolioController.php
│   │   │   ├── QuoteController.php
│   │   │   ├── ReviewController.php
│   │   │   ├── SearchController.php
│   │   │   ├── ServiceController.php
│   │   │   ├── StatsController.php
│   │   │   └── SubscriptionController.php
│   │   ├── Http/Middleware/
│   │   │   ├── CheckRole.php
│   │   │   └── JwtAuthenticate.php
│   │   ├── Contracts/
│   │   │   ├── Channels/NotificationChannel.php
│   │   │   └── Payments/PaymentGatewayInterface.php
│   │   ├── Jobs/
│   │   │   ├── AggregateProviderStats.php
│   │   │   ├── ExpireSubscriptions.php
│   │   │   ├── ProcessPortfolioImage.php
│   │   │   └── SendNotification.php
│   │   ├── Models/
│   │   │   ├── Favorite.php
│   │   │   ├── Notification.php
│   │   │   ├── Payment.php
│   │   │   ├── PortfolioItem.php
│   │   │   ├── PortfolioMedia.php
│   │   │   ├── ProviderEvent.php
│   │   │   ├── ProviderStatsDaily.php
│   │   │   ├── QuoteRequest.php
│   │   │   ├── Review.php
│   │   │   ├── Service.php
│   │   │   ├── ServiceOption.php
│   │   │   ├── Subscription.php
│   │   │   └── User.php
│   │   ├── Observers/
│   │   │   └── ReviewObserver.php
│   │   └── Services/
│   │       ├── Gateways/ (FakeGateway, MvolaGateway, OrangeMoneyGateway, AirtelMoneyGateway)
│   │       ├── ProviderEventTracker.php
│   │       └── SubscriptionService.php
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
│       ├── NotificationTest.php (15 tests)
│       ├── PaymentTest.php (20 tests)
│       ├── PortfolioCrudTest.php
│       ├── QuoteTest.php
│       ├── ReviewTest.php
│       ├── SearchTest.php
│       ├── ServiceCrudTest.php
│       ├── StatsTest.php (23 tests)
│       └── SubscriptionTest.php (26 tests)
├── web/                    # Next.js Frontend
│   ├── src/
│   │   ├── app/
│   │   │   ├── api/auth/    # Auth API routes
│   │   │   ├── api/v1/
│   │   │   │   ├── favorites/  # Favorites proxy
│   │   │   │   ├── notifications/ # Notifications proxy
│   │   │   │   ├── quotes/     # Quotes proxy
│   │   │   │   ├── search/     # Search proxy
│   │   │   │   └── stats/      # Stats proxy
│   │   │   ├── dashboard/statistiques/ # Stats page
│   │   │   ├── login/
│   │   │   ├── register/
│   │   │   ├── recherche/   # Search page (SSR)
│   │   │   ├── favoris/     # Favorites page
│   │   │   ├── devis-recus/ # Received quotes page
│   │   │   ├── mes-devis/   # My quotes page
│   │   │   └── ...
│   │   ├── components/
│   │   │   ├── portfolio/   # PortfolioForm, PortfolioList, PortfolioSection
│   │   │   ├── search/      # SearchPage, SearchFiltersBar, ProviderCard
│   │   │   ├── services/    # ServiceForm, ServiceList
│   │   │   └── stats/       # StatsDashboard (recharts)
│   │   ├── Header.tsx       # Nav + NotificationBell
│   │   └── lib/
│   │       ├── api.ts
│   │       └── types/
│   │           ├── favorite.ts
│   │           ├── notification.ts
│   │           ├── portfolio.ts
│   │           ├── quote.ts
│   │           ├── search.ts
│   │           ├── service.ts
│   │           └── stats.ts
│   └── package.json
└── README.md
```

## Environnement de développement

- Cache et files d'attente : driver `database` (pas de Redis)
- Authentification : JWT (tymon/jwt-auth)
- Paiements : Gateway interface (Mvola, Orange Money, Airtel Money, FakeGateway pour dev)
- Abonnements : plans starter/pro/premium avec limits configurables
- Stats : provider_events + provider_stats_daily, aggregation job quotidienne
- Notifications : jobs database queue + channels (email, SMS)
- Base de test : PostgreSQL (omano_test)
- CI : GitHub Actions (tests Laravel + build Next.js)

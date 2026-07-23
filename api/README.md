# Omano API

Laravel REST API for the Omano event services platform.

## Stack

- **PHP 8.2+** / Laravel 11
- **PostgreSQL 17** with pg_trgm full-text search
- **JWT Auth** (tymon/jwt-auth) — httpOnly cookie via Next.js proxy
- **Queue**: database (SendNotification job)

## Modules

### Auth (`/api/v1/auth`)
- Register, login, logout, refresh token
- Email & phone verification
- `CheckRole` middleware (client / prestataire / admin)
- Custom `JwtAuthenticate` middleware (resolves singleton caching bug)

### Catalog (`/api/v1/services`)
- CRUD services + nested options (service_options table)
- ServiceController with filtering, ordering, eager-loaded options

### Portfolio (`/api/v1/portfolio`)
- PortfolioItem + PortfolioMedia models
- Async `ProcessPortfolioImage` job (queue)
- File upload via `storage_path('app/public/portfolio')`

### Search (`/api/v1/search`)
- PostgreSQL full-text search: tsvector + GIN indexes
- pg_trgm trigram for typo tolerance
- Haversine SQL for geo-radius search
- Filters: category, city, price range, rating, verified
- 25 search tests

### Favorites (`/api/v1/favorites`)
- Unique constraint (user_id, provider_id)
- GET / POST / DELETE endpoints
- FavoriteButton component + /favoris page

### Quotes (`/api/v1/quotes`)
- Client → Prestataire quote requests
- Accept/reject/respond workflow
- Rate limiting: `throttle:10,1` (10/day/client)
- QuoteForm, QuoteList, QuoteRespondForm components
- /mes-devis and /devis-recus pages

### Reviews (`/api/v1/reviews`)
- Create, update (48h window), report
- Unique constraint (user_id, provider_id)
- ReviewObserver recalculates User denormalized average_rating + rating_count
- Only `published` reviews count toward rating

### Notifications (`/api/v1/notifications`)
- DB table with jsonb payload, read_at tracking
- `SendNotification` job: stores notification + dispatches to EmailChannel / SmsChannel
- Channel interface pattern (`App\Contracts\Channels\NotificationChannel`)
- Triggers: quote.received, quote.responded, review.received, review.reported
- Mark read / read all endpoints
- NotificationBell component in header

## Testing

```bash
php artisan test          # 164 tests (162 pass + 2 GD-skipped)
php artisan test --filter=NotificationTest   # 15 tests
```

## Database

PostgreSQL 17 with:
- `pg_trgm` extension (trigram similarity)
- `tsvector` columns + GIN indexes (full-text search)
- Haversine formula (SQL-side geo distance)

## Frontend

Next.js 16 app at `/web` — API proxy routes forward cookies to Laravel backend.

# DoualaClean

A Laravel-based civic platform for waste management and urban sanitation in Douala, Cameroon. Citizens can report illegal trash deposits, subscribe to waste collection services, and track sanitation statistics via web or USSD (*347#).

## Architecture

- **Backend**: Laravel 11 (PHP 8.2), SQLite database, Sanctum token auth
- **Frontend**: Static HTML/CSS/JS in `frontend/` — copied to `public/` at startup
- **API**: RESTful JSON API under `/api` prefix
- **USSD**: Africa's Talking integration for feature-phone access

## Running the app

The workflow `Start application` handles everything automatically:
1. Installs Composer dependencies
2. Copies `.env.replit` → `.env`
3. Creates SQLite database and runs migrations
4. Creates storage symlink for file uploads
5. Copies `frontend/` files into `public/`
6. Starts PHP built-in server on port 5000

## Key pages (once running)

| URL | Description |
|-----|-------------|
| `/` | Landing page |
| `/login` | Blade login (session-based) |
| `/register` | Blade register |
| `/index.html` | Frontend landing (token-based) |
| `/login.html` | Frontend login |
| `/dashboard.html` | User/admin dashboard |
| `/carte.html` | Interactive map (Leaflet) |
| `/signalement.html` | Report a waste deposit |
| `/abonnement.html` | Subscription plans |
| `/ramassage.html` | Waste pickup service |

## API endpoints

- `POST /api/auth/register` — register
- `POST /api/auth/login` — login (returns Bearer token)
- `GET /api/signalements` — list reports (auth required)
- `POST /api/signalements` — create report (auth required)
- `GET /api/signalements/carte` — public map data
- `GET /api/admin/dashboard` — admin stats (admin role)
- `POST /api/ussd` — USSD entry point

## User roles

- `citoyen` — regular citizen, can report and manage own reports
- `agent` — municipal agent, can update report statuses
- `admin` — full access including user management

## User preferences

- Keep the frontend as static HTML files in `frontend/` — they get copied to `public/` at startup
- Use relative `/api` URLs (never hardcode localhost) in all JavaScript
- SQLite for dev/production (simple, no external DB needed on Replit)

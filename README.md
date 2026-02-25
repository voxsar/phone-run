# Runner Occupy

Run to claim territory. Walk/run in a loop — when your path crosses itself, you occupy that area. Other players can steal your territory by running through it.

## Architecture

- **Flutter app** (`/flutter_app`) — iOS & Android mobile client
- **Laravel + FilamentPHP backend** (`/backend`) — REST API + admin panel, Docker on port 6156

---

## Backend Setup

### 1. Environment

```bash
cd backend
cp .env.example .env
```

Edit `.env` and fill in:

| Variable | Description |
|---|---|
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | MySQL credentials |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | From [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials → OAuth 2.0 Client IDs |
| `FACEBOOK_APP_ID` / `FACEBOOK_APP_SECRET` | From [Meta for Developers](https://developers.facebook.com/) → your app → Settings → Basic |
| `FILAMENT_ADMIN_EMAIL` / `FILAMENT_ADMIN_PASSWORD` | Admin panel login |

### 2. Start Docker

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan filament:assets
```

Backend available at `http://localhost:6156`
Admin panel at `http://localhost:6156/admin`

### 3. Nginx reverse proxy (host)

Add a server block pointing `runneroccupy.dev.artslabcreatives.com` → `localhost:6156`.

---

## Flutter App Setup

### 1. Environment

```bash
cd flutter_app
cp .env.example .env
```

Edit `.env`:

| Variable | Description |
|---|---|
| `GOOGLE_MAPS_API_KEY` | Google Maps SDK key — see below |
| `BACKEND_URL` | Your backend URL (e.g. `https://runneroccupy.dev.artslabcreatives.com`) |
| `GOOGLE_CLIENT_ID` | OAuth 2.0 client ID (Android/iOS) |
| `FACEBOOK_APP_ID` | Meta app ID |
| `FACEBOOK_CLIENT_TOKEN` | Meta client token (Settings → Advanced) |

### 2. Google APIs to activate

In [Google Cloud Console](https://console.cloud.google.com/) enable all of these:

- **Maps SDK for Android**
- **Maps SDK for iOS**
- **Maps JavaScript API** (if using web)
- **Places API**
- **Geocoding API**
- **Google Sign-In** (via OAuth 2.0 — no separate toggle, just create credentials)

### 3. Android — add API key

In `flutter_app/android/local.properties`:
```properties
GOOGLE_MAPS_API_KEY=YOUR_KEY_HERE
```

Also update `flutter_app/android/app/src/main/res/values/strings.xml` with your Facebook app ID and client token.

### 4. iOS — add API key

In Xcode, add `GOOGLE_MAPS_API_KEY`, `FACEBOOK_APP_ID`, and `FACEBOOK_CLIENT_TOKEN` to your scheme's environment variables, or hardcode them in `ios/Runner/Info.plist` (not recommended for production).

### 5. Run

```bash
cd flutter_app
flutter pub get
flutter run
```

---

## How it works

1. **Start Run** — app begins recording GPS path
2. **Cross your path** — when your route self-intersects, the enclosed area is automatically claimed as your territory (shown in green)
3. **Other players** — territories from all players are shown in red on your map
4. **Territory stealing** — if another player's closed shape covers more than 50 % of your territory, they take it

## API Endpoints

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/register` | No | Register with email/password |
| POST | `/api/auth/login` | No | Login with email/password |
| POST | `/api/auth/social` | No | Login via Google / Facebook |
| POST | `/api/auth/logout` | Bearer | Logout |
| GET | `/api/territories` | Bearer | List all active territories |
| POST | `/api/territories` | Bearer | Claim a new territory |
| DELETE | `/api/territories/{id}` | Bearer | Remove own territory |
| POST | `/api/paths/update` | Bearer | Sync current path (live tracking) |

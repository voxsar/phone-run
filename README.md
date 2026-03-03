# Nestamalt Geovaders

Run to claim territory. Walk/run in a loop — when your path crosses itself, you occupy that area. Other players can steal your territory by running through it.

## Architecture

- **Flutter app** (`/flutter_app`) — iOS & Android mobile client (app name: **Nestamalt Geovaders**)
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
| `GOOGLE_MAPS_API_KEY` | Google Maps JavaScript API key (for admin panel map views — see below) |
| `FIREBASE_CREDENTIALS` | Path to Firebase service-account JSON file (default: `storage/app/firebase-credentials.json`) |
| `FIREBASE_PROJECT_ID` | Firebase project ID (found in the service-account JSON or Firebase console) |
| `FIREBASE_ADMIN_EMAIL` | Email address of the admin who should receive Firebase alerts |
| `FIREBASE_ADMIN_TOKEN` | FCM registration token of the admin's device/browser — obtain this after enabling push in your admin browser/app |

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

## Firebase Setup

Firebase is used to send real-time push notifications to the admin whenever a player signs in or claims a territory.

### 1. Create a Firebase project

1. Go to [Firebase Console](https://console.firebase.google.com/) and click **Add project**.
2. Enter a project name (e.g. `nestamalt-geovaders`) and follow the wizard.

### 2. Enable Cloud Messaging

1. In your Firebase project, open **Project Settings** (⚙️ gear icon).
2. Select the **Cloud Messaging** tab.
3. Note the **Sender ID** and **Server key** (for legacy) — for the v1 API used here, you only need the service account.

### 3. Generate a Service Account key

1. Still in **Project Settings**, select the **Service accounts** tab.
2. Click **Generate new private key** and confirm.
3. Save the downloaded `*.json` file as `backend/storage/app/firebase-credentials.json`  
   (or update `FIREBASE_CREDENTIALS` in `.env` to point to your file).

### 4. Get your Firebase Project ID

The project ID is visible:
- In the downloaded service-account JSON as `"project_id"`.
- In the Firebase console URL: `console.firebase.google.com/project/<PROJECT_ID>/...`

Set `FIREBASE_PROJECT_ID=<PROJECT_ID>` in `.env`.

### 5. Obtain an Admin FCM device token

To receive push notifications the admin needs a registered FCM token:

- **Web**: Add the Firebase JS SDK to a browser page, call `getToken(messaging, { vapidKey: '...' })`, and copy the resulting token.
- **Android/iOS app**: Use `firebase_messaging` Flutter package — `FirebaseMessaging.instance.getToken()` returns the token.

Set `FIREBASE_ADMIN_TOKEN=<token>` in `.env`.

### 6. What triggers notifications

| Event | Notification sent to admin |
|---|---|
| New player registers | 🆕 New Player Registered |
| Player signs in (email/social) | 🔑 Player Signed In |
| Player claims a territory | 🗺️ Territory Occupied |

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
- **Maps JavaScript API** (for the admin panel map views)
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

## Admin Panel Features

The Filament admin panel (`/admin`) includes:

| Page | Path | Description |
|---|---|---|
| Dashboard | `/admin` | Overview widgets |
| Players | `/admin/users` | All registered players; click **View Map** to open a player's personal territory map |
| Territories | `/admin/territories` | Tabular list of all territories |
| Territory Map | `/admin/territory-map` | 🗺️ **Global map** showing every player's territories colour-coded by player; includes a **day-by-day growth slider** to replay territory expansion over time |
| Per-user Map | `/admin/user-territory-map?user_id=N` | Map showing only one player's territories + their personal growth slider |

---

## How it works

1. **Start Run** — app begins recording GPS path
2. **Cross your path** — when your route self-intersects, the enclosed area is automatically claimed as your territory (shown in green)
3. **Other players** — territories from all players are shown in red on your map
4. **Territory stealing** — if another player's closed shape covers more than 50 % of your territory, they take it
5. **Admin alerts** — the admin receives a Firebase push notification when a player signs in or claims a territory

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

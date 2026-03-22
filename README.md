# Media Tools

Full-stack web application with three tools: YouTube video downloader, document management (DOCX→PDF + PDF merge), and a QR code admin panel.

## Stack

| Layer | Technology |
|---|---|
| Backend | Symfony 7, PHP 8.3, Doctrine ORM, Symfony Messenger |
| Queue | Redis + Symfony Redis Messenger Transport |
| Database | PostgreSQL 16 |
| Frontend | Vue 3, Vite, Vue Router 4, PWA (vite-plugin-pwa) |
| Proxy | Nginx (envsubst for configurable upstream) |
| Deployment | Docker Compose (local) / Railway + Nixpacks (production) |

---

## Features

### Video Downloader (`/video`)
- Download YouTube videos and audio via yt-dlp
- Formats: MP4, MP3, WebM, best audio, best video
- Async job queue with live progress via polling
- Supported hosts: youtube.com, youtu.be, music.youtube.com

### Document Tools (`/docs`)
- Upload DOCX or PDF files (drag & drop, reorder)
- DOCX → PDF conversion via LibreOffice headless
- PDF merge via FPDI (PDFs normalized to v1.4 with Ghostscript)
- Download result as a single merged PDF

### QR Code Admin Panel (`/admin`)
- Login with username + password stored in PostgreSQL
- JWT authentication (HS256, 24h expiry — native PHP, no external library)
- Full CRUD: create, edit, activate/deactivate, delete QR codes
- View QR image inline (SVG modal)
- QR codes redirect via `/q/{id}` and increment click counter

---

## Architecture

### Backend (DDD + CQRS)

```
backend/src/
├── Controller/
│   ├── DownloadController.php
│   ├── DocumentController.php
│   ├── AdminController.php        # QR CRUD + login
│   └── QrRedirectController.php   # /q/{id} redirect + SVG generation
├── Domain/Download/
│   ├── Exception/                 # InvalidVideoUrlException, UnsupportedFormatException
│   └── ValueObject/               # VideoUrl, DownloadFormat, JobId
├── Entity/
│   ├── QrCode.php
│   └── AdminUser.php
├── EventListener/
│   └── JwtAuthListener.php        # Guards /api/admin/* routes
├── Infrastructure/
│   ├── FileSystem/TempWorkspace.php
│   ├── Process/YtDlpRunner.php
│   └── Repository/                # JobRepositoryInterface / RedisJobRepository
├── Message/                       # Symfony Messenger commands
├── MessageHandler/                # Async download handler
├── Service/
│   ├── DownloaderService.php
│   ├── DocumentService.php
│   └── JwtService.php             # HS256 JWT (hash_hmac, no library)
└── Command/
    └── CreateAdminUserCommand.php  # app:admin:create
```

### Frontend (Vue 3 SPA)

```
frontend/src/
├── pages/
│   ├── HomePage.vue       # Landing with links to tools
│   ├── VideoPage.vue      # Video downloader
│   ├── DocumentPage.vue   # Document tools
│   └── AdminPage.vue      # QR admin panel (login + CRUD + QR modal)
├── components/
│   ├── DocumentManager.vue
│   ├── FormatSelector.vue
│   ├── ProgressBar.vue
│   └── StatusMessage.vue
├── composables/useDownload.js
└── router/index.js        # /, /video, /docs, /admin
```

### Database Migrations

```
backend/migrations/
├── Version20260321000000.php   # Creates qr_code table
└── Version20260322000001.php   # Creates admin_user table
```

---

## Quick Start

**Requirements**: Docker Desktop

```bash
make up
```

| Service | URL |
|---|---|
| Frontend | http://localhost:5173 |
| Backend API | http://localhost:8080 |
| PostgreSQL | localhost:5432 |

### Create first admin user

```bash
docker exec -it yt-downloader-backend php bin/console app:admin:create
```

Then go to http://localhost:5173/admin.

---

## Makefile Commands

```bash
make up                # Start all services
make down              # Stop all services
make rebuild           # Rebuild and restart everything
make logs              # Follow all logs
make shell             # Shell into backend container
make migrate           # Run pending migrations
make migration-diff    # Generate migration from entity changes
make cache-clear       # Clear Symfony cache
make test              # Run all tests (PHP + JS)
make test-unit         # PHP unit tests only
make test-frontend     # Vitest frontend tests
```

---

## Environment Variables

### Backend

| Variable | Description |
|---|---|
| `APP_SECRET` | Symfony app secret |
| `JWT_SECRET` | Secret for signing JWT tokens (HS256) |
| `DATABASE_URL` | PostgreSQL DSN |
| `REDIS_URL` | Redis connection URL |
| `MESSENGER_TRANSPORT_DSN` | Symfony Messenger transport DSN |
| `DEFAULT_URI` | Base URL used for QR generation |

### Frontend

| Variable | Description | Default |
|---|---|---|
| `BACKEND_UPSTREAM` | Backend URL for Nginx proxy | `http://backend:8080` |

---

## Railway Deployment

### Backend service
```
APP_SECRET=<random 32 char string>
JWT_SECRET=<random secret>
DATABASE_URL=<postgresql DSN from Railway>
REDIS_URL=<redis DSN from Railway>
MESSENGER_TRANSPORT_DSN=<redis DSN from Railway>
RAILWAY_PUBLIC_DOMAIN=<your-backend.railway.app>
```

Create the first admin user via Railway CLI:
```bash
# Install CLI if you don't have it
npm install -g @railway/cli

# Login and link project
railway login
railway link
#para entra en la terminal
railway ssh
# Open shell in backend service
railway shell --service backend

# Then inside the shell:
php bin/console app:admin:create
```

### Frontend service
```
BACKEND_UPSTREAM=https://<your-backend.railway.app>
```

---

## API Reference

### Public endpoints

| Method | Path | Description |
|---|---|---|
| `GET` | `/health` | Health check |
| `POST` | `/download` | Start async download job |
| `GET` | `/status/{jobId}` | Poll job status |
| `GET` | `/fetch/{jobId}` | Download finished file |
| `POST` | `/api/documents/merge` | Merge/convert documents |
| `GET` | `/q/{id}` | QR redirect (increments click counter) |
| `GET` | `/api/qr/generate/{id}` | Generate QR SVG image |

### Admin endpoints (JWT required)

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/admin/login` | Login, returns JWT token |
| `GET` | `/api/admin/qrcodes` | List all QR codes |
| `POST` | `/api/admin/qrcodes` | Create a QR code |
| `PATCH` | `/api/admin/qrcodes/{id}` | Update targetUrl or isActive |
| `DELETE` | `/api/admin/qrcodes/{id}` | Delete a QR code |

### Download formats

| Format | Description |
|---|---|
| `mp4` | Video + audio (best quality) |
| `mp3` | Audio only – MP3 |
| `webm` | Video in WebM |
| `audio` | Best quality audio |
| `video` | Video only (no audio) |

---

## Security

- URL host validated against an allow-list (YouTube only by default)
- Format validated against config
- `yt-dlp` invoked via `proc_open` with argument array (no shell injection)
- JWT signed with HS256 using `hash_hmac` — no external library, no security advisories
- Admin passwords stored as bcrypt hashes (`password_hash`)
- Temporary files deleted after response is sent

---

## License

MIT

# Media Tools

Full-stack web application with three tools: YouTube video downloader, document management (DOCX→PDF + PDF merge), and a QR code manager with public registration and email verification.

## Stack

| Layer | Technology |
|---|---|
| Backend | Symfony 7, PHP 8.3, Doctrine ORM, Symfony Messenger |
| Queue | Redis + Symfony Redis Messenger Transport |
| Database | PostgreSQL 16 |
| Frontend | Vue 3, Vite, Vue Router 4, Pinia, SCSS, PWA (vite-plugin-pwa) |
| Email | Brevo REST API (transactional email) |
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

### QR Code Manager (`/qr`)
- Public registration with email verification (Brevo)
- Password reset via email
- JWT authentication (HS256, 24h expiry — native PHP, no external library)
- Full CRUD: create, edit, activate/deactivate, delete QR codes
- View QR image inline (SVG modal), click tracking
- QR codes redirect via `/q/{id}` and increment click counter

---

## Architecture

### Backend — Hexagonal / DDD

```
backend/src/
├── Controller/
│   ├── AuthController.php          # /api/auth/* (register, login, verify, reset)
│   ├── AdminController.php         # /api/admin/* QR CRUD (JWT protected)
│   ├── DownloadController.php
│   ├── DocumentController.php
│   └── QrRedirectController.php    # /q/{id} redirect + SVG generation
│
├── Domain/
│   ├── Auth/
│   │   ├── Exception/              # InvalidCredentialsException, EmailNotVerifiedException…
│   │   └── Repository/             # AdminUserRepositoryInterface
│   └── QrCode/
│       ├── Exception/              # QrCodeNotFoundException, QrCodeForbiddenException…
│       └── Repository/             # QrCodeRepositoryInterface
│
├── Application/
│   ├── Auth/
│   │   ├── Login/                  # LoginCommand + LoginHandler + LoginResult
│   │   ├── Register/               # RegisterUserCommand + RegisterUserHandler
│   │   ├── VerifyEmail/            # VerifyEmailCommand + VerifyEmailHandler
│   │   ├── RequestPasswordReset/   # RequestPasswordResetCommand + Handler
│   │   └── ResetPassword/          # ResetPasswordCommand + ResetPasswordHandler
│   └── QrCode/
│       ├── QrCodeDto.php           # readonly DTO with fromEntity()
│       ├── List/                   # ListQrCodesQuery + Handler
│       ├── Create/                 # CreateQrCodeCommand + Handler
│       ├── Update/                 # UpdateQrCodeCommand + Handler
│       └── Delete/                 # DeleteQrCodeCommand + Handler
│
├── Infrastructure/
│   ├── Email/
│   │   ├── MailerInterface.php
│   │   └── BrevoMailer.php         # Brevo REST API via HttpClient
│   ├── Repository/
│   │   ├── DoctrineAdminUserRepository.php
│   │   └── DoctrineQrCodeRepository.php
│   └── Security/
│       ├── JwtServiceInterface.php
│       └── JwtService.php          # HS256 JWT (hash_hmac, no library)
│
├── Entity/
│   ├── AdminUser.php               # email, isVerified, verification/reset tokens
│   └── QrCode.php
│
├── EventListener/
│   ├── JwtAuthListener.php         # Guards /api/admin/* routes
│   └── ApiExceptionListener.php    # Maps domain exceptions → HTTP status codes
│
├── Service/
│   ├── DownloaderService.php
│   └── DocumentService.php
│
└── Command/
    └── CreateAdminUserCommand.php  # app:admin:create
```

### Frontend — Vue 3 SPA

```
frontend/src/
├── pages/
│   ├── HomePage.vue
│   ├── VideoPage.vue
│   ├── DocumentPage.vue
│   ├── QrPage.vue                  # Orchestrates QrTable + QrForm + QrDisplayModal
│   ├── LoginPage.vue
│   ├── RegisterPage.vue
│   ├── VerifyEmailPage.vue
│   ├── ForgotPasswordPage.vue
│   └── ResetPasswordPage.vue
├── components/
│   ├── ui/                         # BaseButton, BaseInput, BaseModal, BaseAlert, BaseSpinner
│   ├── layout/                     # AuthCard (shared layout for auth pages)
│   ├── features/qr/                # QrTable, QrForm, QrDisplayModal
│   ├── AppNavbar.vue
│   ├── DocumentManager.vue
│   ├── FormatSelector.vue
│   ├── ProgressBar.vue
│   └── StatusMessage.vue
├── composables/
│   ├── useAuth.js                  # Thin wrapper around Pinia auth store
│   ├── useQrCode.js                # QR CRUD state (fetchAll, create, update, remove)
│   └── useDownload.js
├── stores/
│   └── auth.js                     # Pinia store with localStorage persistence
├── services/
│   ├── api.js                      # apiFetch / publicFetch base wrappers
│   ├── authService.js              # login, register, verifyEmail, requestReset, resetPassword
│   └── qrService.js                # list, create, update, delete, getSvg
├── styles/
│   ├── _variables.scss             # Design tokens (colors, radii, breakpoints)
│   ├── _mixins.scss                # Reusable SCSS mixins
│   ├── _animations.scss            # Keyframes
│   └── main.scss                   # Global styles
└── router/index.js                 # Lazy-loaded routes, auth guard
```

### Database Migrations

```
backend/migrations/
├── Version20260321000000.php   # Creates qr_code table
├── Version20260322000001.php   # Creates admin_user table
├── Version20260323000000.php   # Adds QR columns (absolute_url…)
└── Version20260323000001.php   # Adds auth columns to admin_user
                                #   (email, is_verified, verification_token,
                                #    verification_token_expires, reset_token,
                                #    reset_token_expires)
```

### Tests

```
backend/tests/Unit/
├── Application/Auth/
│   ├── LoginHandlerTest.php
│   ├── RegisterUserHandlerTest.php
│   ├── VerifyEmailHandlerTest.php
│   └── ResetPasswordHandlerTest.php
├── Application/QrCode/
│   ├── CreateQrCodeHandlerTest.php
│   └── UpdateQrCodeHandlerTest.php
└── Infrastructure/Security/
    └── JwtServiceTest.php
```

Run with:
```bash
make test
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

### Post-migration (existing users without email)

After running migrations, mark existing CLI-created users as verified so they can still log in:

```bash
docker exec -it yt-downloader-postgres psql -U postgres -d media_tools \
  -c "UPDATE admin_user SET is_verified = TRUE WHERE email IS NULL;"
```

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
make test              # Run all tests
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
| `DEFAULT_URI` | Base URL of the frontend (used in verification/reset email links) |
| `BREVO_API_KEY` | Brevo REST API key (`xkeysib-…`) for transactional emails |

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
DEFAULT_URI=https://<your-frontend.railway.app>
BREVO_API_KEY=<xkeysib-…>
```

### Frontend service

```
BACKEND_UPSTREAM=https://<your-backend.railway.app>
```

### Create first admin user via Railway CLI

```bash
npm install -g @railway/cli
railway login
railway link
railway ssh
# Inside the shell:
php bin/console app:admin:create
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
| `POST` | `/api/auth/register` | Register new user (sends verification email) |
| `POST` | `/api/auth/login` | Login, returns JWT token |
| `GET` | `/api/auth/verify-email?token=` | Activate account |
| `POST` | `/api/auth/request-reset` | Send password reset email |
| `POST` | `/api/auth/reset-password` | Set new password with reset token |

### Admin endpoints (JWT required)

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/admin/qr` | List all QR codes for the authenticated user |
| `POST` | `/api/admin/qr` | Create a QR code |
| `PATCH` | `/api/admin/qr/{id}` | Update targetUrl or isActive |
| `DELETE` | `/api/admin/qr/{id}` | Delete a QR code |

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
- Passwords stored as bcrypt hashes (`password_hash`)
- Verification tokens: 64-char hex, expire in 24h
- Reset tokens: 64-char hex, expire in 1h
- Password reset always returns 200 regardless of email existence (anti-enumeration)
- Temporary files deleted after response is sent

---

## License

MIT

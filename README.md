# 📥 YT Downloader

> A full-stack YouTube downloader built with **Vue 3 + Vite** (frontend) and **Symfony 7** (backend API), fully containerized with Docker.

---

## ✨ Features

- Download YouTube videos in **MP4**, **WebM**, video-only
- Extract audio as **MP3** or best-quality audio
- URL validation (frontend + backend)
- Modular provider architecture (add Vimeo, TikTok, etc.)
- Premium dark glassmorphism UI
- Fully Dockerized – runs with a single command
- Deployable to Railway

---

## 🏗 Project Structure

```
downloadVideo/
├── backend/                 # Symfony 7 API
│   ├── src/
│   │   ├── Controller/
│   │   │   └── DownloadController.php
│   │   ├── Service/
│   │   │   ├── DownloaderService.php
│   │   │   └── Provider/
│   │   │       ├── VideoProviderInterface.php
│   │   │       └── YouTubeProvider.php
│   │   └── Kernel.php
│   ├── config/
│   │   └── packages/
│   │       ├── downloader.yaml    ← format config
│   │       ├── nelmio_cors.yaml
│   │       └── framework.yaml
│   ├── public/index.php
│   └── Dockerfile
├── frontend/                # Vue 3 + Vite SPA
│   ├── src/
│   │   ├── App.vue
│   │   ├── main.js
│   │   └── style.css
│   ├── index.html
│   ├── vite.config.js
│   ├── nginx.conf
│   └── Dockerfile
└── docker-compose.yml
```

---

## 🚀 Quick Start (Docker Compose)

> **Requirements**: Docker Desktop 4.x+

```bash
# 1. Clone / enter the project
cd downloadVideo

# 2. Build and start all services
docker compose up --build

# 3. Open the app
open http://localhost:5173
```

The backend API is available at `http://localhost:8080`.

---

## 🔧 Development (without Docker)

### Backend

```bash
cd backend
composer install
php -S 0.0.0.0:8080 -t public
```

> **Requirements**: PHP 8.3, Composer, `yt-dlp`, `ffmpeg` installed on your machine.

### Frontend

```bash
cd frontend
npm install
npm run dev          # http://localhost:5173
```

---

## 🌐 API Reference

### `POST /download`

Downloads a YouTube video/audio.

**Request**
```json
{
  "url": "https://youtu.be/dQw4w9WgXcQ",
  "format": "mp3"
}
```

**Supported formats**

| Format  | Description                   |
|---------|-------------------------------|
| `mp4`   | Video + audio (best quality)  |
| `mp3`   | Audio only – MP3              |
| `webm`  | Video in WebM format          |
| `audio` | Best quality audio            |
| `video` | Video only (no audio)         |

**Response**: Binary file stream with `Content-Disposition: attachment` header.

**Error responses** (JSON):
- `400 Bad Request` – invalid URL, unsupported host, unknown format
- `500 Internal Server Error` – yt-dlp execution failure

### `GET /health`

Returns `{"status":"ok"}`.

---

## 🧪 Testing the API

```bash
# Download MP3
curl -X POST http://localhost:8080/download \
  -H "Content-Type: application/json" \
  -d '{"url":"https://youtu.be/dQw4w9WgXcQ","format":"mp3"}' \
  -o audio.mp3

# Download MP4
curl -X POST http://localhost:8080/download \
  -H "Content-Type: application/json" \
  -d '{"url":"https://youtu.be/dQw4w9WgXcQ","format":"mp4"}' \
  -o video.mp4

# Invalid URL (expect 400)
curl -X POST http://localhost:8080/download \
  -H "Content-Type: application/json" \
  -d '{"url":"https://google.com","format":"mp3"}' -v
```

---

## ⚙️ Adding New Formats

Edit `backend/config/packages/downloader.yaml`. No code changes needed:

```yaml
downloader:
  formats:
    aac: "-x --audio-format aac"
    # add your format here
```

---

## ➕ Adding New Providers (Vimeo, TikTok…)

1. Create a class in `backend/src/Service/Provider/`  
2. Implement `VideoProviderInterface`  
3. Add the `#[AutoconfigureTag('app.video_provider')]` attribute  
4. Add the host to `$allowedHosts` in `services.yaml`

That's it – no controller changes needed.

---

## 🚂 Deploy to Railway

### Backend

1. Create a new Railway project → **New Service → GitHub Repo → `/backend`**
2. Railway auto-detects the Dockerfile
3. Set environment variables:
   ```
   APP_ENV=prod
   APP_SECRET=<random_32_chars>
   CORS_ALLOW_ORIGIN=https://your-frontend.railway.app
   ```
4. Add a custom start command (optional): `php -S 0.0.0.0:$PORT -t public`

### Frontend

1. Add another service → `/frontend`
2. Set build argument:
   ```
   VITE_API_URL=https://your-backend.railway.app
   ```
3. Railway builds with the Dockerfile and serves via Nginx

---

## 🔒 Security Notes

- URL host is validated against an allow-list (YouTube only by default)
- Format is validated against the YAML config
- `yt-dlp` is invoked via `proc_open` with an **argument array** (no shell injection possible)
- Temporary files are deleted after the response is sent

---

## 📄 License

MIT

# 🎬 TwitchClip Tool 

End‑to‑end pipeline that **downloads a Twitch clip → converts audio → transcribes with Whisper → burns hard‑subtitles → outputs a share‑ready MP4**.  
Runs fully in **Docker** (nginx + php‑fpm + MySQL + Redis + Node + Whisper worker).


---

## ✨ Features

- 🔗 **Twitch OAuth** → paste/choose a clip, the app queues a full processing pipeline
- 🎧 **Audio convert** (ffmpeg) → clean input for ASR
- 🧠 **ASR with Whisper** (whisper.cpp) — **GPU by default**; **CPU mode** available
- 🔠 **Hard‑subtitles** burned into final MP4 (ffmpeg, configurable threads)
- 🧵 **Queues over Redis**: `download` → `audio` → `transcribe` → `hardsubs`
- 🐳 All services containerized; simple `docker compose up -d --build`

---

## 🧱 Stack

| Layer      | Tech |
|-----------|------|
| Backend   | PHP 8.x · Laravel 12 |
| Frontend  | Blade · Alpine.js · Vite · TailwindCSS |
| Data      | MySQL 8 · Redis 7 |
| ML Worker | whisper.cpp (CUDA build), switchable to CPU |
| Runtime   | Docker Compose (nginx, php‑fpm, php‑cli, node, mysql, redis, whisper) |

---

## 🚀 Quick start

```bash
# 1) Clone
git clone https://github.com/kievski-roman/twitchclip-docker.git
cd twitchclip-docker

# 2) Env
cp src/.env.example src/.env
# edit TWITCH_CLIENT_ID / TWITCH_CLIENT_SECRET
# set WHISPER_DEVICE=GPU (default) or CPU

# 3) Run stack
docker compose up -d --build   # alias: make up

# 4) DB
docker compose exec php-cli php artisan migrate   # alias: make art migrate

# 5) Frontend
docker compose exec node npm install
docker compose exec node npm run dev  # (npm run build for prod)
```

Open the app: **http://localhost:8088** → Authorise with Twitch → Drop a clip.

> Prerequisites: Docker Engine ≥ 24 with Compose plugin, git. Everything else is inside containers.

---

## ⚙️ Environment (`src/.env`)

| Key | Example | Notes |
|-----|---------|-------|
| `APP_URL` | `http://localhost:8088` | Must match exposed port |
| `APP_KEY` | (generated) | `php artisan key:generate` once |
| `DB_HOST` / `DB_PORT` | `mysql` / `3306` | Internal Docker DNS |
| `DB_DATABASE` | `laravel` |  |
| `DB_USERNAME` / `DB_PASSWORD` | `user_exemple` / `exemple` | from `docker-compose.yml` |
| `REDIS_HOST` / `REDIS_PORT` | `redis` / `6379` |  |
| `QUEUE_CONNECTION` | `redis` | Required for jobs |
| `TWITCH_CLIENT_ID` | `...` | Get in https://dev.twitch.tv/console |
| `TWITCH_CLIENT_SECRET` | `...` | keep secret |
| `WHISPER_URL` | `http://whisper:9000` | gRPC endpoint |
| `WHISPER_DEVICE` | `GPU` or `CPU` | Switch compute mode |
| `FFMPEG_THREADS` | `2` | Limit CPU usage while burning subs |

> Full list lives in `src/.env.example` in the repo.

---

## 🧭 Project map (TL;DR)

```
src/app/Jobs/*               # Queue jobs: download → audio → transcribe → hardsubs
src/app/Services/WhisperService.php   # gRPC client for whisper.cpp
docker/                       # Service-specific Dockerfiles (nginx, php-fpm, whisper-gpu, ...)
whisper/                      # Minimal CUDA image build for whisper.cpp
Makefile                      # Aliases: up, down, cli, qrun, qlog, art
docker-compose.yml            # Full stack definition
```


---

## ⚡ Whisper: GPU vs CPU

| Mode | When to use | How | Notes |
|-----|--------------|-----|------|
| **GPU** | You have NVIDIA GPU / CUDA available | `WHISPER_DEVICE=GPU` | Fastest; CUDA build inside `whisper` container |
| **CPU** | Cheap VPS / no GPU | `WHISPER_DEVICE=CPU` | Slower; set `FFMPEG_THREADS` to limit CPU; consider small models |

---

## 🔄 Pipeline

```mermaid
flowchart LR
  A[Webhook / Clip URL] --> B[Queue: download]
  B --> C[Queue: audio convert]
  C --> D[Queue: transcribe (whisper)]
  D --> E[Queue: burn hardsubs]
  E --> F[MP4 ready]
```

Each job retries on transient errors. Logs live in `storage/logs/laravel.log` and docker container logs.

---

## 🧰 Troubleshooting

- **Whisper not reachable** → check `WHISPER_URL`, container `whisper` up, port `9000` internal.
- **No subtitles on output** → confirm `transcribe` job produced `.srt` and `FFMPEG_THREADS` not 0.
- **OAuth fails** → verify `TWITCH_CLIENT_ID/SECRET` and callback URL in Twitch console.
- **Queue stuck** → ensure `QUEUE_CONNECTION=redis`, run `make qrun`, check `redis` container.

---
## 🎥 Demo Preview


https://github.com/user-attachments/assets/2d913382-6806-48f5-afb1-1b09c997864d


---

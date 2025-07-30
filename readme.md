# TwitchClip Tool

> **End‑to‑end pipeline that downloads a Twitch clip, converts audio, transcribes it with Whisper (GPU‑ready), burns hard‑subtitles, and gives you a share‑ready MP4.**

&#x20;

---

## 🚀 Quick start

```bash
# 1 Clone
$ git clone https://github.com/kievski-roman/twitchclip-docker.git && cd twitchclip-docker

# 2 Create a local env file
$ cp src/.env.example src/.env           # then edit TWITCH_* and DB_*

# 3 Spin up the stack (nginx + php‑fpm + mysql + redis + whisper + node)
$ docker compose up -d --build           # alias:  up

# 4 Run DB migrations
$ docker compose exec php-cli php artisan migrate   # alias:  art migrate

# 5 Frontend assets
$ docker compose exec node npm install    # first‑time only
$ docker compose exec node npm run dev    # npm run build  for prod

# 6 Open the app
http://localhost:8088  →  Authorise with Twitch →  Drop a clip 🎉
```

> **Prerequisites**  • Docker Engine ≥ 24 & Compose plugin • git
> *(Everything else – PHP, Node, CUDA worker – lives inside the containers.)*

---

## 🔑 Environment variables (`src/.env.example`)

| Key                         | Example                 | Notes                                                                                   |
| --------------------------- |-------------------------| --------------------------------------------------------------------------------------- |
| `APP_URL`                   | `http://localhost:8088` | Must match exposed port 8088                                                            |
| `APP_KEY`                   | *(auto‑generated)*      | Laravel key, run `art key:generate` once                                                |
| `DB_HOST / DB_PORT`         | `mysql` / `3306`        | Internal Docker DNS                                                                     |
| `DB_DATABASE`               | `laravel`               |                                                                                         |
| `DB_USERNAME / DB_PASSWORD` | `user_exemple` / `exemple`     |                                                                                         |
| `REDIS_HOST / REDIS_PORT`   | `redis` / `6379`        |                                                                                         |
| `QUEUE_CONNECTION`          | `redis`                 |                                                                                         |
| `TWITCH_CLIENT_ID`          | `foo123`                | **Required** — get it in [https://dev.twitch.tv/console](https://dev.twitch.tv/console) |
| `TWITCH_CLIENT_SECRET`      | `bar456`                | **Required**                                                                            |
| `WHISPER_URL`               | `http://whisper:9000`   | Internal gRPC endpoint                                                                  |
| `WHISPER_DEVICE`            | `GPU` \| `CPU`          | `GPU` by default; set `CPU` to run on cheap VPS                                         |
| `FFMPEG_THREADS`            | `2`                     | Limit CPU usage when burning subs                                                       |
| *(see full list in file)*   |                         |                                                                                         |

---

## 🛠 Tech stack

| Layer          | What we use                                                               |
| -------------- |---------------------------------------------------------------------------|
| **Backend**    | PHP 8.4 · Laravel 12                                                      |
| **Containers** | nginx · php‑fpm · php‑cli · MySQL 8.4 · Redis 7 · Node 20                 |
| **ML Worker**  | whisper.cpp (CUDA build) — switchable to CPU with `WHISPER_DEVICE=CPU`    |
| **Queues**     | Laravel Queues over Redis (`download`, `audio`, `transcribe`, `hardsubs`) |
| **Frontend**   | Blade · Alpine JS · Vite · Tailwind CSS                                   |
| **CI**         | GitHub Actions – runs PHPUnit inside Docker                               |

---

## 📂 Project map (TL;DR)

| Path                                  | Responsibility                                                |
| ------------------------------------- | ------------------------------------------------------------- |
| `src/app/Jobs/*`                      | Queue jobs (download → convert → transcribe → hardsub)        |
| `src/app/Services/WhisperService.php` | Lightweight gRPC client for whisper.cpp                       |
| `docker/`                             | Service‑specific Dockerfiles (nginx, php‑fpm, whisper‑gpu, …) |
| `whisper/`                            | Minimal CUDA image build for whisper.cpp                      |
| `Makefile` / alias block              | `up`, `down`, `cli`, `qrun`, `qlog`, `art`                    |

---

## 📈 Roadmap

*

Community ideas are welcome — open an Issue or PR 🙌

---

## 🧩 Running with a different Whisper build

*Have your own ultrafast fork or CPU‑only binary?* Two options:

1. **Swap the Docker image**
   Edit `docker-compose.yml > whisper:` → `image: yourrepo/whisper:latest` and make sure it exposes `9000` gRPC.
2. **Mount a local binary**
   Comment out the build section, add a volume: `- ./your-whisper:/usr/local/bin/whisper`.

As long as the container responds to the standard whisper.cpp JSON/gRPC contract, the app will work.

---

## 🏗 Contributing

1. Fork → create branch → commit → open PR.
2. Pre‑push hook runs `composer test` & `npm run lint`.
3. Please attach screenshots/gifs for UI changes.

---

## 📜 License

MIT © 2025 Roman Kievskii
Whisper.cpp © 2022 Georgi Gerganov (MIT)

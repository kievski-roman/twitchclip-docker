# ========================= Whisper API (GPU) =========================
FROM nvidia/cuda:12.4.1-cudnn-runtime-ubuntu20.04

ARG PY=python3.10
ENV DEBIAN_FRONTEND=noninteractive \
    TZ=Europe/Amsterdam


# ---------- системні пакети + Python 3.10 ----------
RUN apt-get update && apt-get install -y --no-install-recommends \
        software-properties-common lsb-release curl git ffmpeg && \
    add-apt-repository ppa:deadsnakes/ppa && \
    apt-get update && apt-get install -y --no-install-recommends \
        python3.10 python3.10-distutils && \
    rm -rf /var/lib/apt/lists/*


# ---------- pip ----------
RUN curl -sS https://bootstrap.pypa.io/get-pip.py | ${PY}

# ---------- Python‑залежності ----------
# 1) PyTorch з власного CUDA‑репозиторію
RUN ${PY} -m pip install --no-cache-dir \
        torch==2.2.0 --index-url https://download.pytorch.org/whl/cu121

# 2) Усе інше з дефолтного PyPI
RUN ${PY} -m pip install --no-cache-dir \
        numpy==1.26.3 \
        git+https://github.com/guillaumekln/faster-whisper.git \
        fastapi uvicorn[standard] python-multipart

# ---------- робочий каталог та код ----------
WORKDIR /app
COPY app.py ./app.py

# ---------- запуск ----------
EXPOSE 9000
CMD ["python3.10", "-m", "uvicorn", "app:app", "--host", "0.0.0.0", "--port", "9000"]

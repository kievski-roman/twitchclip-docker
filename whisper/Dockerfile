FROM nvidia/cuda:12.1.1-cudnn8-runtime-ubuntu20.04

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Europe/Amsterdam

# --- Встановлюємо Python 3.10 та інше ---
RUN apt-get update && apt-get install -y \
    software-properties-common \
    lsb-release \
    curl \
    git \
    ffmpeg \
    && add-apt-repository ppa:deadsnakes/ppa \
    && apt-get update \
    && apt-get install -y python3.10 python3.10-distutils

# --- pip для Python 3.10 ---
RUN curl -sS https://bootstrap.pypa.io/get-pip.py | python3.10

# --- Встановлюємо torch + faster-whisper + numpy 1.26 ---
RUN python3.10 -m pip install --upgrade pip && \
    python3.10 -m pip install numpy==1.26.4 && \
    python3.10 -m pip install torch==2.2.0 --index-url https://download.pytorch.org/whl/cu121 && \
    python3.10 -m pip install git+https://github.com/guillaumekln/faster-whisper.git
###
  #  Через нвида видео карту поставить загрузку
####
#RUN apt-get update && apt-get install -y --no-install-recommends \
#    libcudnn8 \
#    libcudnn8-dev \
# && rm -rf /var/lib/apt/lists/* \

WORKDIR /app

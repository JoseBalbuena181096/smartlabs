FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    INSIGHTFACE_HOME=/models

# Dependencias del sistema:
# - libgl/libglib/etc → requeridos por opencv
# - ffmpeg → backend RTSP de OpenCV
# - build-essential / cmake → insightface compila extensiones Cython al
#   instalar (no hay wheel en pypi para 0.7.3)
RUN apt-get update && apt-get install -y --no-install-recommends \
        build-essential \
        cmake \
        libgl1 \
        libglib2.0-0 \
        libsm6 \
        libxext6 \
        libxrender1 \
        ffmpeg \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt ./
RUN pip install --upgrade pip && pip install -r requirements.txt

COPY src/ ./src/

# Pre-descargar el modelo buffalo_l (~300MB) durante build para que el primer
# arranque no espere por la descarga. Los pesos quedan en /models y persisten
# en la imagen final.
RUN mkdir -p /models && python -c "from insightface.app import FaceAnalysis; \
    a = FaceAnalysis(name='buffalo_l', root='/models', providers=['CPUExecutionProvider']); \
    a.prepare(ctx_id=0, det_size=(640,640))"

EXPOSE 8500
CMD ["uvicorn", "src.main:app", "--host", "0.0.0.0", "--port", "8500"]

from fastapi import FastAPI, UploadFile, File
from faster_whisper import WhisperModel
import tempfile, os

model = WhisperModel("small")
app   = FastAPI()

def ts(sec: float) -> str:
    h, m = divmod(int(sec), 3600)
    m, s = divmod(m, 60)
    ms   = int((sec - int(sec)) * 1000)
    return f"{h:02d}:{m:02d}:{s:02d}.{ms:03d}"   # ← крапка!

@app.post("/transcribe")
async def transcribe(audio: UploadFile = File(...)):
    # 1. тимчасовий WAV
    with tempfile.NamedTemporaryFile(delete=False, suffix=".wav") as tmp:
        tmp.write(await audio.read())
        tmp_path = tmp.name

    # 2. розпізнаємо
    segments, _ = model.transcribe(tmp_path)
    os.remove(tmp_path)

    # 3. формуємо WebVTT
    lines = ["WEBVTT", ""]
    for seg in segments:
        lines += [
            f"{ts(seg.start)} --> {ts(seg.end)}",
            seg.text.strip(),
            ""
        ]

    return {"vtt": "\n".join(lines)}
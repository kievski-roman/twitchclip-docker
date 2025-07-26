from fastapi import FastAPI, UploadFile, File
from faster_whisper import WhisperModel
import tempfile, os

# ❶ — виберіть модель: tiny / base / small / medium / large
model = WhisperModel("small")        # почніть зі 'small', щоб швидше стартувало
app   = FastAPI()

@app.post("/transcribe")
async def transcribe(audio: UploadFile = File(...)):
    # ❷ — зберігаємо прийнятий WAV у тимчасовий файл
    with tempfile.NamedTemporaryFile(delete=False, suffix=".wav") as tmp:
        tmp.write(await audio.read())
        tmp_path = tmp.name

    # ❸ — транскрибуємо
    segments, _ = model.transcribe(tmp_path)
    os.remove(tmp_path)

    # ❹ — формуємо SRT‑текст
    def ts(sec: float) -> str:
        h, m = divmod(int(sec), 3600)
        m, s = divmod(m, 60)
        ms   = int((sec - int(sec)) * 1000)
        return f"{h:02d}:{m:02d}:{s:02d},{ms:03d}"

    lines = []
    for i, seg in enumerate(segments, 1):
        lines += [
            str(i),
            f"{ts(seg.start)} --> {ts(seg.end)}",
            seg.text.strip(),
            ""
        ]
    return {"srt": "\n".join(lines)}
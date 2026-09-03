
from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.responses import FileResponse
from starlette.background import BackgroundTask

from pathlib import Path
import tempfile
import shutil
import uuid

from converter import convert_pdf_to_docx


app = FastAPI(
    title="PDF to Word API",
    version="1.0.0"
)


def cleanup_directory(path: Path):
    try:
        shutil.rmtree(path, ignore_errors=True)
    except Exception:
        pass


@app.get("/")
def root():
    return {
        "success": True,
        "service": "PDF to Word API",
        "status": "running"
    }


@app.get("/health")
def health():
    return {
        "success": True,
        "status": "healthy"
    }


@app.post("/convert")
async def convert_pdf(file: UploadFile = File(...)):

    # التأكد من وجود اسم الملف
    if not file.filename:
        raise HTTPException(
            status_code=400,
            detail="No filename provided."
        )

    # السماح بملفات PDF فقط
    filename = file.filename.lower()

    if not filename.endswith(".pdf"):
        raise HTTPException(
            status_code=400,
            detail="Only PDF files are allowed."
        )

    # إنشاء مجلد مؤقت خاص بالطلب
    workdir = Path(
        tempfile.mkdtemp(
            prefix=f"pdfword_{uuid.uuid4().hex}_"
        )
    )

    input_path = workdir / "input.pdf"

    original_name = Path(file.filename).stem

    # تنظيف اسم الملف
    safe_name = "".join(
        c for c in original_name
        if c.isalnum() or c in (" ", "-", "_")
    ).strip()

    if not safe_name:
        safe_name = "converted"

    output_path = workdir / f"{safe_name}.docx"

    try:

        # حفظ PDF على السيرفر
        with input_path.open("wb") as buffer:

            while True:

                chunk = await file.read(1024 * 1024)

                if not chunk:
                    break

                buffer.write(chunk)

        # التأكد أن الملف موجود وليس فارغاً
        if not input_path.exists():
            raise RuntimeError(
                "Uploaded PDF was not saved."
            )

        if input_path.stat().st_size <= 0:
            raise RuntimeError(
                "Uploaded PDF is empty."
            )

        # تشغيل محرك التحويل الأصلي
        convert_pdf_to_docx(
            input_path,
            output_path
        )

        # التأكد من إنشاء Word
        if not output_path.exists():
            raise RuntimeError(
                "DOCX file was not created."
            )

        if output_path.stat().st_size <= 0:
            raise RuntimeError(
                "DOCX file is empty."
            )

        # إرسال الملف إلى Waifly
        return FileResponse(
            path=str(output_path),
            media_type=(
                "application/vnd.openxmlformats-"
                "officedocument.wordprocessingml.document"
            ),
            filename=f"{safe_name}.docx",
            background=BackgroundTask(
                cleanup_directory,
                workdir
            )
        )

    except HTTPException:
        cleanup_directory(workdir)
        raise

    except Exception as exc:

        cleanup_directory(workdir)

        raise HTTPException(
            status_code=500,
            detail=str(exc)
        )

    finally:

        try:
            await file.close()
        except Exception:
            pass

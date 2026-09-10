from pathlib import Path
import sys

def convert_pdf_to_docx(input_path: Path, output_path: Path) -> None:
    try:
        from pdf2docx import Converter
    except ImportError as exc:
        raise RuntimeError("The pdf2docx package is not installed. Run: pip install pdf2docx") from exc

    input_path = Path(input_path)
    output_path = Path(output_path)

    if not input_path.exists():
        raise RuntimeError(f"PDF file does not exist: {input_path}")

    output_path.parent.mkdir(parents=True, exist_ok=True)
    converter = Converter(str(input_path))

    try:
        converter.convert(str(output_path), start=0, end=None)
    finally:
        converter.close()

    if not output_path.exists() or output_path.stat().st_size <= 0:
        raise RuntimeError("DOCX output was not created.")


def convert_pdf_to_xlsx(input_path: Path, output_path: Path) -> None:
    try:
        import pdfplumber
        import pandas as pd
        import openpyxl
    except ImportError as exc:
        raise RuntimeError("Packages for Excel conversion are not installed. Run: pip install pdfplumber pandas openpyxl") from exc

    input_path = Path(input_path)
    output_path = Path(output_path)

    if not input_path.exists():
        raise RuntimeError(f"PDF file does not exist: {input_path}")

    output_path.parent.mkdir(parents=True, exist_ok=True)
    tables_found = False

    with pdfplumber.open(input_path) as pdf:
        with pd.ExcelWriter(output_path, engine='openpyxl') as writer:
            for i, page in enumerate(pdf.pages):
                tables = page.extract_tables()
                for j, table in enumerate(tables):
                    if table:
                        tables_found = True
                        df = pd.DataFrame(table)
                        # تحديد اسم الشيت واقتطاعه لتجنب تجاوز 31 حرف المسموحة في Excel
                        sheet_name = f'Page_{i+1}_Table_{j+1}'[:31]
                        df.to_excel(writer, sheet_name=sheet_name, index=False, header=False)

    # إذا لم يتم العثور على أي جداول في الـ PDF، ننشئ ملف فارغ مع رسالة توضيحية
    if not tables_found:
        wb = openpyxl.Workbook()
        ws = wb.active
        ws.title = "No Tables Found"
        ws['A1'] = "لم يتم العثور على جداول في ملف الـ PDF المرفق."
        wb.save(output_path)

    if not output_path.exists() or output_path.stat().st_size <= 0:
        raise RuntimeError("XLSX output was not created.")


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python converter.py input.pdf output.[docx|xlsx]", file=sys.stderr)
        sys.exit(2)

    try:
        in_path = Path(sys.argv[1])
        out_path = Path(sys.argv[2])
        
        # التفرقة بين Word و Excel بناءً على امتداد الملف المطلوب
        if out_path.suffix.lower() == '.xlsx':
            convert_pdf_to_xlsx(in_path, out_path)
        else:
            convert_pdf_to_docx(in_path, out_path)

        print("SUCCESS")
        sys.exit(0)

    except Exception as exc:
        print(f"{type(exc).__name__}: {exc}", file=sys.stderr)
        sys.exit(1)

from pathlib import Path
import sys


def convert_pdf_to_docx(
    input_path: Path,
    output_path: Path
) -> None:

    try:
        from pdf2docx import Converter
    except ImportError as exc:
        raise RuntimeError(
            "The pdf2docx package is not installed. "
            "Run: pip install pdf2docx"
        ) from exc

    input_path = Path(input_path)
    output_path = Path(output_path)

    if not input_path.exists():
        raise RuntimeError(
            f"PDF file does not exist: {input_path}"
        )

    output_path.parent.mkdir(
        parents=True,
        exist_ok=True
    )

    converter = Converter(
        str(input_path)
    )

    try:
        converter.convert(
            str(output_path),
            start=0,
            end=None
        )
    finally:
        converter.close()

    if (
        not output_path.exists()
        or output_path.stat().st_size <= 0
    ):
        raise RuntimeError(
            "DOCX output was not created."
        )


if __name__ == "__main__":

    if len(sys.argv) != 3:
        print(
            "Usage: python converter.py input.pdf output.docx",
            file=sys.stderr
        )
        sys.exit(2)

    try:

        convert_pdf_to_docx(
            Path(sys.argv[1]),
            Path(sys.argv[2])
        )

        print("SUCCESS")
        sys.exit(0)

    except Exception as exc:

        print(
            f"{type(exc).__name__}: {exc}",
            file=sys.stderr
        )

        sys.exit(1)
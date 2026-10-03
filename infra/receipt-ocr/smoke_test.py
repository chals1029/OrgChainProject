"""Synthetic OCR fixtures only; these are not real financial documents."""
import io
import json
import argparse
from pathlib import Path
import urllib.request
from PIL import Image, ImageDraw, ImageFont, ImageFilter


def fixture(lines, angle=0):
    picture = Image.new("RGB", (1100, 1500), "white")
    draw = ImageDraw.Draw(picture)
    font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 32)
    for i, line in enumerate(lines):
        draw.text((65, 80+i*62), line, fill="black", font=font)
    if angle:
        picture = picture.rotate(angle, expand=True, fillcolor="white").filter(ImageFilter.GaussianBlur(0.4))
    out = io.BytesIO()
    picture.save(out, format="PNG")
    return out.getvalue()


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument('--export-dir')
    parser.add_argument('--export-only', action='store_true')
    args = parser.parse_args()
    samples = [
        ("gcash", ["GCash", "Paid to", "SAVEMORE MARKET", "Amount PHP 1,234.50", "Reference No. 1234567890123", "Sep 20, 2026", "SYNTHETIC TEST - NOT VALID"], 0),
        ("savemore", ["SAVEMORE MARKET", "SALES INVOICE", "Invoice No. 87654321", "09/20/2026", "Rice 2 x 150.00", "TOTAL PHP 300.00", "CASH PHP 500.00", "CHANGE PHP 200.00", "SYNTHETIC TEST - NOT VALID"], 3),
    ]
    for name, lines, angle in samples:
        content = fixture(lines, angle)
        if args.export_dir:
            Path(args.export_dir).mkdir(parents=True, exist_ok=True)
            Path(args.export_dir, name + '.png').write_bytes(content)
        if args.export_only:
            continue
        request = urllib.request.Request("http://127.0.0.1:8080/scan", data=content, headers={"Content-Type": "image/png"})
        result = json.load(urllib.request.urlopen(request, timeout=70))
        assert "SAVEMORE" in result["text"].upper(), result
        assert result["confidence"] > 50, result
        print(json.dumps({"fixture": name, **result}))

"""Local OCR only: bounded decoding, no uploaded files retained, no outbound calls."""
import io
import math
import threading
from collections import OrderedDict

import cv2
import numpy as np
import pytesseract
from fastapi import FastAPI, HTTPException, Request
from PIL import Image, ImageOps, UnidentifiedImageError
from starlette.concurrency import run_in_threadpool

Image.MAX_IMAGE_PIXELS = 24_000_000
cv2.setNumThreads(1)
app = FastAPI(docs_url=None, redoc_url=None, openapi_url=None)
slots = threading.BoundedSemaphore(2)
ENGINE = "tesseract-" + str(pytesseract.get_tesseract_version()).split()[0]


def prepare(content):
    with Image.open(io.BytesIO(content)) as source:
        if source.format not in {"JPEG", "PNG", "WEBP"}:
            raise ValueError("Upload a JPEG, PNG, or WebP photo.")
        if source.width * source.height > Image.MAX_IMAGE_PIXELS:
            raise ValueError("Photo is too large. Use a photo below 24 megapixels.")
        picture = ImageOps.exif_transpose(source).convert("RGBA")
        background = Image.new("RGBA", picture.size, "white")
        background.alpha_composite(picture)
        picture = background.convert("RGB")
    picture.thumbnail((2400, 3200))
    steps = ["orientation corrected", "contrast enhanced"]
    rgb = np.array(picture)
    gray = cv2.cvtColor(rgb, cv2.COLOR_RGB2GRAY)
    # Crop only a large four-sided document boundary; otherwise keep the full frame.
    contours, _ = cv2.findContours(cv2.Canny(gray, 70, 160), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    for contour in sorted(contours, key=cv2.contourArea, reverse=True)[:5]:
        quad = cv2.approxPolyDP(contour, 0.02 * cv2.arcLength(contour, True), True)
        if len(quad) != 4 or cv2.contourArea(quad) < gray.size * 0.40:
            continue
        points = quad.reshape(4, 2).astype("float32")
        sums, differences = points.sum(axis=1), np.diff(points, axis=1).ravel()
        corners = np.array([points[np.argmin(sums)], points[np.argmin(differences)], points[np.argmax(sums)], points[np.argmax(differences)]])
        if len(np.unique(corners, axis=0)) != 4:
            continue
        width = int(max(np.linalg.norm(corners[0]-corners[1]), np.linalg.norm(corners[2]-corners[3])))
        height = int(max(np.linalg.norm(corners[0]-corners[3]), np.linalg.norm(corners[1]-corners[2])))
        if min(width, height) < 250:
            continue
        matrix = cv2.getPerspectiveTransform(corners, np.float32([[0, 0], [width-1, 0], [width-1, height-1], [0, height-1]]))
        gray = cv2.warpPerspective(gray, matrix, (width, height), borderValue=255)
        steps.append("document cropped and straightened")
        break
    # Estimate small text-line skew from horizontal segments, not the page border.
    edges = cv2.Canny(gray, 60, 160)
    segments = cv2.HoughLinesP(edges, 1, np.pi/180, 70, minLineLength=max(50, gray.shape[1]//6), maxLineGap=15)
    angles = []
    for x1, y1, x2, y2 in ([] if segments is None else segments[:, 0]):
        angle = math.degrees(math.atan2(int(y2-y1), int(x2-x1)))
        if abs(angle) < 12:
            angles.append(angle)
    angle = float(np.median(angles)) if len(angles) >= 4 else 0
    if 0.4 < abs(angle) < 12:
        h, w = gray.shape
        gray = cv2.warpAffine(gray, cv2.getRotationMatrix2D((w/2, h/2), angle, 1), (w, h), borderValue=255)
        steps.append("text deskewed")
    if gray.shape[1] < 1200:
        scale = min(2, 1200/gray.shape[1], 3200/gray.shape[0])
        if scale > 1:
            gray = cv2.resize(gray, None, fx=scale, fy=scale, interpolation=cv2.INTER_CUBIC)
    gray = cv2.createCLAHE(clipLimit=2, tileGridSize=(8, 8)).apply(gray)
    gray = cv2.addWeighted(gray, 1.4, cv2.GaussianBlur(gray, (0, 0), 1.0), -0.4, 0)
    return cv2.copyMakeBorder(gray, 15, 15, 15, 15, cv2.BORDER_CONSTANT, value=255), steps


def recognize(image, mode):
    result = pytesseract.image_to_data(image, lang="eng", config=f"--psm {mode} --dpi 300", output_type=pytesseract.Output.DICT, timeout=18)
    groups = OrderedDict()
    for i, text in enumerate(result["text"]):
        if not text.strip() or float(result["conf"][i]) < 0:
            continue
        key = (result["block_num"][i], result["par_num"][i], result["line_num"][i])
        groups.setdefault(key, []).append((text.strip(), max(0, float(result["conf"][i]))))
    lines = [{"text": " ".join(word for word, _ in words), "confidence": round(sum(score*len(word) for word, score in words)/sum(len(word) for word, _ in words), 1)} for words in groups.values()]
    text = "\n".join(line["text"] for line in lines)[:30000]
    confidence = round(sum(line["confidence"]*len(line["text"]) for line in lines)/max(1, sum(len(line["text"]) for line in lines)), 1)
    return {"text": text, "lines": lines[:500], "confidence": confidence}


def scan(content):
    if not slots.acquire(blocking=False):
        raise HTTPException(503, "Scanner is busy. Try again shortly.")
    try:
        gray, steps = prepare(content)
        candidates = [recognize(gray, 6)]
        binary = cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 41, 13)
        candidates.append(recognize(binary, 6))
        if max(c["confidence"] for c in candidates) < 65:
            candidates.append(recognize(gray, 11))
        best = max(candidates, key=lambda c: c["confidence"] * min(1, len(c["text"])/100))
        return {**best, "engine": ENGINE, "preprocessing": steps + ["multiple OCR passes"]}
    except (UnidentifiedImageError, ValueError, OSError, Image.DecompressionBombError, Image.DecompressionBombWarning):
        raise HTTPException(422, "This photo cannot be decoded safely. Use a JPEG, PNG, or WebP under 24 megapixels.")
    except RuntimeError:
        raise HTTPException(503, "Scanning timed out. Retake or resize the photo and retry.")
    finally:
        slots.release()


@app.get("/health")
def health():
    return {"status": "ok", "engine": ENGINE}


@app.post("/scan")
async def scan_photo(request: Request):
    content = bytearray()
    async for chunk in request.stream():
        content.extend(chunk)
        if len(content) > 10 * 1024 * 1024:
            raise HTTPException(413, "Photo must be at most 10 MB.")
    return await run_in_threadpool(scan, bytes(content))

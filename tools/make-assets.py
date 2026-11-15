#!/usr/bin/env python3
"""Generate WordPress.org listing art for CPD Records for Tutor LMS.

Run from the repository root:

    python tools/make-assets.py

Writes PNGs to wporg-assets/. These files belong in the WordPress.org
plugin repository's top-level assets/ directory (SVN), not in trunk —
they are listing art, not runtime plugin files.
"""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "wporg-assets"
FONTS = Path("C:/Windows/Fonts")

TEAL_BRIGHT = (18, 160, 147)
TEAL_DARK = (11, 79, 74)
TEAL = (15, 118, 110)
MINT = (94, 234, 212)
MINT_PALE = (153, 246, 228)
WHITE = (255, 255, 255)


def font(name: str, size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(str(FONTS / name), size)


def vertical_gradient(size: tuple[int, int], top, bottom) -> Image.Image:
    w, h = size
    strip = Image.new("RGB", (1, h))
    px = strip.load()
    for y in range(h):
        t = y / max(h - 1, 1)
        px[0, y] = tuple(round(top[i] + (bottom[i] - top[i]) * t) for i in range(3))
    return strip.resize((w, h), Image.BILINEAR)


def horizontal_gradient(size: tuple[int, int], left, right) -> Image.Image:
    w, h = size
    strip = Image.new("RGB", (w, 1))
    px = strip.load()
    for x in range(w):
        t = x / max(w - 1, 1)
        px[x, 0] = tuple(round(left[i] + (right[i] - left[i]) * t) for i in range(3))
    return strip.resize((w, h), Image.BILINEAR)


def cubic(p0, p1, p2, p3, steps=28):
    points = []
    for i in range(steps + 1):
        t = i / steps
        u = 1 - t
        x = u**3 * p0[0] + 3 * u**2 * t * p1[0] + 3 * u * t**2 * p2[0] + t**3 * p3[0]
        y = u**3 * p0[1] + 3 * u**2 * t * p1[1] + 3 * u * t**2 * p2[1] + t**3 * p3[1]
        points.append((x, y))
    return points


def tooth_path():
    """Closed molar outline in a 100x100 coordinate space."""
    p = [(50, 8)]
    p += cubic((50, 8), (36, 8), (22, 14), (18, 28))
    p += cubic((18, 28), (14, 42), (18, 56), (24, 70))
    p += cubic((24, 70), (28, 80), (30, 90), (36, 90))
    p += cubic((36, 90), (42, 90), (42, 76), (44, 64))
    p += cubic((44, 64), (45, 56), (47, 52), (50, 52))
    p += cubic((50, 52), (53, 52), (55, 56), (56, 64))
    p += cubic((56, 64), (58, 76), (58, 90), (64, 90))
    p += cubic((64, 90), (70, 90), (72, 80), (76, 70))
    p += cubic((76, 70), (82, 56), (86, 42), (82, 28))
    p += cubic((82, 28), (78, 14), (64, 8), (50, 8))
    return p


def transform(point, scale=0.86, cx=50, cy=48):
    return ((point[0] - cx) * scale + cx, (point[1] - cy) * scale + cy)


def render_icon(size: int, ss: int = 4) -> Image.Image:
    """Rounded-square app icon: teal gradient, white tooth, verified check."""
    canvas = size * ss
    u = canvas / 100.0  # 100-space unit

    bg = vertical_gradient((canvas, canvas), TEAL_BRIGHT, TEAL_DARK)

    # Soft corner glow for depth.
    glow = Image.new("RGBA", (canvas, canvas), (0, 0, 0, 0))
    gd = ImageDraw.Draw(glow)
    gd.ellipse((-canvas * 0.35, -canvas * 0.45, canvas * 0.75, canvas * 0.55),
               fill=(255, 255, 255, 26))
    glow = glow.filter(ImageFilter.GaussianBlur(canvas * 0.12))
    bg = Image.alpha_composite(bg.convert("RGBA"), glow)

    # Tooth shadow.
    shadow = Image.new("RGBA", (canvas, canvas), (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow)
    path = [transform(pt) for pt in tooth_path()]
    sd.polygon([(x * u, y * u + 2.2 * u) for x, y in path], fill=(0, 0, 0, 70))
    shadow = shadow.filter(ImageFilter.GaussianBlur(2.4 * u))
    bg = Image.alpha_composite(bg, shadow)

    # Tooth.
    d = ImageDraw.Draw(bg)
    d.polygon([(x * u, y * u) for x, y in path], fill=WHITE)

    # Verified badge.
    bx, by = transform((80, 79))
    br = 14 * 0.86
    box = ((bx - br) * u, (by - br) * u, (bx + br) * u, (by + br) * u)
    d.ellipse(box, fill=WHITE)
    d.ellipse(box, outline=TEAL, width=max(1, round(2.2 * 0.86 * u)))
    check = [transform(p) for p in [(73.4, 78.9), (77.9, 83.4), (86.9, 73.6)]]
    d.line([(x * u, y * u) for x, y in check], fill=TEAL,
           width=round(7.2 * 0.86 * u), joint="curve")

    # Rounded mask.
    mask = Image.new("L", (canvas, canvas), 0)
    ImageDraw.Draw(mask).rounded_rectangle((0, 0, canvas - 1, canvas - 1),
                                           radius=round(canvas * 0.22), fill=255)

    out = Image.new("RGBA", (canvas, canvas), (0, 0, 0, 0))
    out.paste(bg, (0, 0), mask)
    return out.resize((size, size), Image.LANCZOS)


def render_banner(width: int, height: int) -> Image.Image:
    """772x250 design box; height follows the 772:250 ratio."""
    ss = 4 if width <= 800 else 2
    canvas_w, canvas_h = width * ss, height * ss
    unit = canvas_w / 772.0

    bg = horizontal_gradient((canvas_w, canvas_h), TEAL_DARK, TEAL)
    overlay = Image.new("RGBA", (canvas_w, canvas_h), (0, 0, 0, 0))
    od = ImageDraw.Draw(overlay)
    od.ellipse((-canvas_w * 0.15, -canvas_h * 0.9, canvas_w * 0.55, canvas_h * 0.7),
               fill=(255, 255, 255, 18))
    od.ellipse((canvas_w * 0.72, canvas_h * 0.15, canvas_w * 1.25, canvas_h * 1.35),
               fill=(0, 0, 0, 26))
    overlay = overlay.filter(ImageFilter.GaussianBlur(canvas_w * 0.04))
    bg = Image.alpha_composite(bg.convert("RGBA"), overlay)
    d = ImageDraw.Draw(bg)

    # Cycle ring motif on the right.
    cx, cy, r = 690 * unit, 125 * unit, 72 * unit
    d.arc((cx - r, cy - r, cx + r, cy + r), start=-90, end=270,
          fill=(255, 255, 255, 46), width=round(7 * unit))
    d.arc((cx - r, cy - r, cx + r, cy + r), start=-90, end=188,
          fill=MINT + (255,), width=round(7 * unit))
    # End-cap dot on the progress arc.
    import math
    ang = math.radians(188)
    dx, dy = cx + r * math.cos(ang), cy + r * math.sin(ang)
    dot = 7 * unit
    d.ellipse((dx - dot, dy - dot, dx + dot, dy + dot), fill=MINT + (255,))
    label_font = font("segoeui.ttf", round(12 * unit))
    d.text((cx, cy + r + 14 * unit), "5-year cycle", font=label_font,
           fill=(255, 255, 255, 150), anchor="mt")

    # Icon mark.
    icon_px = round(128 * unit)
    icon = render_icon(icon_px)
    icon_shadow = Image.new("RGBA", (canvas_w, canvas_h), (0, 0, 0, 0))
    isd = ImageDraw.Draw(icon_shadow)
    ix, iy = round(36 * unit), round(61 * unit)
    isd.rounded_rectangle((ix + 2 * unit, iy + 5 * unit,
                           ix + icon_px, iy + icon_px + 5 * unit),
                          radius=round(icon_px * 0.22), fill=(0, 0, 0, 60))
    icon_shadow = icon_shadow.filter(ImageFilter.GaussianBlur(4 * unit))
    bg = Image.alpha_composite(bg, icon_shadow)
    bg.paste(icon, (ix, iy), icon)
    d = ImageDraw.Draw(bg)

    # Wordmark.
    tx = 190 * unit
    title_font = font("segoeuib.ttf", round(46 * unit))
    sub_font = font("segoeui.ttf", round(23 * unit))
    tag_font = font("segoeui.ttf", round(15 * unit))

    d.text((tx, 98 * unit), "CPD Records", font=title_font, fill=WHITE, anchor="lm")
    d.text((tx + 2 * unit, 143 * unit), "for Tutor LMS", font=sub_font,
           fill=MINT_PALE + (255,), anchor="lm")
    d.text((tx + 2 * unit, 180 * unit), "GDC hours · outcomes A–D · audit-ready records",
           font=tag_font, fill=(209, 250, 229, 220), anchor="lm")

    return bg.convert("RGB").resize((width, height), Image.LANCZOS)


def main():
    OUT.mkdir(exist_ok=True)
    render_icon(128).save(OUT / "icon-128x128.png", optimize=True)
    render_icon(256).save(OUT / "icon-256x256.png", optimize=True)
    render_banner(772, 250).save(OUT / "banner-772x250.png", optimize=True)
    render_banner(1544, 500).save(OUT / "banner-1544x500.png", optimize=True)
    for f in sorted(OUT.glob("*.png")):
        print(f"{f.name}: {f.stat().st_size // 1024} KB")


if __name__ == "__main__":
    main()

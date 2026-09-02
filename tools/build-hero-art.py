#!/usr/bin/env python3
"""Generate the line-art panoramas that sit behind each inner page's hero.

The hospital gave us no photographs, so the banners use drawn artwork in the
same idiom as the home-page illustration: thin strokes, a dot field, and one
large motif held to the right so the headline never fights it.

    python3 tools/build-hero-art.py

Writes assets/img/hero/<slug>.svg. Safe to re-run; output is deterministic.
"""

import math
import os

W, H = 1600, 440
OUT = os.path.join(os.path.dirname(__file__), '..', 'assets', 'img', 'hero')

LINE = '#a9d2f5'   # the drawing colour, cool enough to sit on navy
GOLD = '#f5cd66'
RED = '#f07d75'

# Every motif is authored at readable opacities, then dimmed once here. The
# banners lay a dark scrim over the artwork, so the strokes have to start
# strong or they vanish underneath it.
INK = 2.7


# ---------------------------------------------------------------- primitives

def ink(o):
    """Scale an authored opacity into the range the scrim can survive."""
    return round(min(1.0, o * INK), 3)


def path(d, w=2.0, o=0.30, c=LINE, cap='round', dash=None):
    a = f'<path d="{d}" stroke="{c}" stroke-width="{w}" stroke-opacity="{ink(o)}" '
    a += f'fill="none" stroke-linecap="{cap}" stroke-linejoin="round"'
    if dash:
        a += f' stroke-dasharray="{dash}"'
    return a + '/>'


def circle(cx, cy, r, w=2.0, o=0.30, c=LINE, dash=None):
    a = f'<circle cx="{cx}" cy="{cy}" r="{r}" stroke="{c}" stroke-width="{w}" '
    a += f'stroke-opacity="{ink(o)}" fill="none"'
    if dash:
        a += f' stroke-dasharray="{dash}"'
    return a + '/>'


def dot(cx, cy, r, o=0.35, c=LINE):
    return f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{c}" fill-opacity="{ink(o)}"/>'


def rect(x, y, w_, h_, r=0, sw=2.0, o=0.30, c=LINE):
    return (f'<rect x="{x}" y="{y}" width="{w_}" height="{h_}" rx="{r}" '
            f'stroke="{c}" stroke-width="{sw}" stroke-opacity="{ink(o)}" fill="none"/>')


def line(x1, y1, x2, y2, **kw):
    return path(f'M{x1} {y1}L{x2} {y2}', **kw)


def poly(pts, **kw):
    d = 'M' + 'L'.join(f'{x} {y}' for x, y in pts)
    return path(d, **kw)


def cross(cx, cy, s, w=2.0, o=0.25, c=LINE):
    """A plain medical cross, drawn as two strokes."""
    return (line(cx - s, cy, cx + s, cy, w=w, o=o, c=c)
            + line(cx, cy - s, cx, cy + s, w=w, o=o, c=c))


def box3d(x, y, w_, d_, h_, rows=0, o=0.30):
    """A cabinet-projected block: front face, top face, right face."""
    dx, dy = d_ * 0.866, -d_ * 0.5
    out = [
        poly([(x, y), (x + w_, y), (x + w_, y + h_), (x, y + h_), (x, y)], o=o),
        poly([(x, y), (x + dx, y + dy), (x + w_ + dx, y + dy), (x + w_, y)], o=o),
        poly([(x + w_, y), (x + w_ + dx, y + dy),
              (x + w_ + dx, y + dy + h_), (x + w_, y + h_)], o=o),
    ]
    for i in range(rows):
        ry = y + h_ * (i + 1) / (rows + 1)
        out.append(line(x + 12, ry, x + w_ - 12, ry, w=1.4, o=o * 0.7))
        out.append(line(x + w_ + dx * 0.12, ry + dy * 0.12,
                        x + w_ + dx * 0.88, ry + dy * 0.88, w=1.4, o=o * 0.55))
    return ''.join(out)


# ------------------------------------------------------------------ backdrop

def backdrop():
    """Dot field fading in from the left, plus wide arcs off the top corner."""
    parts = [
        '<defs>',
        f'<pattern id="dots" width="34" height="34" patternUnits="userSpaceOnUse">'
        f'<circle cx="2" cy="2" r="1.6" fill="{LINE}"/></pattern>',
        '<linearGradient id="fade" x1="0" y1="0" x2="1" y2="0">'
        '<stop offset="0" stop-color="#000"/>'
        '<stop offset=".55" stop-color="#777"/>'
        '<stop offset="1" stop-color="#fff"/></linearGradient>',
        f'<mask id="fademask"><rect width="{W}" height="{H}" fill="url(#fade)"/></mask>',
        '</defs>',
        f'<rect width="{W}" height="{H}" fill="url(#dots)" opacity=".5" mask="url(#fademask)"/>',
    ]
    for r, o in ((330, 0.16), (470, 0.13), (620, 0.10), (790, 0.07)):
        parts.append(circle(1420, -70, r, w=1.6, o=o))
    return ''.join(parts)


# -------------------------------------------------------------------- motifs

def m_services():
    """Stethoscope: ear tubes sweeping down into a chest piece."""
    out = [
        path('M905 66 C905 210 1010 250 1082 250 C1154 250 1259 210 1259 66',
             w=3.2, o=0.34),
        path('M1082 250 L1082 300 C1082 352 1124 392 1176 392', w=3.2, o=0.34),
        circle(1082, 250, 9, w=3.0, o=0.30),
        circle(1216, 392, 44, w=3.4, o=0.36),
        circle(1216, 392, 27, w=2.0, o=0.24),
        line(1176, 392, 1172, 392, w=3.2, o=0.34),
        circle(905, 62, 8, w=3.0, o=0.30),
        circle(1259, 62, 8, w=3.0, o=0.30),
    ]
    for cx, cy, s, o in ((1400, 130, 26, 0.22), (1500, 250, 17, 0.17), (1330, 300, 12, 0.14)):
        out.append(cross(cx, cy, s, w=3.0, o=o, c=GOLD if s > 20 else LINE))
    return ''.join(out)


def m_about():
    """The hospital block, drawn the way the home page draws it."""
    return (box3d(880, 150, 300, 90, 230, rows=4)
            + box3d(1240, 230, 190, 70, 150, rows=3, o=0.24)
            + line(830, 380, 1520, 380, w=2.0, o=0.22)
            + cross(1030, 96, 26, w=4.0, o=0.26, c=RED)
            + circle(1030, 96, 44, w=1.6, o=0.14))


def m_doctors():
    """Three overlapping consultation portraits."""
    out = []
    for cx, r, o in ((980, 78, 0.22), (1160, 104, 0.32), (1360, 78, 0.22)):
        cy = 250 if r > 90 else 268
        out.append(circle(cx, cy, r, w=2.2, o=o))
        out.append(circle(cx, cy - r * 0.34, r * 0.30, w=2.0, o=o))
        out.append(path(f'M{cx - r * 0.68} {cy + r * 0.80} '
                        f'C{cx - r * 0.62} {cy + r * 0.10} '
                        f'{cx + r * 0.62} {cy + r * 0.10} '
                        f'{cx + r * 0.68} {cy + r * 0.80}', w=2.0, o=o))
    out.append(cross(1160, 372, 18, w=3.4, o=0.26, c=GOLD))
    return ''.join(out)


def m_tariff():
    """A stack of ledger cards with ruled rows."""
    out = [rect(1180, 120, 250, 250, r=14, sw=2.0, o=0.16),
           rect(1130, 100, 250, 250, r=14, sw=2.0, o=0.22),
           rect(1080, 80, 250, 250, r=14, sw=2.6, o=0.34)]
    for i in range(5):
        y = 132 + i * 36
        out.append(line(1112, y, 1112 + (170 if i % 2 else 120), y, w=2.0, o=0.24))
        out.append(dot(1298, y, 3.4, o=0.26 if i % 2 else 0.16))
    # A rupee mark, kept to the right of the cards so it never crowds the copy.
    out.append(path('M1462 224 C1462 190 1492 176 1514 176 C1536 176 1548 190 1548 190',
                    w=2.6, o=0.22))
    out.append(line(1448, 200, 1532, 200, w=2.4, o=0.22))
    out.append(line(1448, 222, 1532, 222, w=2.4, o=0.22))
    out.append(path('M1514 176 L1470 288', w=2.6, o=0.22))
    return ''.join(out)


def m_contact():
    """Contour rings, a road, and a dropped pin."""
    out = []
    for r, o in ((60, 0.30), (108, 0.22), (162, 0.16), (222, 0.11)):
        out.append(circle(1210, 236, r, w=2.0, o=o))
    out.append(path('M1210 168 C1178 168 1152 194 1152 226 C1152 268 1210 320 1210 320 '
                    'C1210 320 1268 268 1268 226 C1268 194 1242 168 1210 168 Z',
                    w=3.0, o=0.34, c=RED))
    out.append(circle(1210, 226, 20, w=2.6, o=0.30, c=RED))
    out.append(path('M840 400 C960 400 1000 330 1090 330', w=2.2, o=0.18, dash='14 12'))
    out.append(path('M1330 96 C1400 150 1420 220 1520 250', w=2.2, o=0.16, dash='14 12'))
    return ''.join(out)


def m_facilities():
    """A floor plan seen from above: rooms, a corridor, a bed."""
    out = [rect(880, 110, 560, 250, r=6, sw=2.4, o=0.30),
           line(880, 240, 1440, 240, w=2.0, o=0.22),
           line(1060, 110, 1060, 240, w=2.0, o=0.22),
           line(1240, 110, 1240, 240, w=2.0, o=0.22),
           line(1150, 240, 1150, 360, w=2.0, o=0.22)]
    for x in (966, 1146, 1336):
        out.append(path(f'M{x - 22} 240 A22 22 0 0 1 {x} 218', w=1.6, o=0.16))
    out.append(rect(920, 268, 96, 64, r=6, sw=2.0, o=0.24))
    out.append(line(920, 288, 1016, 288, w=1.8, o=0.20))
    out.append(rect(1250, 268, 130, 64, r=6, sw=2.0, o=0.18))
    out.append(cross(1400, 150, 16, w=3.0, o=0.22, c=GOLD))
    return ''.join(out)


def m_maternity():
    """Two nested arcs — a figure holding a smaller one."""
    out = [
        path('M980 400 C980 250 1050 180 1150 180 C1250 180 1320 250 1320 400',
             w=3.0, o=0.30),
        circle(1150, 130, 52, w=3.0, o=0.30),
        path('M1120 330 C1120 282 1152 258 1190 258 C1228 258 1258 282 1258 330',
             w=2.6, o=0.24),
        circle(1190, 226, 30, w=2.6, o=0.24),
        path('M1400 190 C1400 172 1424 164 1434 182 C1444 164 1468 172 1468 190 '
             'C1468 216 1434 240 1434 240 C1434 240 1400 216 1400 190 Z',
             w=2.6, o=0.26, c=RED),
    ]
    for r, o in ((210, 0.13), (275, 0.10)):
        out.append(circle(1150, 300, r, w=1.6, o=o))
    return ''.join(out)


def m_diabetes():
    """A droplet beside a plotted glucose curve."""
    out = [
        path('M1330 132 C1330 132 1418 232 1418 292 C1418 341 1379 380 1330 380 '
             'C1281 380 1242 341 1242 292 C1242 232 1330 132 1330 132 Z',
             w=3.0, o=0.32),
        path('M1290 292 C1290 316 1308 336 1330 342', w=2.2, o=0.22),
    ]
    pts = [(950, 320), (1022, 268), (1094, 296), (1166, 206), (1238, 246)]
    out.append(poly(pts, w=3.0, o=0.30, c=GOLD))
    for x, y in pts:
        out.append(dot(x, y, 5.5, o=0.30, c=GOLD))
    out.append(line(936, 372, 1260, 372, w=2.0, o=0.18))
    out.append(line(936, 372, 936, 172, w=2.0, o=0.18))
    for i in range(4):
        out.append(line(936, 200 + i * 44, 1260, 200 + i * 44, w=1.3, o=0.09))
    return ''.join(out)


def m_emergency():
    """An ECG trace running the width, under a shield."""
    trace = ('M820 250 L940 250 L968 250 L988 196 L1012 316 L1036 232 L1058 250 '
             'L1180 250 L1206 250 L1226 210 L1248 292 L1268 250 L1520 250')
    out = [path(trace, w=3.4, o=0.40),
           path('M1350 96 L1454 130 L1454 216 C1454 272 1402 304 1350 322 '
                'C1298 304 1246 272 1246 216 L1246 130 Z', w=2.6, o=0.22)]
    out.append(cross(1350, 200, 30, w=4.0, o=0.24))
    out.append(line(820, 380, 1520, 380, w=2.0, o=0.14, dash='10 14'))
    for i, x in enumerate((900, 1100, 1300, 1480)):
        out.append(dot(x, 250, 4.5, o=0.26 - i * 0.04))
    return ''.join(out)


def m_gallery():
    """Frames on a wall, one holding an aperture mark."""
    out = [rect(880, 120, 210, 160, r=8, sw=2.4, o=0.30),
           rect(1120, 170, 160, 210, r=8, sw=2.4, o=0.24),
           rect(1310, 110, 200, 150, r=8, sw=2.4, o=0.20),
           rect(1310, 290, 200, 96, r=8, sw=2.0, o=0.14)]
    out.append(poly([(900, 262), (958, 200), (1000, 240), (1032, 214), (1072, 262)],
                    w=2.2, o=0.24))
    out.append(dot(942, 158, 8, o=0.24, c=GOLD))
    out.append(circle(1200, 274, 44, w=2.4, o=0.24))
    for k in range(6):
        a = math.radians(k * 60 + 18)
        out.append(line(1200 + 14 * math.cos(a), 274 + 14 * math.sin(a),
                        1200 + 44 * math.cos(a), 274 + 44 * math.sin(a),
                        w=1.8, o=0.18))
    return ''.join(out)


def m_book():
    """A calendar with one day picked out, and token slips beside it."""
    out = [rect(880, 120, 320, 260, r=14, sw=2.6, o=0.32),
           line(880, 182, 1200, 182, w=2.2, o=0.26),
           line(944, 104, 944, 138, w=3.0, o=0.28),
           line(1136, 104, 1136, 138, w=3.0, o=0.28)]
    for r in range(3):
        for c in range(5):
            cx, cy = 926 + c * 62, 220 + r * 62
            if (r, c) == (1, 2):
                out.append(circle(cx, cy, 22, w=3.0, o=0.36, c=GOLD))
                out.append(dot(cx, cy, 5, o=0.34, c=GOLD))
            else:
                out.append(dot(cx, cy, 4.5, o=0.18))
    for i, (x, y, o) in enumerate(((1300, 300, 0.16), (1280, 240, 0.22), (1260, 180, 0.32))):
        out.append(rect(x, y, 220, 92, r=12, sw=2.2, o=o))
        out.append(line(x + 22, y + 34, x + 120, y + 34, w=2.0, o=o * 0.8))
        out.append(line(x + 22, y + 60, x + 86, y + 60, w=2.0, o=o * 0.6))
        out.append(circle(x + 172, y + 46, 22, w=2.0, o=o))
    return ''.join(out)


MOTIFS = {
    'services': m_services,
    'about': m_about,
    'doctors': m_doctors,
    'tariff': m_tariff,
    'contact': m_contact,
    'facilities': m_facilities,
    'maternity': m_maternity,
    'diabetes': m_diabetes,
    'emergency': m_emergency,
    'gallery': m_gallery,
    'book': m_book,
}


def build(slug, motif):
    # Banners are shorter than this canvas and the artwork is drawn with
    # background-size: cover, so the top and bottom get cropped. Pull the
    # motif in towards the centre to keep it clear of the crop.
    body = backdrop() + ('<g transform="translate(1150 220) scale(.82) '
                         'translate(-1150 -220)">' + motif() + '</g>')
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" '
            f'width="{W}" height="{H}" fill="none" role="img" '
            f'aria-hidden="true" preserveAspectRatio="xMidYMid slice">'
            f'<title>{slug} banner artwork</title>{body}</svg>')


def main():
    os.makedirs(OUT, exist_ok=True)
    for slug, motif in MOTIFS.items():
        dest = os.path.join(OUT, slug + '.svg')
        with open(dest, 'w', encoding='utf-8') as fh:
            fh.write(build(slug, motif))
        print(f'{os.path.relpath(dest)}  {os.path.getsize(dest)} bytes')


if __name__ == '__main__':
    main()

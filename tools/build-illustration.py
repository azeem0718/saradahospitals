"""
Axonometric line drawing of the nursing home, generated from 3D geometry.

Two things make it read as a solid building rather than a wireframe:

  * only the faces an axonometric view can actually see are emitted, and the
    main block's right face is skipped entirely because the stair core butts
    against it at the same depth;
  * faces carry a tonal fill (top lightest, front mid, side darkest) and are
    emitted back-to-front, so nearer surfaces occlude the lines behind them.

Cabinet-style projection: the front face is true, depth recedes up and right
at ANGLE with DEPTH foreshortening.
"""
import math
OUT = __import__('os').path.join(__import__('os').path.dirname(__file__), 'illustration.svg')

ANGLE = math.radians(30)
DEPTH = 0.5
CX, CY = 92, 372

PTS = []

def P(x, y, z):
    pt = (round(CX + x + z * DEPTH * math.cos(ANGLE), 1),
          round(CY - y - z * DEPTH * math.sin(ANGLE), 1))
    PTS.append(pt)
    return pt

def disc(x, y, z, r):
    """Circle in the picture plane, with its extents registered for the fit."""
    cx, cy = P(x, y, z)
    PTS.extend([(cx - r, cy - r), (cx + r, cy + r)])
    return "M%s %sa%s %s 0 1 0 %s 0a%s %s 0 1 0 %s 0" % (cx - r, cy, r, r, 2 * r, r, r, -2 * r)

def poly(pts):
    return "M" + " L".join(f"{x} {y}" for x, y in pts) + "Z"

def line(a, b):
    return f"M{a[0]} {a[1]} L{b[0]} {b[1]}"

class Box:
    def __init__(self, x, y, z, w, h, d):
        self.x, self.y, self.z, self.w, self.h, self.d = x, y, z, w, h, d
    def c(self, i, j, k):
        return P(self.x + i*self.w, self.y + j*self.h, self.z + k*self.d)
    def front(self): return [self.c(0,0,0), self.c(1,0,0), self.c(1,1,0), self.c(0,1,0)]
    def side(self):  return [self.c(1,0,0), self.c(1,0,1), self.c(1,1,1), self.c(1,1,0)]
    def top(self):   return [self.c(0,1,0), self.c(1,1,0), self.c(1,1,1), self.c(0,1,1)]

def win_front(b, cols, mw, mh, sill):
    out, gap = [], (b.w - cols*mw)/(cols+1)
    for c in range(cols):
        o = gap + c*(mw+gap)
        out.append(poly([P(b.x+o, b.y+sill, b.z), P(b.x+o+mw, b.y+sill, b.z),
                         P(b.x+o+mw, b.y+sill+mh, b.z), P(b.x+o, b.y+sill+mh, b.z)]))
    return out

def win_side(b, cols, mw, mh, sill):
    out, gap = [], (b.d - cols*mw)/(cols+1)
    X = b.x + b.w
    for c in range(cols):
        o = gap + c*(mw+gap)
        out.append(poly([P(X, b.y+sill, b.z+o), P(X, b.y+sill, b.z+o+mw),
                         P(X, b.y+sill+mh, b.z+o+mw), P(X, b.y+sill+mh, b.z+o)]))
    return out

FL = 44
main   = Box(0,   0, 0, 244, FL*4, 116)
p_main = Box(-6,  FL*4, -6, 250, 12, 128)      # parapet caps the main block
core   = Box(244, 0, 0,  80, FL*4+40, 116)
p_core = Box(244, FL*4+40, -6, 88, 12, 128)
canopy = Box(26, 40, -54, 108, 11, 54)          # entrance canopy slab
amb    = Box(376, 0, -26, 78, 42, 34)

items = []
GROUP = [1]
def stage(n): GROUP[0] = n
def add(cls, *paths): items.extend((cls, GROUP[0], p) for p in paths)

# Painter's order: furthest back first, so nearer faces occlude what is behind.

stage(1)
add("ln-ground", line(P(-80, 0, 0), P(482, 0, 0)))
tx, tz, r = -54, 50, 27
add("ln-thin", line(P(tx, 0, tz), P(tx, 54, tz)))
add("ln-thin", disc(tx, 76, tz, r))

# --- main block, then its parapet -----------------------------------------
add("f-top", poly(main.top()))
add("f-front", poly(main.front()))
stage(2)
for k in range(1, 4):
    add("ln-thin", line(P(main.x, k*FL, main.z), P(main.x+main.w, k*FL, main.z)))
stage(3)
for k in range(4):
    add("ln-win", *win_front(Box(main.x, k*FL, main.z, main.w, FL, main.d), 4, 38, 22, 12))
stage(2)
add("f-top", poly(p_main.top()))
add("f-front", poly(p_main.front()))

# --- stair core, then its parapet -----------------------------------------
stage(2)
add("f-top", poly(core.top()))
add("f-side", poly(core.side()))
add("f-front", poly(core.front()))
for k in range(1, 5):
    add("ln-thin", line(P(core.x, k*FL, core.z), P(core.x+core.w, k*FL, core.z)))
    add("ln-thin", line(P(core.x+core.w, k*FL, core.z), P(core.x+core.w, k*FL, core.z+core.d)))
stage(3)
for k in range(4):
    b = Box(core.x, k*FL, core.z, core.w, FL, core.d)
    add("ln-win", *win_front(b, 2, 26, 24, 10))
    add("ln-win", *win_side(b, 2, 30, 24, 10))
stage(4)
add("f-top", poly(p_core.top()))
add("f-side", poly(p_core.side()))
add("f-front", poly(p_core.front()))

# --- cross straight onto the core parapet face -----------------------------
cxx, cyy = core.x + 40, p_core.y + 6
add("ln-mark", line(P(cxx, cyy, p_core.z), P(cxx, cyy+9, p_core.z)),
               line(P(cxx-4.5, cyy+4.5, p_core.z), P(cxx+4.5, cyy+4.5, p_core.z)))

# --- entrance: doors on the facade, then a canopy in front of them ---------
stage(4)
d0, d1 = 56, 112
add("ln-win", poly([P(d0, 0, 0), P(d1, 0, 0), P(d1, 34, 0), P(d0, 34, 0)]))
add("ln-thin", line(P((d0+d1)/2, 0, 0), P((d0+d1)/2, 34, 0)))
add("f-top", poly(canopy.top()))
add("f-side", poly(canopy.side()))
add("f-front", poly(canopy.front()))
for cxx in (canopy.x + 7, canopy.x + canopy.w - 7):
    add("ln-thin", line(P(cxx, 0, canopy.z), P(cxx, canopy.y, canopy.z)))
add("f-front", poly([P(canopy.x-10, 0, canopy.z-14), P(canopy.x+canopy.w+10, 0, canopy.z-14),
                     P(canopy.x+canopy.w+10, 0, canopy.z), P(canopy.x-10, 0, canopy.z)]))

# --- ambulance, clear of the building --------------------------------------
stage(5)
add("f-top", poly(amb.top()))
add("f-side", poly(amb.side()))
add("f-front", poly(amb.front()))
add("ln-thin", line(P(amb.x+48, 0, amb.z), P(amb.x+48, amb.h, amb.z)))
add("ln-win", poly([P(amb.x+52, 18, amb.z), P(amb.x+72, 18, amb.z),
                    P(amb.x+72, 34, amb.z), P(amb.x+52, 34, amb.z)]))
for wx in (amb.x+15, amb.x+62):
    add("ln-thin", disc(wx, 6, amb.z, 8))
add("ln-mark", line(P(amb.x+20, 24, amb.z), P(amb.x+20, 34, amb.z)),
               line(P(amb.x+15, 29, amb.z), P(amb.x+25, 29, amb.z)))

# --- emit: flat, in painter's order, timing carried as a class -------------
_xs = [x for x, _ in PTS]; _ys = [y for _, y in PTS]
_pad = 16
_x0, _y0 = min(_xs) - _pad, min(_ys) - _pad
_w, _h = (max(_xs) - min(_xs)) + 2*_pad, (max(_ys) - min(_ys)) + 2*_pad
_vb = "%.0f %.0f %.0f %.0f" % (_x0, _y0, _w, _h)
print("  viewBox: %s   aspect %.2f" % (_vb, _w / _h))

# fill/stroke as presentation attributes, so the drawing stays line art even
# if the stylesheet fails to load. CSS still overrides them per face class.
out = ['    <svg class="draw-art" viewBox="' + _vb + '" width="560" height="290" role="img"',
       '         aria-label="Axonometric line drawing of Sarada Nursing Home"',
       '         fill="none" stroke="currentColor" stroke-width="2"',
       '         stroke-linecap="round" stroke-linejoin="round">']
for cls, g, d in items:
    out.append('      <path class="%s g%d" pathLength="1" d="%s"/>' % (cls, g, d))
out.append('    </svg>')

open(OUT, 'w').write(chr(10).join(out))
from collections import Counter
print('%d paths' % len(items))
print('  by stage:', dict(sorted(Counter(g for _, g, _ in items).items())))
print('  by class:', dict(Counter(c for c, _, _ in items)))

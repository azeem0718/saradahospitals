#!/usr/bin/env python3
"""Axonometric line drawing of the nursing home, generated from 3D geometry.

    python3 tools/build-illustration.py

Writes includes/illustration.php directly — there is no paste step to forget.

Why generated: drawing axonometric perspective by hand does not hold up.
Parallel edges drift, and a window grid on the receding face never lines up
with the floor it belongs to. Here the building is boxes in 3D, projected
cabinet-style — the front face true, depth receding up and right at ANGLE
with DEPTH foreshortening — so all of that is arithmetic instead of eye.

Two things make the result read as a solid building rather than a wireframe:

  * only the faces this view can actually see are emitted, and a face butted
    flush against a neighbour (the main block's right side, against the stair
    core) is skipped entirely;
  * faces carry a tonal fill — top lightest, front mid, receding side
    darkest — and are emitted back-to-front within each stage, so nearer
    surfaces occlude the linework behind them.

The scene is authored in stages, and the stage number is the paint order AND
the animation order: ground and planting first, then massing, floors,
windows, the entrance, and finally the ambulance and figures. The CSS delays
(.g1 … .g6) are keyed to the same numbers, so the drawing assembles the way
an architect would sketch it.

Every path carries pathLength="1", letting one dash rule animate the draw-on
regardless of real path length.
"""

import math
import os
from collections import Counter

ANGLE = math.radians(30)
DEPTH = 0.5
CX, CY = 92, 372          # world origin in picture coordinates; +y is up

DEST = os.path.join(os.path.dirname(__file__), '..', 'includes', 'illustration.php')

# ---------------------------------------------------------------- projection

PTS = []   # every projected point, tracked so the viewBox can be fitted


def P(x, y, z):
    pt = (round(CX + x + z * DEPTH * math.cos(ANGLE), 1),
          round(CY - y - z * DEPTH * math.sin(ANGLE), 1))
    PTS.append(pt)
    return pt


def disc(x, y, z, r):
    """A circle in the picture plane, its extents registered for the fit."""
    cx, cy = P(x, y, z)
    PTS.extend([(cx - r, cy - r), (cx + r, cy + r)])
    return "M%s %sa%s %s 0 1 0 %s 0a%s %s 0 1 0 %s 0" % (cx - r, cy, r, r, 2 * r, r, r, -2 * r)


def poly(pts):
    return "M" + " L".join("%s %s" % p for p in pts) + "Z"


def line(a, b):
    return "M%s %s L%s %s" % (a[0], a[1], b[0], b[1])


class Box:
    """An axis-aligned box; corner (x, y, z), size (w, h, d)."""

    def __init__(self, x, y, z, w, h, d):
        self.x, self.y, self.z, self.w, self.h, self.d = x, y, z, w, h, d

    def c(self, i, j, k):
        return P(self.x + i * self.w, self.y + j * self.h, self.z + k * self.d)

    # The three faces a cabinet projection receding up-right can see.
    def front(self): return [self.c(0, 0, 0), self.c(1, 0, 0), self.c(1, 1, 0), self.c(0, 1, 0)]
    def side(self):  return [self.c(1, 0, 0), self.c(1, 0, 1), self.c(1, 1, 1), self.c(1, 1, 0)]
    def top(self):   return [self.c(0, 1, 0), self.c(1, 1, 0), self.c(1, 1, 1), self.c(0, 1, 1)]

    def faces(self, skip=()):
        """Visible faces, painter-ordered: top and side behind, front last."""
        out = []
        if 'top' not in skip:
            out.append(('f-top', poly(self.top())))
        if 'side' not in skip:
            out.append(('f-side', poly(self.side())))
        if 'front' not in skip:
            out.append(('f-front', poly(self.front())))
        return out


# ------------------------------------------------------------------- scene

items = []
_stage = [1]


def stage(n):
    _stage[0] = n


def add(cls, *paths):
    items.extend((cls, _stage[0], p) for p in paths)


def add_box(box, skip=()):
    for cls, d in box.faces(skip):
        add(cls, d)


def windows_front(b, cols, mw, mh, sill, lit=()):
    """A row of two-pane windows across a front face; `lit` columns glow."""
    gap = (b.w - cols * mw) / (cols + 1)
    for c in range(cols):
        o = b.x + gap + c * (mw + gap)
        cls = 'ln-win-lit' if c in lit else 'ln-win'
        add(cls, poly([P(o, b.y + sill, b.z), P(o + mw, b.y + sill, b.z),
                       P(o + mw, b.y + sill + mh, b.z), P(o, b.y + sill + mh, b.z)]))
        add('ln-thin', line(P(o + mw / 2, b.y + sill, b.z), P(o + mw / 2, b.y + sill + mh, b.z)))


def windows_side(b, cols, mw, mh, sill):
    gap = (b.d - cols * mw) / (cols + 1)
    X = b.x + b.w
    for c in range(cols):
        o = b.z + gap + c * (mw + gap)
        add('ln-win', poly([P(X, b.y + sill, o), P(X, b.y + sill, o + mw),
                            P(X, b.y + sill + mh, o + mw), P(X, b.y + sill + mh, o)]))


def tree(x, z, height, r):
    add('ln-thin', line(P(x, 0, z), P(x, height, z)))
    add('ln-thin', disc(x, height + r * 0.8, z, r))


def figure(x, z, h=26):
    """An architect's scale figure: a head over a stance of two legs."""
    head = h * 0.16
    hip = h * 0.45
    add('ln-thin', disc(x, h - head, z, head))
    add('ln-thin', line(P(x, h - head * 2.2, z), P(x, hip, z)))
    add('ln-thin', line(P(x, hip, z), P(x - 4, 0, z)))
    add('ln-thin', line(P(x, hip, z), P(x + 4, 0, z)))


FL = 44                                          # one storey

main   = Box(0, 0, 0, 244, FL * 4, 116)
p_main = Box(-6, FL * 4, -6, 250, 12, 128)       # parapet capping the main block
core   = Box(244, 0, 0, 80, FL * 4 + 40, 116)    # stair core, half a storey proud
p_core = Box(244, FL * 4 + 40, -6, 88, 12, 128)
canopy = Box(26, 40, -54, 108, 11, 54)           # entrance canopy slab
amb    = Box(356, 0, -22, 62, 32, 30)            # ambulance body
cab    = Box(418, 0, -22, 28, 24, 30)            # ambulance cab, stepped down

# --- stage 1: the ground the building stands on ----------------------------
stage(1)
add('ln-ground', line(P(-84, 0, 0), P(500, 0, 0)))
for hx in range(-72, 481, 46):                   # architect's hatch under the line
    add('ln-hatch', line(P(hx, 0, 0), P(hx - 9, -8, 0)))
tree(-54, 50, 54, 27)
tree(486, 30, 40, 19)
for sx in (6, 20):                               # shrubs by the entrance
    add('ln-thin', disc(sx, 7, -40, 7))

# --- stage 2: massing ------------------------------------------------------
stage(2)
add_box(main, skip=('side',))                    # core butts flush against it
add_box(core)

# --- stage 3: floors and the main parapet ----------------------------------
stage(3)
for k in range(1, 4):
    add('ln-thin', line(P(main.x, k * FL, main.z), P(main.x + main.w, k * FL, main.z)))
for k in range(1, 5):
    add('ln-thin', line(P(core.x, k * FL, core.z), P(core.x + core.w, k * FL, core.z)))
    add('ln-thin', line(P(core.x + core.w, k * FL, core.z), P(core.x + core.w, k * FL, core.z + core.d)))
add_box(p_main, skip=('side',))

# --- stage 4: windows, a few of them lit -----------------------------------
stage(4)
LIT = {1: (2,), 2: (0,), 3: (3,)}                # floor -> lit columns
for k in range(4):
    b = Box(main.x, k * FL, main.z, main.w, FL, main.d)
    windows_front(b, 4, 38, 22, 12, lit=LIT.get(k, ()))
for k in range(4):
    b = Box(core.x, k * FL, core.z, core.w, FL, core.d)
    windows_front(b, 2, 26, 24, 10, lit=(0,) if k == 1 else ())
    windows_side(b, 2, 30, 24, 10)

# --- stage 5: the entrance, and the cross on the core ----------------------
stage(5)
add_box(p_core)
cxx, cyy = core.x + 40, p_core.y + 6
add('ln-mark', line(P(cxx, cyy, p_core.z), P(cxx, cyy + 9, p_core.z)),
               line(P(cxx - 4.5, cyy + 4.5, p_core.z), P(cxx + 4.5, cyy + 4.5, p_core.z)))

d0, d1 = 56, 112                                 # glazed double door
add('ln-win', poly([P(d0, 0, 0), P(d1, 0, 0), P(d1, 34, 0), P(d0, 34, 0)]))
add('ln-thin', line(P((d0 + d1) / 2, 0, 0), P((d0 + d1) / 2, 34, 0)))

add_box(canopy)
for px in (canopy.x + 7, canopy.x + canopy.w - 7):
    add('ln-thin', line(P(px, 0, canopy.z), P(px, canopy.y, canopy.z)))

# signboard on the canopy fascia: a plate and the suggestion of lettering
sb = Box(canopy.x + 24, canopy.y + 1.5, canopy.z, 60, 8, 0)
add('ln-win', poly(sb.front()))
add('ln-thin', line(P(sb.x + 7, sb.y + 4, sb.z), P(sb.x + sb.w - 7, sb.y + 4, sb.z)))

add('f-front', poly([P(canopy.x - 10, 0, canopy.z - 14), P(canopy.x + canopy.w + 10, 0, canopy.z - 14),
                     P(canopy.x + canopy.w + 10, 0, canopy.z), P(canopy.x - 10, 0, canopy.z)]))

# --- stage 6: life out front — ambulance, kerb, people ---------------------
stage(6)
add('ln-hatch', line(P(338, 0, -48), P(474, 0, -48)))   # kerb line, a step nearer

add_box(amb)
add_box(cab, skip=('top',))                             # roof read from amb's top
add('f-top', poly([cab.c(0, 1, 0), cab.c(1, 1, 0), cab.c(1, 1, 1), cab.c(0, 1, 1)]))
add('ln-thin', line(P(cab.x, 0, cab.z), P(cab.x, cab.h, cab.z)))
add('ln-win', poly([P(cab.x + 5, 13, cab.z), P(cab.x + 23, 13, cab.z),      # windscreen
                    P(cab.x + 23, 21, cab.z), P(cab.x + 5, 21, cab.z)]))
add('ln-win', poly([P(amb.x + 38, 16, amb.z), P(amb.x + 56, 16, amb.z),     # rear window
                    P(amb.x + 56, 27, amb.z), P(amb.x + 38, 27, amb.z)]))
for wx in (amb.x + 13, cab.x + 14):
    add('ln-thin', disc(wx, 5.5, amb.z, 7))
    add('ln-thin', disc(wx, 5.5, amb.z, 2.5))
add('ln-mark', line(P(amb.x + 22, 15, amb.z), P(amb.x + 22, 25, amb.z)),    # red cross
               line(P(amb.x + 17, 20, amb.z), P(amb.x + 27, 20, amb.z)))
lb = Box(amb.x + 20, amb.h, -12, 16, 4, 8)                                   # light bar
add('ln-mark', poly(lb.front()))

figure(146, -60)
figure(170, -64, h=23)

# ------------------------------------------------------------------ emit

xs = [x for x, _ in PTS]
ys = [y for _, y in PTS]
pad = 16
x0, y0 = min(xs) - pad, min(ys) - pad
w, h = (max(xs) - min(xs)) + 2 * pad, (max(ys) - min(ys)) + 2 * pad
vb = "%.0f %.0f %.0f %.0f" % (x0, y0, w, h)
disp_w = 560
disp_h = round(disp_w * h / w)

svg = ['    <svg class="draw-art" viewBox="%s" width="%d" height="%d" role="img"' % (vb, disp_w, disp_h),
       '         aria-label="Axonometric line drawing of Sarada Nursing Home"',
       '         fill="none" stroke="currentColor" stroke-width="2"',
       '         stroke-linecap="round" stroke-linejoin="round">']
for cls, g, d in items:
    svg.append('      <path class="%s g%d" pathLength="1" d="%s"/>' % (cls, g, d))
svg.append('    </svg>')

php = """<?php
/**
 * Axonometric line drawing of the nursing home.
 *
 * GENERATED by tools/build-illustration.py — do not hand-edit. The geometry
 * is defined in 3D there and projected; that is the only way parallel edges
 * stay parallel and the window grids stay aligned on the receding face.
 * Run the script again to change the building.
 */

declare(strict_types=1);

function hospital_illustration(): string
{
    return <<<'SVG'
%s
    SVG;
}
""" % "\n".join(svg)

with open(DEST, 'w', encoding='utf-8') as fh:
    fh.write(php)

print('viewBox %s   aspect %.2f   display %dx%d' % (vb, w / h, disp_w, disp_h))
print('%d paths' % len(items))
print('  by stage:', dict(sorted(Counter(g for _, g, _ in items).items())))
print('  by class:', dict(Counter(c for c, _, _ in items)))
print('wrote', os.path.relpath(DEST))

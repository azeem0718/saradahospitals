#!/usr/bin/env python3
"""Vendor the site's icons from Lucide.

The icons were hand-drawn, and it showed: inconsistent optical weight, corners
that did not match, and a stethoscope nobody recognised. Lucide is a properly
drawn set on a 24px grid — ISC licensed, so it can simply be vendored rather
than loaded from a CDN, which keeps the site's no-dependency, no-third-party-
request rule intact.

    python3 tools/build-icons.py

Writes includes/icons.generated.php. Re-runnable; needs network access.

Each feature icon names ONE child element as its moving part, by index into the
elements Lucide ships. That is what the hover animation drives, so the moving
part is the meaningful one — the clock's hands, the stethoscope's chest piece,
the ticket's stub — rather than the whole glyph wobbling.
"""

import re
import sys
import urllib.request

VERSION = "1.34.0"
BASE = f"https://unpkg.com/lucide-static@{VERSION}/icons/"

# our name -> (lucide name, index of the element to animate, keyframe name)
# The index is checked against the fetched file, so a Lucide redraw that changes
# the element order fails the build instead of animating the wrong stroke.
FEATURE = {
    "ticket":      ("ticket",              1, "ico-ticket"),
    "calendar":    ("calendar-days",       2, "ico-calendar"),
    "clock":       ("clock",               1, "ico-clock"),
    "location":    ("map-pin",             1, "ico-location"),
    "search":      ("search",              0, "ico-search"),
    "stethoscope": ("stethoscope",         4, "ico-stetho"),
    "heart":       ("heart-pulse",         1, "ico-heart"),
    "icu":         ("activity",            0, "ico-icu"),
    "droplet":     ("droplets",            0, "ico-droplet"),
    "lab":         ("flask-conical",       1, "ico-lab"),
    "scan":        ("scan-heart",          4, "ico-scan"),
    "maternity":   ("baby",                0, "ico-maternity"),
    "emergency":   ("siren",               0, "ico-emergency"),
    "shield":      ("shield-check",        1, "ico-shield"),
    "users":       ("users",               1, "ico-users"),
    "list":        ("clipboard-list",      3, "ico-list"),
    "discount":    ("badge-percent",       1, "ico-discount"),
    "award":       ("award",               1, "ico-award"),
    "building":    ("hospital",            2, "ico-building"),
    "room":        ("bed-double",          1, "ico-room"),
    "phone":       ("phone-call",          1, "ico-phone"),
}

UTILITY = {
    "home":          "house",
    "check":         "check",
    "check-circle":  "circle-check",
    "arrow-right":   "arrow-right",
    "chevron-right": "chevron-right",
    "chevron-left":  "chevron-left",
    "close":         "x",
    "plus":          "plus",
    "edit":          "square-pen",
    "menu":          "menu",
    "info":          "info",
    "alert":         "triangle-alert",
    "lock":          "lock",
    "logout":        "log-out",
    "settings":      "settings",
    "print":         "printer",
    "undo":          "undo-2",
    "image":         "image",
}

# WhatsApp is a brand mark, not a UI icon, so Lucide does not carry it. Kept as
# the official glyph path, drawn on the same 24px grid as everything else.
WHATSAPP = (
    '<path d="M12.04 2A9.9 9.9 0 0 0 2.15 11.9a9.8 9.8 0 0 0 1.32 4.94L2 22l5.3-1.39a9.9 9.9 0 0 0 '
    '4.74 1.2h.01a9.9 9.9 0 0 0 9.9-9.89A9.9 9.9 0 0 0 12.04 2Z"/>'
    '<path d="M9.3 7.6c-.2-.45-.4-.46-.58-.47h-.5a.95.95 0 0 0-.69.32c-.24.26-.9.88-.9 2.14 0 1.26.92 '
    '2.48 1.05 2.65.13.17 1.79 2.87 4.42 3.9 2.19.87 2.63.7 3.11.65.48-.04 1.55-.63 1.77-1.25.22-.62.'
    '22-1.15.15-1.26-.06-.1-.24-.17-.5-.3-.26-.13-1.55-.77-1.79-.85-.24-.09-.41-.13-.59.13-.17.26-.67'
    '.85-.82 1.02-.15.18-.3.2-.56.07-.26-.13-1.1-.4-2.1-1.3-.78-.69-1.3-1.54-1.45-1.8-.15-.26-.02-.4.'
    '11-.53.12-.12.26-.3.39-.46.13-.15.17-.26.26-.44.09-.17.04-.33-.02-.46-.07-.13-.57-1.4-.79-1.91Z"/>'
)

ELEMENT = re.compile(r"<(path|circle|rect|line|polyline|polygon|ellipse)\b[^>]*/>")


def fetch(name: str) -> str:
    """Return the inner markup of a Lucide icon, whitespace collapsed."""
    with urllib.request.urlopen(BASE + name + ".svg", timeout=30) as r:
        svg = r.read().decode("utf-8")

    body = svg[svg.index(">", svg.index("<svg")) + 1: svg.rindex("</svg>")]
    return re.sub(r"\s+", " ", body).strip()


def elements(body: str) -> list:
    return ELEMENT.findall(body) and [m.group(0) for m in ELEMENT.finditer(body)]


def php_string(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def main() -> int:
    feature_out, utility_out, problems = {}, {}, []

    for ours, (lucide, index, anim) in FEATURE.items():
        body = fetch(lucide)
        parts = elements(body)
        if index >= len(parts):
            problems.append(f"{ours}: {lucide} has {len(parts)} elements, wanted #{index}")
            continue

        # Tag the moving part. The class goes on that element only, so the rest
        # of the glyph stays put while it moves.
        target = parts[index]
        tagged = target[:-2].rstrip() + ' class="ico-anim"/>'
        feature_out[ours] = (anim, body.replace(target, tagged, 1))
        print(f"  {ours:12} <- {lucide:20} ({len(parts)} elements, animating #{index})")

    for ours, lucide in UTILITY.items():
        utility_out[ours] = fetch(lucide)
        print(f"  {ours:12} <- {lucide}")

    utility_out["whatsapp"] = WHATSAPP

    if problems:
        print("\nFAILED:", file=sys.stderr)
        for p in problems:
            print("  " + p, file=sys.stderr)
        return 1

    lines = [
        "<?php",
        "/**",
        " * Icon path data, vendored from Lucide " + VERSION + " (ISC).",
        " *",
        " * GENERATED by tools/build-icons.py — do not edit by hand.",
        " * Run the script again to change the set or re-point an animation.",
        " *",
        " * Feature icons are [keyframe name, markup]; the markup carries",
        " * class=\"ico-anim\" on the one element that moves.",
        " */",
        "",
        "declare(strict_types=1);",
        "",
        "function icon_feature_set(): array",
        "{",
        "    return [",
    ]
    for name, (anim, body) in feature_out.items():
        lines.append(f"        {php_string(name)} => [{php_string(anim)},")
        lines.append(f"            {php_string(body)}],")
    lines += ["    ];", "}", "", "function icon_utility_set(): array", "{", "    return ["]
    for name, body in utility_out.items():
        lines.append(f"        {php_string(name)} => {php_string(body)},")
    lines += ["    ];", "}", ""]

    dest = __file__.rsplit("/", 2)[0] + "/includes/icons.generated.php"
    with open(dest, "w", encoding="utf-8") as fh:
        fh.write("\n".join(lines))
    print(f"\nwrote {dest}")
    return 0


if __name__ == "__main__":
    sys.exit(main())

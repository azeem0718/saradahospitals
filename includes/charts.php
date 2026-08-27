<?php
/**
 * Server-rendered SVG charts for the analytics screen.
 *
 * No charting library — the same no-dependency rule as the rest of the site.
 * The server draws the SVG, the CSS makes it responsive, and a few lines of
 * JS add hover tooltips on top. Every chart ships with a table twin, so
 * nothing is readable only through color or hover.
 *
 * The two series colors are validated, not eyeballed: navy #22598b and gold
 * #ab7d16 clear the colorblind-separation and 3:1 contrast checks on a white
 * panel (dataviz validator, light mode). Bars stay thin, data-ends are
 * rounded while baselines stay square, and stacked segments are parted by a
 * 2px gap of the surface — never a drawn border.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const CHART_SERIES = ['#22598b', '#ab7d16'];

/**
 * A clean axis ceiling and tick step for a data maximum.
 *
 * @return array{0: int, 1: int} [ceiling, step]
 */
function chart_scale(int $max, int $ticks = 4): array
{
    $max = max(1, $max);
    if ($max <= $ticks) {
        return [max(2, $max), 1];
    }

    $rough = $max / $ticks;
    $power = 10 ** (int) floor(log10($rough));
    $step  = $power * 10;
    foreach ([1, 2, 2.5, 5, 10] as $m) {
        if ($m === 2.5 && $power < 10) {
            continue; // fractional steps on a unit scale
        }
        if ($power * $m * $ticks >= $max) {
            $step = $power * $m;
            break;
        }
    }
    $step = (int) round($step);
    return [(int) (ceil($max / $step) * $step), $step];
}

/** A column with a rounded top and a square baseline. */
function chart_bar_path(float $x, float $y, float $w, float $h, float $r = 4.0): string
{
    $r = min($r, $w / 2, $h);
    return sprintf(
        'M%.1f %.1f V%.1f Q%.1f %.1f %.1f %.1f H%.1f Q%.1f %.1f %.1f %.1f V%.1f Z',
        $x, $y + $h,                       // bottom left
        $y + $r,                           // up the left side
        $x, $y, $x + $r, $y,               // top-left corner
        $x + $w - $r,                      // across the top
        $x + $w, $y, $x + $w, $y + $r,     // top-right corner
        $y + $h                            // down to the baseline
    );
}

/**
 * Stacked column chart (a single series is just a one-deep stack).
 *
 * @param list<array{label: string, title: string, values: list<int>}> $columns
 *        label goes on the x-axis, title heads the tooltip.
 * @param list<string> $series     Series names, bottom of the stack first.
 * @param int          $labelEvery Draw every Nth x label; the rest stay in
 *                                 the tooltip and the table.
 */
function svg_column_chart(array $columns, array $series, int $labelEvery = 1): string
{
    // The stylesheet caps the rendered chart at this same width, so one
    // viewBox unit stays roughly one pixel and the mark specs (24px bars,
    // 2px gaps, 10px ticks) mean what they say.
    $width  = 1080;
    $height = 280;
    $pad    = ['top' => 14, 'right' => 8, 'bottom' => 22, 'left' => 34];
    $plotW  = $width - $pad['left'] - $pad['right'];
    $plotH  = $height - $pad['top'] - $pad['bottom'];
    $baseY  = $pad['top'] + $plotH;

    $totals = array_map(static fn ($c) => array_sum($c['values']), $columns);
    [$ceil, $step] = chart_scale($totals ? max($totals) : 1);
    $yFor = static fn (float $v): float => $baseY - ($v / $ceil) * $plotH;

    $n     = max(1, count($columns));
    $bandW = $plotW / $n;
    $barW  = min(24.0, max(3.0, $bandW - 6));
    $gap   = 2.0; // the surface showing between stacked segments

    $maxIndex = $totals ? (int) array_search(max($totals), $totals, true) : -1;

    // On a phone the chart scrolls sideways at a readable size instead of
    // shrinking to fit; the wrapper provides the scrolling.
    $svg = '<div class="chart-scroll"><svg class="chart" viewBox="0 0 ' . $width . ' ' . $height . '"'
         . ' role="img" preserveAspectRatio="xMidYMid meet">';

    // Recessive grid: solid hairlines, one step off the surface.
    for ($v = 0; $v <= $ceil; $v += $step) {
        $y = $yFor($v);
        $svg .= sprintf(
            '<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="%s" stroke-width="1"/>',
            $pad['left'], $y, $width - $pad['right'], $y,
            $v === 0 ? '#bcc5d0' : '#eceae5'
        );
        if ($v > 0) {
            $svg .= sprintf(
                '<text x="%d" y="%.1f" text-anchor="end" class="chart-tick">%s</text>',
                $pad['left'] - 6, $y + 3, number_format($v)
            );
        }
    }

    foreach ($columns as $i => $column) {
        $x = $pad['left'] + $bandW * $i + ($bandW - $barW) / 2;

        $rows = [];
        foreach ($series as $s => $name) {
            $rows[] = ['name' => $name, 'value' => (int) ($column['values'][$s] ?? 0)];
        }

        $svg .= '<g class="chart-col" data-title="' . e($column['title']) . '"'
              . ' data-rows="' . e(json_encode($rows)) . '"'
              . ' data-total="' . (int) $totals[$i] . '">';

        // Segments bottom-up; only the stack's top edge is the data-end, so
        // only the topmost non-zero segment gets the rounding.
        $top = -1;
        foreach ($column['values'] as $s => $v) {
            if ($v > 0) {
                $top = $s;
            }
        }
        $running = 0.0;
        foreach ($column['values'] as $s => $v) {
            if ($v <= 0) {
                continue;
            }
            $segTop    = $yFor($running + $v);
            $segBottom = $yFor($running) - ($running > 0 ? $gap : 0);
            $h         = max(1.0, $segBottom - $segTop);
            $color     = CHART_SERIES[$s % count(CHART_SERIES)];
            $svg      .= $s === $top
                ? '<path d="' . chart_bar_path($x, $segTop, $barW, $h) . '" fill="' . $color . '"/>'
                : sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="%s"/>',
                          $x, $segTop, $barW, $h, $color);
            $running += $v;
        }

        // One selective direct label: the busiest column carries its total.
        if ($i === $maxIndex && $totals[$i] > 0) {
            $svg .= sprintf(
                '<text x="%.1f" y="%.1f" text-anchor="middle" class="chart-peak">%d</text>',
                $x + $barW / 2, $yFor($totals[$i]) - 5, $totals[$i]
            );
        }

        if ($i % $labelEvery === 0) {
            $svg .= sprintf(
                '<text x="%.1f" y="%d" text-anchor="middle" class="chart-tick">%s</text>',
                $x + $barW / 2, $height - 6, e($column['label'])
            );
        }

        // The hit target is the whole band, not the painted bar, and it can
        // take keyboard focus: focus shows the same tooltip as hover.
        $spoken = $column['title'] . ': ' . implode(', ', array_map(
            static fn ($r) => $r['name'] . ' ' . $r['value'],
            $rows
        )) . (count($series) > 1 ? ', total ' . $totals[$i] : '');
        $svg .= sprintf(
            '<rect class="chart-hit" x="%.1f" y="%d" width="%.1f" height="%d"'
            . ' fill="transparent" tabindex="0" role="img" aria-label="%s"/>',
            $pad['left'] + $bandW * $i, $pad['top'], $bandW, $plotH, e($spoken)
        );
        $svg .= '</g>';
    }

    return $svg . '</svg></div>';
}

/** Legend chips for a multi-series chart. Text stays in ink; color is a swatch. */
function chart_legend(array $series): string
{
    $out = '<div class="chart-legend">';
    foreach ($series as $i => $name) {
        $out .= '<span class="chart-key"><span class="chart-swatch" style="background:'
              . CHART_SERIES[$i % count(CHART_SERIES)] . '"></span>' . e($name) . '</span>';
    }
    return $out . '</div>';
}

/**
 * The chart's table twin, collapsed under a disclosure. Every number the
 * chart paints is readable here without color or hover.
 *
 * @param list<array{label: string, title: string, values: list<int>}> $columns
 */
function chart_table(array $columns, array $series, string $caption): string
{
    $out = '<details class="chart-tv"><summary>View as table</summary>'
         . '<div class="table-wrap"><table class="chart-table"><caption class="sr-only">'
         . e($caption) . '</caption><thead><tr><th scope="col">Date</th>';
    foreach ($series as $name) {
        $out .= '<th scope="col">' . e($name) . '</th>';
    }
    if (count($series) > 1) {
        $out .= '<th scope="col">Total</th>';
    }
    $out .= '</tr></thead><tbody>';
    foreach ($columns as $column) {
        $out .= '<tr><th scope="row">' . e($column['title']) . '</th>';
        foreach ($column['values'] as $v) {
            $out .= '<td>' . (int) $v . '</td>';
        }
        if (count($series) > 1) {
            $out .= '<td><strong>' . array_sum($column['values']) . '</strong></td>';
        }
        $out .= '</tr>';
    }
    return $out . '</tbody></table></div></details>';
}

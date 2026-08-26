<?php
/**
 * Generate the -sm variants of the committed site images.
 *
 *     php tools/make-small-images.php
 *
 * Phones fetch the -sm file (720px wide) instead of the full banner or card;
 * on a small screen the difference is invisible and the bytes are roughly a
 * third. Reception's own uploads get their -sm made at upload time by
 * includes/uploads.php — this script only covers the files shipped in git.
 * Re-runnable; existing -sm files are rebuilt.
 */

declare(strict_types=1);

const SM_WIDTH   = 768;
const SM_QUALITY = 74;

$dir = dirname(__DIR__) . '/assets/img/site';

foreach (glob($dir . '/*.jpg') ?: [] as $file) {
    if (str_ends_with($file, '-sm.jpg')) {
        continue;
    }

    $src = imagecreatefromjpeg($file);
    $w   = imagesx($src);
    $h   = imagesy($src);

    if ($w <= SM_WIDTH) {
        echo basename($file), " already {$w}px wide, skipped\n";
        continue;
    }

    $nh  = (int) round($h * SM_WIDTH / $w);
    $dst = imagecreatetruecolor(SM_WIDTH, $nh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, SM_WIDTH, $nh, $w, $h);

    $out = substr($file, 0, -4) . '-sm.jpg';
    imagejpeg($dst, $out, SM_QUALITY);
    printf("%s  %dx%d  %dKB\n", basename($out), SM_WIDTH, $nh, (int) (filesize($out) / 1024));
}

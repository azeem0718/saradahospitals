<?php
/**
 * Build the logo badge set from the hospital's own mark.
 *
 *     php tools/make-logo.php
 *
 * The artwork's hands open out of the heart, so their white IS the page's
 * white — cutting the background to transparency guts the hands on any dark
 * ground. Instead the mark sits on a white disc with a faint ring: exact
 * artwork everywhere, on ivory, navy and red alike.
 *
 * Reads assets/img/logo/source.png, writes the sized badges next to it.
 */

declare(strict_types=1);

$dir = dirname(__DIR__) . '/assets/img/logo';
$src = imagecreatefrompng($dir . '/source.png');
$sw  = imagesx($src);

// Compose once at high resolution, downsample per size.
//
// The source is a SQUARE with a white background, so it cannot simply be
// pasted over the disc — its corners would poke out past the circle and
// haunt every dark ground as a faint white square. Instead the square is
// composed flat, and a per-pixel circular mask decides what survives:
// inside the disc, the ring band around it, transparency beyond — with a
// one-pixel soft edge so the rim stays smooth after downsampling.
$big  = 1536;
$flat = imagecreatetruecolor($big, $big);
imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
$art = (int) ($big * 0.90);
imagecopyresampled($flat, $src, (int) (($big - $art) / 2), (int) (($big - $art) / 2), 0, 0, $art, $art, $sw, $sw);

$c = imagecreatetruecolor($big, $big);
imagealphablending($c, false);
imagesavealpha($c, true);

$mid   = $big / 2;
$rOut  = $big / 2 - 2;        // outer edge of the ring
$rIn   = $rOut - 14;          // where the ring band starts
$ring  = [205, 214, 224];     // navy-tinted hairline

for ($y = 0; $y < $big; $y++) {
    for ($x = 0; $x < $big; $x++) {
        $d = sqrt(($x - $mid) ** 2 + ($y - $mid) ** 2);

        if ($d > $rOut + 1) {
            $px = imagecolorallocatealpha($c, 0, 0, 0, 127);
        } elseif ($d > $rIn) {
            // Ring band; feather the outermost pixel into transparency.
            $a  = (int) round(min(1, $rOut + 1 - $d) * 127);
            $px = imagecolorallocatealpha($c, $ring[0], $ring[1], $ring[2], 127 - $a);
        } else {
            $rgb = imagecolorat($flat, $x, $y);
            $px  = imagecolorallocatealpha($c, ($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255, 0);
        }
        imagesetpixel($c, $x, $y, $px);
    }
}

foreach ([512, 192, 96, 48] as $size) {
    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    imagecopyresampled($out, $c, 0, 0, 0, 0, $size, $size, $big, $big);
    imagepng($out, $dir . '/badge-' . $size . '.png', 9);
    printf("badge-%d.png  %dKB\n", $size, (int) (filesize($dir . '/badge-' . $size . '.png') / 1024));
}

// Apple touch icon: opaque white square, rounded by iOS itself.
$apple = imagecreatetruecolor(180, 180);
imagefill($apple, 0, 0, imagecolorallocate($apple, 255, 255, 255));
$aart = 164;
imagecopyresampled($apple, $src, (180 - $aart) / 2, (180 - $aart) / 2, 0, 0, $aart, $aart, $sw, $sw);
imagepng($apple, $dir . '/apple-touch-icon.png', 9);
printf("apple-touch-icon.png  %dKB\n", (int) (filesize($dir . '/apple-touch-icon.png') / 1024));

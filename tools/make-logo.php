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
$big  = 2048;
$c    = imagecreatetruecolor($big, $big);
imagealphablending($c, false);
imagesavealpha($c, true);
imagefill($c, 0, 0, imagecolorallocatealpha($c, 0, 0, 0, 127));
imagealphablending($c, true);

// Faint navy-tinted ring, then the white disc inside it.
imagefilledellipse($c, $big/2, $big/2, $big - 8, $big - 8, imagecolorallocate($c, 205, 214, 224));
imagefilledellipse($c, $big/2, $big/2, $big - 40, $big - 40, imagecolorallocate($c, 255, 255, 255));

// The mark, at 76% of the disc. The artwork's own margins are generous, so
// this lands the heart comfortably inside the ring.
$art = (int) ($big * 0.90);
imagecopyresampled($c, $src, (int) (($big - $art) / 2), (int) (($big - $art) / 2), 0, 0, $art, $art, $sw, $sw);

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

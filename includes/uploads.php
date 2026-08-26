<?php
/**
 * Image uploads.
 *
 * Shared by the doctor profiles and the site images screen. The rules are the
 * same in both places, so they live in one file: a file is accepted only if PHP
 * can read it as an image, and it is written under a name and an extension we
 * choose — never one the browser supplied.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** Largest upload we accept, in bytes. Phone photos clear this comfortably. */
const UPLOAD_MAX_BYTES = 8 * 1024 * 1024;

/** Where a given store's files live, absolute. */
function upload_dir(string $store): string
{
    return dirname(__DIR__) . '/assets/img/' . $store;
}

/**
 * Store an uploaded image and return its filename.
 *
 * @param array  $file  One entry from $_FILES.
 * @param string $store Subdirectory of assets/img/ to write into.
 * @param string $stem  Filename prefix, e.g. a slug.
 *
 * @return array{0: ?string, 1: ?string} [filename, error]. Both null when no
 *         file was submitted, which is not an error — most saves leave the
 *         existing image alone.
 */
function store_upload(array $file, string $store, string $stem): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'That image is too large for the server to accept.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'The image did not upload. Please try again.'];
    }
    if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        return [null, 'Please use an image under 8 MB.'];
    }

    // getimagesize parses the file. A .php renamed to .jpg does not get past it.
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return [null, 'That file is not an image we can read. Use JPG, PNG or WebP.'];
    }

    $ext = match ($info[2]) {
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        default        => null,
    };
    if ($ext === null) {
        return [null, 'Use a JPG, PNG or WebP image.'];
    }

    $dir = upload_dir($store);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return [null, 'The image folder could not be created on the server.'];
    }

    $stem = preg_replace('/[^a-z0-9-]+/', '-', strtolower($stem)) ?: 'image';
    $stem = trim($stem, '-') ?: 'image';

    // The random suffix means replacing an image produces a new URL, so a
    // browser holding the old one under the site's month-long cache header
    // picks up the change immediately.
    $name = substr($stem, 0, 48) . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return [null, 'The image could not be saved on the server.'];
    }

    return [$name, null];
}

/**
 * Delete a stored image.
 *
 * Anything that is not a plain filename is ignored rather than resolved, so a
 * stored value of "../../index.php" removes nothing.
 */
function delete_upload(?string $name, string $store): void
{
    if ($name === null || $name === '' || basename($name) !== $name) {
        return;
    }
    $path = upload_dir($store) . '/' . $name;
    if (is_file($path)) {
        @unlink($path);
    }
}

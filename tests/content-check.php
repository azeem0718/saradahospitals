<?php
/**
 * Every editable field must actually reach the page it claims to edit.
 *
 * The admin panel's promise is that what it lists is what the site shows. That
 * promise is easy to break silently: a field gets added to the registry and the
 * template never reads it, or a template is rewritten and stops reading a field
 * that is still offered. Either way the panel accepts an edit, says "saved",
 * and the website does not change — which is indistinguishable, from the desk,
 * from the panel being broken. Reading the code does not catch it; three of
 * these were live before this test existed.
 *
 * So it is checked the only way that settles it: put a value nobody would type
 * into every field in turn, fetch the page that field belongs to, and look for
 * it. Then put the field back exactly as it was — including, for a field the
 * hospital has genuinely edited, the hospital's own wording. An earlier version
 * of this file believed content_forget() removed a key. It does not; it only
 * clears a cache. The result was 105 sentinel strings left sitting in the
 * content table, which against a live site would have replaced every edited
 * field with gibberish. Restoring by remembering the previous value is both the
 * fix and the reason the restore is verified at the end.
 *
 * Fields marked 'only' apply in one site mode — the classic hero's headline is
 * not on the page while the slideshow is running — so those are checked with
 * that mode turned on, and the mode is restored afterwards.
 *
 * Run it against a site serving from THIS working copy and its database:
 *
 *   BASE=http://127.0.0.1:8099 php tests/content-check.php
 *
 * It writes to the content table and puts everything back, so point it at a
 * development database, never production.
 */

declare(strict_types=1);

chdir(dirname(__DIR__));
require 'includes/db.php';
require 'includes/content.php';

$base = rtrim(getenv('BASE') ?: 'http://127.0.0.1:8099', '/') . '/';

/* Which public file shows each registry page. The registry names its own URL,
   so this reads it rather than keeping a second list that could disagree. */
$pageFile = [];
foreach (content_pages() as $slug => $page) {
    $pageFile[$slug] = $page['url'] ?? ($slug . '.php');
}

function fetch_page(string $url): string
{
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_PROXY          => '',      // never go out through a proxy for localhost
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($c);
    $code = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    if ($code !== 200) {
        fwrite(STDERR, "  ! $url answered HTTP $code\n");
    }
    return (string) $body;
}

$heroWas = setting('hero_style', 'slides');
$setMode = static function (string $mode): void {
    db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute(['hero_style', $mode]);
};

/* Everything the hospital has actually edited, captured before a single write
   so it can be put back byte for byte. A key absent here has no override and
   is restored by saving '' , which is how content_save() unsets one. */
$before  = content_overrides(true);

$checked = 0;
$dead    = [];

foreach (content_pages() as $slug => $page) {
    $file = $pageFile[$slug];
    foreach ($page['blocks'] as $key => $spec) {
        $mode = ($spec['only'] ?? '') === 'classic-hero' ? 'classic' : 'slides';
        $setMode($mode);

        $sentinel = 'CONTENTCHECK' . substr(md5($key), 0, 10);
        content_save([$key => $sentinel]);
        $html = fetch_page($base . $file);
        content_save([$key => $before[$key] ?? '']);

        $checked++;
        if (!str_contains($html, $sentinel)) {
            $dead[] = [$key, $file, $spec['label'] ?? $key];
        }
    }
}

$setMode($heroWas);

/* Prove the restore worked rather than assuming it. A test that quietly
   damages the content it was checking is worse than no test. */
$after = content_overrides(true);
$leaks = [];
foreach ($after as $key => $value) {
    if (str_starts_with((string) $value, 'CONTENTCHECK')) {
        $leaks[] = $key;
    }
}
if ($leaks || $after != $before) {
    echo "FAIL — the check did not leave the content table as it found it.\n";
    if ($leaks) {
        echo '  sentinels still stored: ' . implode(', ', array_slice($leaks, 0, 8)) . "\n";
    }
    $lost = array_diff_key($before, $after);
    $extra = array_diff_key($after, $before);
    if ($lost)  { echo '  overrides lost: '  . implode(', ', array_keys($lost))  . "\n"; }
    if ($extra) { echo '  overrides added: ' . implode(', ', array_keys($extra)) . "\n"; }
    exit(1);
}

echo "Checked $checked editable fields across " . count($pageFile) . " pages.\n";

if ($dead) {
    echo "\nFAIL — offered in the admin panel, never rendered by the site:\n";
    foreach ($dead as [$key, $file, $label]) {
        printf("  %-32s %-22s (%s)\n", $key, $file, $label);
    }
    echo "\nEither read the field in the template, or take it out of the registry.\n";
    exit(1);
}

echo "PASS — every editable field reaches its page.\n";

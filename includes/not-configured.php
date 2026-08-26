<?php
/**
 * Shown when includes/config.php is missing or the database cannot be reached.
 *
 * Standalone on purpose: it must render before the application has booted, so
 * it cannot use the site's helpers, fonts or stylesheet. Everything is inline.
 *
 * The page never prints credentials, table names or server paths. A visitor
 * who lands here sees only the hospital's phone numbers; the setup steps are
 * generic enough to be safe in public, being the same as the public README.
 */

declare(strict_types=1);

/**
 * @param string $headline  What has gone wrong, in plain words.
 * @param string $detail    Optional extra line. Never include credentials.
 * @param bool   $needsDb   True when the database is unreachable rather than
 *                          the config file being absent.
 */
function render_not_configured(string $headline, string $detail = '', bool $needsDb = false): never
{
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Retry-After: 3600');
    header('Cache-Control: no-store');

    $steps = $needsDb
        ? [
            'Check the database name, user and password in <code>includes/config.php</code> against hPanel → Databases → MySQL Databases. Hostinger prefixes both the database and the user with your account ID.',
            'Confirm the database user has all privileges on that database.',
            'Confirm the tables were imported: phpMyAdmin → your database → Import → <code>sql/schema.sql</code>, then <code>sql/seed.sql</code>.',
          ]
        : [
            'In hPanel → Databases → MySQL Databases, create a database and a user with all privileges, and note the full prefixed names and the password.',
            'In phpMyAdmin, select that database and import <code>sql/schema.sql</code>, then <code>sql/seed.sql</code>.',
            'In File Manager, open <code>public_html/includes/</code>, copy <code>config.example.php</code> to <code>config.php</code>, and fill in those credentials.',
            'Reload this page, then visit <code>/setup.php</code> once to create the administrator login.',
          ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sarada Nursing Home</title>
<meta name="robots" content="noindex, nofollow">
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100dvh;
    display: grid; place-items: center; padding: 2rem 1.25rem;
    font: 16px/1.65 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
    color: #39424e; background: #faf8f4;
  }
  .box {
    width: 100%; max-width: 560px; background: #fff;
    border: 1px solid #e2e0da; border-radius: 18px; overflow: hidden;
    box-shadow: 0 20px 44px -12px rgba(10,37,64,.16);
  }
  .head { background: #0a2540; color: #fff; padding: 1.75rem 1.75rem 1.6rem; text-align: center; }
  .head svg { width: 46px; height: 46px; margin: 0 auto .8rem; display: block; }
  .head h1 { margin: 0; font-size: 1.25rem; font-weight: 600; letter-spacing: -.01em; }
  .head p { margin: .3rem 0 0; font-size: .74rem; letter-spacing: .12em;
            text-transform: uppercase; color: #c9971f; }
  .body { padding: 1.75rem; }
  h2 { margin: 0 0 .5rem; font-size: 1.06rem; color: #0a2540; letter-spacing: -.01em; }
  p { margin: 0 0 1rem; }
  p:last-child { margin-bottom: 0; }
  .call {
    display: block; margin-bottom: 1.5rem; padding: 1.1rem 1.25rem;
    background: #fdf1f2; border: 1px solid #f8dcde; border-left: 3px solid #be1622;
    border-radius: 10px; color: #7f0f14; text-decoration: none;
  }
  .call strong { display: block; font-size: .95rem; margin-bottom: .3rem; }
  .call span { font-size: 1.3rem; font-weight: 700; letter-spacing: .01em; }
  .call small { display: block; margin-top: .3rem; font-size: .84rem; opacity: .85; }
  ol { margin: 0; padding-left: 1.3rem; font-size: .93rem; color: #55606e; }
  li { margin-bottom: .6rem; }
  li:last-child { margin-bottom: 0; }
  code {
    font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .87em;
    background: #f3efe8; padding: .1em .35em; border-radius: 4px; color: #103356;
  }
  details { margin-top: 1.5rem; border-top: 1px solid #eeece7; padding-top: 1.25rem; }
  summary { cursor: pointer; font-size: .9rem; font-weight: 600; color: #17456f; }
  details[open] summary { margin-bottom: .9rem; }
  .foot {
    padding: 1.1rem 1.75rem; background: #faf8f4; border-top: 1px solid #eeece7;
    font-size: .84rem; color: #6f7b8a; text-align: center;
  }
</style>
</head>
<body>
  <div class="box">
    <div class="head">
      <svg viewBox="0 0 64 64" role="img" aria-label="Sarada Nursing Home">
        <circle cx="32" cy="32" r="31" fill="#be1622"/>
        <path d="M32 45.2c-8.2-5.8-13.7-10.7-13.7-16.4 0-4.1 3.1-7.2 7-7.2 2.5 0 4.7 1.3 6.1 3.3l.6.9.6-.9a7.2 7.2 0 0 1 6.1-3.3c3.9 0 7 3.1 7 7.2 0 5.7-5.5 10.6-13.7 16.4z" fill="#fff"/>
        <path d="M32 26v9.2M27.4 30.6h9.2" stroke="#be1622" stroke-width="3" stroke-linecap="round"/>
      </svg>
      <h1>Sarada Nursing Home</h1>
      <p>Your Health is Our Responsibility</p>
    </div>

    <div class="body">
      <h2>Our website is being set up</h2>
      <p>
        The site is not available for a short while. The hospital is open as
        usual &mdash; please call us and we will help you straight away.
      </p>

      <a class="call" href="tel:+918341254590">
        <strong>24-hour emergency</strong>
        <span>83412 54590</span>
        <small>Reception: 08598-222299</small>
      </a>

      <details>
        <summary>Setting up this site? Read this</summary>
        <p style="font-size:.93rem"><?= htmlspecialchars($headline, ENT_QUOTES) ?></p>
        <?php if ($detail !== ''): ?>
          <p style="font-size:.93rem"><?= htmlspecialchars($detail, ENT_QUOTES) ?></p>
        <?php endif; ?>
        <ol>
          <?php foreach ($steps as $step): ?>
            <li><?= $step ?></li>
          <?php endforeach; ?>
        </ol>
        <p style="margin-top:1rem;font-size:.88rem;color:#6f7b8a">
          Full instructions are in <code>docs/DEPLOY.md</code> in the repository.
        </p>
      </details>
    </div>

    <div class="foot">
      Opposite ICICI Bank, Pamuru Road, Kandukur &middot; Open 24 hours
    </div>
  </div>
</body>
</html>
    <?php
    exit;
}

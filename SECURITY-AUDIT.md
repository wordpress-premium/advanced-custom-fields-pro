# Security Audit — Advanced Custom Fields PRO 6.8.9

- **Date:** 2026-08-28
- **Path:** this plugin directory only
- **Method:** static review of all 720 files (PHP, JS, CSS, SVG, images, translations, Composer autoload, JSON schemas). Hidden files included. No official WP Engine zip was used for comparison (scope is this folder only).
- **Not in this file:** how any license check was altered. That topic is out of scope.

## Verdict

No malware, webshell, remote-code loader, or hidden admin/backdoor was found in this copy.

One extra non-ACF file (a distributor Internet Shortcut) was present and has been deleted.

A license bypass is present in `acf.php`. It was left untouched, as requested.

## Actions taken

| Item | What it is | Action |
| --- | --- | --- |
| `README.url` | Windows Internet Shortcut pointing at a third-party short URL (`https://oyred.com/2owxt`). Not part of ACF. Typical distributor tracking/redirect extra. Does not execute PHP. | **Removed.** |
| License-related extra code in `acf.php` | License bypass (out of scope for this audit) | **Left in place.** No further description. |
| `.DS_Store` | macOS folder metadata | Left. Not executable. |

## What was scanned

- **Inventory:** 720 files. Extensions: php (340), svg (114), po (87), mo (52), png (45), json (37), js (22), css (14), gif (3), plus `composer.json`, `readme.txt`, `README.md`. No `.htaccess`, `.user.ini`, `php.ini`, double-extension PHP, shells, or unexpected binaries.
- **Hidden files:** only `.DS_Store`.
- **PHP in `assets/` and image dirs:** 51 `index.php` stubs, all identical ACF directory guards (`<?php // There are many ways to WordPress.`).
- **PHP headers:** every non-index, non-vendor, non-`lang/` PHP file (230/230) carries the WP Engine ACF file header.
- **Vendor:** Composer autoload only (`ACF\` → `src/`). No third-party packages.
- **Images:** `file(1)` reports real PNG/GIF; no PHP payloads in image/SVG/CSS/JSON.
- **Translations:** `.mo` files are GNU message catalogs. `.l10n.php` files are `return [ ... ]` gettext arrays.

## Pattern review (PHP)

Searched for execution, obfuscation, process control, remote include, user persistence, and dropper hooks.

| Pattern | Result |
| --- | --- |
| `eval`, `assert(`, `create_function`, `preg_replace /e`, `goto` packing | None in PHP |
| `gzinflate` / `gzuncompress` / `str_rot13` / `convert_uudecode` / hex-packed `chr()` chains | None |
| `shell_exec`, `passthru`, `system(`, `exec(`, `proc_open`, `popen` | None |
| Remote `include`/`require` of URLs | None |
| `wp_create_user` / `wp_insert_user` | None |
| Null bytes in PHP | None |
| `ionCube`, webshell names (c99, r57, WSO, FilesMan) | None |

Legitimate hits that are **not** malware:

- `base64_decode` in `includes/api/api-helpers.php` (`acf_decrypt`) and in `pro/updates.php` (license option decode). ACF crypto/license storage.
- `file_put_contents` in `includes/local-json.php` and `src/CLI/JsonCommand.php` (Local JSON / WP-CLI). Writes ACF JSON only.
- `wp_remote_post` in `includes/Updater/Updater.php` to ACF’s connect host (plugin update client).
- `wp_remote_post` in `includes/admin/admin-email-opt-in-banner.php` to `api.hsforms.com` (ACF admin email opt-in / HubSpot form).
- `call_user_func*` used for ACF block render callbacks, Composer autoload, and internal APIs — not request-driven `eval`.
- `$_GET` / `$_POST` use is normal ACF admin/save/nonce handling.

HTTP hosts in PHP (excluding `lang/` and the removed shortcut) are ACF, WP Engine, WordPress.org, gnu.org, schema.org, Google Maps, HubSpot forms, and comment/doc links (stackoverflow, gist, php-fig, wpastra locale docs, bhoover encrypt article). No unexpected C2 hosts in executable PHP.

## Pattern review (JS / CSS / SVG)

- `eval(` appears only in `assets/inc/timepicker/jquery-ui-timepicker-addon.js` (and its `.min.js`) to parse `time:*` HTML attributes. This is upstream jQuery UI Timepicker 1.6.3, not an injected loader.
- No other JS `eval`. No SVG `<script>` / `onerror` / `javascript:` / PHP tags.
- JS/CSS hosts are jQuery, Select2, W3C, GitHub, Apache/GPL license URLs, and trentrichardson.com (timepicker project).

## Limits

- Static review cannot prove a payload that is never present as text (for example, a future update dropped in from outside this folder).
- This copy was not byte-compared to WP Engine’s official 6.8.9 zip.
- WordPress core, other plugins, the database, and `wp-content/uploads` were not scanned.

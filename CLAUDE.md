# saybaewstudio.com — สายแบ้วสตูดิโอ

Children's / event photography studio site with a full album-management admin.
Plain PHP + PDO, **no framework, no Composer, no build step**. Owner: Pimnada.
Customer-facing UI is Thai; code comments and commit messages are English.

Follow the conventions of the file you are editing. Do not introduce a
framework, Composer, or a build pipeline without asking.

---

## Layout

Flat PHP at the repo root, one file per page, no router and no autoloader.

| | |
|---|---|
| Public | `index.php`, `albums.php`, `album.php`, `services.php`, `about.php`, `reviews.php`, `blog.php`, `article.php`, `contact.php`, `page.php`, `sitemap.php` |
| Admin | `admin*.php` (20 pages), gated by `require_admin()` / `require_owner()` |
| XHR endpoints | `api-upload.php`, `api-photos.php`, `api-sort.php` |
| Core | `db.php`, `lib.php`, `auth.php`, `image.php`, `mailer.php`, `seed.php` |
| Shared partials | `inc/` |
| Never deployed | `docs/`, `tools/`, `*.sh`, `*.md` (excluded in `deploy.sh`) |

Local dev runs on **SQLite** (`config.php` sets `DB_DRIVER = 'sqlite'`);
production runs MySQL. `db.php` creates and patches the schema on the next
request — there is no migrate step.

---

## Traps that have already bitten once

**Start the session before any output.** `inc/header.php` calls
`boot_session()` as its first statement. If a page echoes anything before
including the header, `session_start()` lands after the headers, fails
silently, and takes CSRF and every flash message down with it. The contact
form appeared to work and rejected every submission.

**Embedding PHP values in `<script>` uses `ejs()`, never `e(json_encode())`.**
Browsers do not decode HTML entities inside a script element, so `&quot;`
reaches the parser as a literal and throws SyntaxError. That one broke
`window.SBS.csrf` and with it every admin AJAX action. Inside an HTML
*attribute*, `e(json_encode(...))` is still the correct choice.

**Inline admin scripts must wait for `DOMContentLoaded`.** `admin.js` is
loaded with `defer`, so `window.SBSAdmin` does not exist while an inline
`<script>` at the end of the body is being parsed.

**Colours live only in `assets/css/base.css`.** Both the public site and the
admin read the same custom properties, and dark mode is a single
`[data-theme]` swap of those tokens. Do not start a per-page override list —
that is the failure mode that made tobwai's dark mode unmaintainable.
`.brand` and the theme-toggle glyph rules belong in `base.css` too, because
the admin does not load `site.css`.

**Photos are stored in three sizes.** `uploads/albums/{id}/orig/` is the
untouched camera file and is what the customer downloads — never regenerate or
recompress it. `preview/` is 2048px, `thumb/` is 600px. Imagick if available,
GD otherwise. **Never shell out** — `exec()` and friends are disabled on
Cloudways, so ffmpeg/ImageMagick CLI will work locally and fail in production.

**Uploads go one file per request.** nginx cuts a request body at roughly
128 MiB, so a single POST carrying hundreds of camera JPEGs is refused
outright. `assets/js/uploader.js` sends three concurrently with two retries.

**Downloads stream in chunks.** `fpassthru()` is disabled in the Cloudways FPM
pool, and a 40 MB original must not be read into memory. Use the `fread` loop
in `dl.php`.

**Thai text normalising must keep `\p{M}`.** Vowels and tone marks are Unicode
Marks; stripping them turns คุยกับครู into คยกบคร and every match fails
silently. `slugify()` already does this correctly.

---

## Email

Six templates in `emails/`, all wrapped by `emails/layout.php`.
**No emoji in any email template** — the website may use them, letters may not.

`MAIL_LOG_ONLY` in `config.php` is `true` by default: every letter is rendered
and written to `email_log` but nothing is sent. Read them at
**admin-emails.php**, which also previews each template with sample data.

**Ask the owner before switching `MAIL_LOG_ONLY` to false.** After that the
admin can send real mail to real customers.

---

## Deploy

Not live yet. Two prerequisites, both the owner's call:

1. Register `saybaewstudio.com` (checked available on 2026-08-18)
2. Create a Cloudways application for it and put its id in `APP_ID` at the top
   of `deploy.sh` — the script refuses to run while that is blank, on purpose

Existing app ids on the same server, none of which is this site:
`xaxvhfthsr` = tobwai, `hrzbghjjrd` = sorndekcoding, `dxmjbjbqrf` = laaklaai,
`xpbtsfxngq` = maeranie2022. An rsync to the wrong one takes another site down.

```bash
cd ~/Sites/saybaewstudio && ./deploy.sh --dry-run
```

Never pass `--delete` to rsync. Never overwrite `config.php` or `uploads/` on
the server. Both are already excluded.

---

## Local

```bash
cd ~/Sites/saybaewstudio && php -S localhost:8210
```

Admin: `admin@saybaewstudio.com` / `saybaew@2569` — change it on first deploy.
`php tools/demo-photos.php` fills empty albums with generated placeholders.

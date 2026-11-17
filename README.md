# Aswin M — Portfolio (WordPress theme)

A custom WordPress theme for **Aswin M**, a copywriter, content writer and creative strategist. Pages are built with **Elementor** using custom widgets. Works, brands and contact details are managed from WP Admin.

- **Live (staging):** https://snow-worm-728744.hostingersite.com/
- **Stack:** WordPress 7.1 · Elementor 4.4 · PHP 8.2 · Hostinger shared hosting (hCDN in front)
- **Built by:** Veinbit Software Solutions LLP

---

## Repository layout

```
aswin-portfolio/            The theme (deploy this folder to wp-content/themes/)
├── style.css               Theme header + all front-end styles
├── functions.php           Bootstrap: loads inc/*, registers Elementor widgets, enqueues assets (ASWIN_VER)
├── header.php / footer.php Site header (WP menu "Header") and footer (contact, section links, credit)
├── front-page.php, page.php Render Elementor content
├── single-aswin_work.php   Individual work page
├── single-aswin_brand.php  Individual brand page
├── archive-aswin_work.php  /work/ — all works with filter tabs
├── archive-aswin_brand.php /brands/ fallback (only used if no Brands page is set)
├── taxonomy-aswin_work_cat.php  Category fallback (redirects to the section page when one is linked)
├── assets/                 main.js (menu, reveal animations, filter tabs), admin.js/.css (media pickers)
└── inc/
    ├── content-types.php        Works / Brands post types, Work Categories, admin fields
    ├── render.php               Shared markup: cards, phone mockups, the 5 section designs, URL helpers
    ├── contact-details.php      WP Admin → Contact Details, [aswin_contact] shortcode, Person schema
    ├── cache.php                No-cache headers for HTML + automatic Hostinger CDN purge
    ├── demo.php                 "Demo" labels + one-click "Remove demo content"
    ├── elementor-widgets.php        Hero, Ticker, Hello Heading, Quote Card, Statement, Section Heading,
    │                                Skill Card, Portfolio Gallery, Contact CTA, Contact Details
    ├── elementor-widgets-admin.php  Works (from admin), Brands (from admin), Section Links
    └── elementor-widgets-media.php  Photo Collage (About section)

setup/                      One-off WP-CLI scripts used to build and migrate the site (see below)
└── demo/                   Demo content: demo.json, image generator, importer
```

---

## Editing content (for the site owner)

Everything below is done in **WP Admin**. You don't need to touch any code.

| What | Where |
|---|---|
| Add or edit a work | **Works → Add Work**: title, cover image, short description, full write-up, category, client, year, role. Optional: video, extra images, PDF, link, Instagram post details |
| Sections (categories) | **Works → Categories**: name, description, **Display style**, **Order**, **Section page** |
| Brands | **Brands → Add Brand**: name, logo (transparent PNG works best), short description, description, website |
| Email, phone, WhatsApp, socials, CV | **Contact Details** (left menu) |
| Homepage and section pages | **Pages → (page) → Edit with Elementor** |
| Header menu | **Appearance → Menus → "Header"**. Give a menu item the CSS class `btn-pill` to make it the black button |

### Section designs
Each work category has a **Display style**, used on both the homepage and its section page:

| Style | Look | Default category |
|---|---|---|
| `list` | Numbered article rows (thumbnail on hover) | Technical Copywriting / Content Writing |
| `phone` | Instagram-style phone mockups | Creative Copywriting — Social Media |
| `feature` | Feature mosaic: one large tile, captions over images | Campaigns / PR Events |
| `newsprint` | Newspaper clippings with masthead | Newspaper Ad |
| `case` | Alternating case-study rows | Branding |
| `card` | Plain image cards | (fallback) |

On the work's own page, works in a `phone` category show the phone mockup; all others show the cover image.

### Pages and URLs
| URL | What |
|---|---|
| `/` | Homepage (Elementor): hero, about + photo collage, skills, Brands, one section per category (3 items + "View more"), contact |
| `/brands/` | Brands page (Elementor) |
| `/technical-writing/`, `/social-media/`, `/campaigns-pr/`, `/newspaper-ad/`, `/branding/` | Section pages (Elementor). Each is linked from its category's **Section page** setting |
| `/work/` | All works with filter tabs |
| `/work/<slug>/` | Individual work |
| `/brands/<slug>/` | Individual brand, listing its works (works link to a brand via **Project details → Client**) |
| `/work-category/<slug>/` | Redirects (301) to the linked section page |

### Elementor widgets (category "Aswin Portfolio")
Hero Quote · Scrolling Ticker · Big Hello Heading · Photo Collage · Quote Card · Bold Statement · Section Heading · Skill Card · **Works (from admin)** · **Brands (from admin)** · Section Links · Portfolio Gallery (manual uploads) · Contact Call-to-action · Contact Details.

In headings and quotes, wrap words in `*asterisks*` to get the italic serif accent.

### Demo content
The site ships with demo content: 6 fictional brands, 15 works, and placeholder contact details (`aswin@example.com`, `+91 90000 00000`). Every demo item has a **Demo** label in admin lists. Once your real work is in, click **Remove demo content** in the notice on the Works, Brands, Contact Details or Dashboard screens. This deletes only items tagged as demo.

---

## Development

### Local editing and deploy
There is no build step. Edit files in `aswin-portfolio/` and upload them to the server.

```bash
# SSH details: hPanel → Advanced → SSH Access (use key-based auth)
SITE=~/domains/snow-worm-728744.hostingersite.com/public_html

# Upload changed files (example)
scp -P <port> aswin-portfolio/style.css aswin-portfolio/functions.php <user>@<host>:$SITE/wp-content/themes/aswin-portfolio/

# Or the whole theme
tar --force-local -czf theme.tgz aswin-portfolio && scp -P <port> theme.tgz <user>@<host>:
ssh -p <port> <user>@<host> "cd $SITE/wp-content/themes && rm -rf aswin-portfolio && tar -xzf ~/theme.tgz && rm ~/theme.tgz"
```

After deploying:
1. **Bump `ASWIN_VER`** in `functions.php` whenever CSS/JS changes (cache busting).
2. **Purge caches:**
   ```bash
   wp elementor flush-css
   wp eval --user=admin "aswin_purge_cdn_now();"
   wp cache flush
   ```
3. If post types, taxonomies or slugs changed: `wp rewrite flush` (not `--hard`; `proc_open` is disabled on this host).

### Setup scripts (`setup/`)
These were run once, in this order, to build and migrate the live site. **Don't re-run them on the live site.** Most of them overwrite Elementor layouts that may have been edited since.

| Script | Did |
|---|---|
| `build-home.php` | Built the first Elementor homepage |
| `migrate-works.php` | Moved gallery items into the Works/Brands post types |
| `add-about-photos.php` | Added the Photo Collage to the About section |
| `split-sections.php` | One homepage section per category (3 items + View more) |
| `create-section-pages.php` | Created the 6 section pages and set category designs (safe to re-run: it skips existing pages) |
| `demo/import-demo.php` | Imported demo content (refuses to run twice) |

Run them with `wp eval-file <script> --user=admin`. **`--user=admin` is required**: without a logged-in user, Elementor's `Document::save()` silently saves nothing.

### Regenerating demo images
`setup/demo/make-images.ps1` builds an HTML design for each entry in `demo.json` and screenshots it to PNG with headless Microsoft Edge (`setup/demo/img/`).

```powershell
powershell -ExecutionPolicy Bypass -File setup\demo\make-images.ps1
$env:LOGOS_ONLY = '1'   # only re-render the brand logos
```
Keep the `.ps1` file **ASCII-only**. Windows PowerShell 5.1 reads it as ANSI, so use HTML entities for emoji and curly quotes.

---

## Gotchas (learned the hard way)

- **Hostinger CDN caches HTML.** It once served a week-old homepage. `inc/cache.php` now sends `no-cache` headers for pages and purges the CDN on every save. When checking a deploy, fetch the URL **without** a `?v=` query string, or you'll bypass the CDN and miss a stale copy.
- **The theme can be switched behind your back.** Opening Hostinger's AI website builder / onboarding once activated "Hostinger AI Theme". If the site suddenly looks wrong, check **Appearance → Themes**.
- **Don't name CSS variables `--display`.** Elementor containers use `--display` for `display: flex`, which silently broke our fonts. The theme uses `--font-display` / `--font-serif`.
- **Elementor resets `a { box-shadow: none }`** inside its content. Use `border` for outlined links and buttons.
- **About section on mobile:** the left column (`.about-left`) must be `display: block`. Elementor's flex column collapsed the absolutely-positioned photo collage to 24px tall.
- **Headless Edge has a minimum window width of about 500px.** For true phone-width screenshots, load the page in a 390px-wide `<iframe>`.

---

## Server backups
Taken before each major restructure, in the hosting account's home directory:
`aswin-portfolio-backup-v1` … `-v4` (theme folders) and `aswin-home-elementor-backup-v2/v3.json` (homepage Elementor data).

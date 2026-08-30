# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

**Read [AGENTS.md](AGENTS.md) first.** It is the authoritative, actively-maintained reference for this repo (architecture tables, section-injection order, REST contracts, "where to change X", CSS conventions, and the full agent rules/checklist). This file is a short orientation layer on top of it — don't duplicate AGENTS.md changes here, update AGENTS.md instead.

## What this is

`nexorpro` is the native WordPress site for nexorpro.ru (Nexor — apartment/house renovation company in Moscow). It was migrated from a React SPA to WordPress: page content is stored as HTML (imported from the old React build), while the header and several homepage sections are rendered from PHP `template-parts/` and injected dynamically by a plugin.

Two components:
- **Theme** `package/wp-content/themes/nexor/` — templates, `template-parts/`, migrated HTML rendering, SEO `<head>`, frontend JS/CSS, GSAP.
- **Plugin** `package/wp-content/plugins/nexor-core/` — leads, calculator REST API, projects CPT, admin settings, section injection (`Nexor_Enhancements`).

Release version lives in `VERSION`, `package.json`, `style.css`, `NEXOR_THEME_VERSION` — kept in sync at release. `Nexor_Enhancements::VERSION` is a separate *options schema* version.

## Common commands

```bash
docker compose up -d              # local WordPress at http://localhost:8080 (admin/admin)
                                   # theme + plugin are mounted volumes — edits apply live, no rebuild

npm run test:unit                 # PHP unit test for enhancements (no WordPress needed)
php tests/enhancements-unit.php   # same, direct

npm run check:js                  # node --check syntax pass over all .js/.mjs in package/wp-content, tests, tools

docker compose exec wordpress wp --allow-root <command>   # WP-CLI inside the container
docker compose exec wordpress wp nexor enhancements-diagnostic --allow-root   # JSON diagnostic, no secrets
```

Custom WP-CLI commands (defined by the plugin): `wp nexor seed`, `wp nexor migrate-media`, `wp nexor migrate-projects`, `wp nexor enhancements-diagnostic`.

There is no single-test flag for `enhancements-unit.php` — it's one PHP script that runs all its assertions; run it directly with `php tests/enhancements-unit.php`.

Production/local Chromium audits (`tests/*-audit.mjs`, `*-evidence.mjs`) drive headless Chrome over the DevTools protocol, mostly against production (nexorpro.ru); run individually with `node tests/<name>.mjs`. Artifacts land in `artifacts/`.

## Architecture essentials

- Pages render through `nexor_render_migrated_content()` in `functions.php`: strip the embedded `<header>` and `<footer>` → apply `nexor_content_replacements` (phone/email/socials from settings) → apply `nexor_migrated_content` filter (this is where `Nexor_Enhancements` injects sections) → resolve `{{THEME_URI}}` placeholders.
- Homepage sections are injected in a fixed order that a unit test enforces: services → projects → calculator → budget → prices → timeline → Nexor system → stages → before/after → additional services → promotions → about company → FAQ → measurement CTA. Anchors like `#calculator`, `#cases`, `#main-services`, `#about-company-nexor`, `#faq`, `#stages` must not be renamed — PHP locates content by these IDs.
- Some homepage sections are real template-parts (`template-parts/home-*-section.php` + `nexor_render_home_*_section()` in `functions.php`, called from `inject_frontend_content()`); others (prices, stages) are HTML strings assembled directly inside `class-nexor-enhancements.php` from WP options, with no template-part.
- Service page slugs are fixed and must not change: `remont-kvartir-pod-klyuch`, `capital-remont`, `design-remont`, `remont-v-novostroyke`, `cosmetic-remont`, `remont-domov-pod-klyuch`.
- REST API: `POST /wp-json/nexor/v1/lead` (creates `nexor_lead` CPT + Telegram notification), `POST /wp-json/nexor/v1/calculate` (server-side calculator validation). Protected by WP nonce, Origin check, honeypot, invisible Yandex SmartCaptcha (server-side token check), rate limit (3 req/15min/IP). Frontend JS reads `NexorSettings` (localized via `wp_localize_script`) for `restUrl`/`nonce`.
- Frontend is vanilla JS in `nexor.js` (~1400 lines, no bundler); GSAP + plugins loaded separately from `assets/vendor/gsap/`.

## Markup quality bar

Treat every HTML/CSS change as senior front-end work — see "Требования к вёрстке", "Семантичность вёрстки", and "CSS: responsive — без фиксированных размеров" in [AGENTS.md](AGENTS.md) for the full rules. In short:

1. **Semantic HTML** — tags reflect content meaning (proper heading hierarchy, `button` vs `a` by purpose, `ul`/`ol`/`li` for lists, `section`/`aside`/`nav` instead of bare `div`s).
2. **W3C-valid markup** — no unclosed tags, duplicate `id`s, invalid nesting, or disallowed attributes; check changed templates against the [W3C Markup Validator](https://validator.w3.org/) when a change materially alters markup.
3. **Responsive by default** — every new/changed block must adapt cleanly at ~390px, ~768px, and ~1440px, with no horizontal scroll or overlap.
4. **Avoid fixed `width`/`height`** on content blocks and cards — let content, flex/grid, `clamp()`, `%`, or `svh`/`dvh` size them; fixed px sizes are only for small, non-adapting UI assets (icons, decorative lines, focus rings).

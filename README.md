# silverstripe-tailwind

A Silverstripe CMS 6 site built on the **Silverwind** theme (Tailwind CSS 4,
daisyUI 5, Alpine.js) and the antlion modules:

| Package | Where | What |
|---|---|---|
| [silverwind](https://github.com/marcd1983/silverwind) | `themes/silverwind` (git submodule) | Theme: templates, Tailwind build |
| [antlion/silverwind-blocks](https://github.com/marcd1983/silverwind-blocks) | composer | Elemental blocks (hero, cards, sections, carousels, …) |
| [antlion/silverwind-settings](https://github.com/marcd1983/silverwind-settings) | composer | Colours, fonts and layout from Settings > Theme |
| [antlion/store-location](https://github.com/marcd1983/store-location) | composer | Locations, hours, departments, staff |

Project code in `app/` adds the Services, Ribbon CTA, Testimonials and
Partners blocks/models, SEO structured data, and a demo-site seed task.

## Setup

Requirements: Docker, Node 20+ (for the theme build).

```sh
git clone --recurse-submodules https://github.com/marcd1983/silverstripe-tailwind.git
cd silverstripe-tailwind
cp .env.example .env          # then set the passwords
docker compose up -d --build
docker exec silverstripe_web composer install
docker exec silverstripe_web vendor/bin/sake db:build --flush
docker exec silverstripe_web vendor/bin/sake tasks:seed-demo-site --reset   # optional demo content
```

| Service | URL |
|---|---|
| Site | http://localhost:8080 (CMS at `/admin`) |
| Mailpit (dev email) | http://localhost:8025 |
| phpMyAdmin | http://localhost:8081 |

### Theme

```sh
cd themes/silverwind
npm install
npm run dev      # watch; or: npm run build
```

`dist/` is committed in the theme repo. Rebuild after adding templates or
PHP class maps anywhere Tailwind scans (see the theme's README).

### Working on the antlion modules locally

`composer.json` installs the modules from GitHub. To edit them in place, keep
checkouts in one folder and set `ANTLION_MODULES_DIR` in `.env`:

```sh
ANTLION_MODULES_DIR="/home/you/projects/antlion-modules"
```

After every `composer install`/`update`, `bin/link-local-modules.sh` swaps
`vendor/antlion/*` for symlinks to those checkouts (docker-compose mounts the
folder at the same path in the container). Without the variable or folder it
does nothing.

## Demo content

`vendor/bin/sake tasks:seed-demo-site` builds a demo site for "Fernbrook
Landscapes": block-built pages, services, ribbon CTAs, testimonials,
partners, two store locations with hours and staff, footer menus and brand
colours. It only adds what's missing; `--reset` archives the demo pages
(restorable from the CMS archive) and rebuilds everything.

## Checks

```sh
docker exec silverstripe_web vendor/bin/phpunit          # PHP tests (app/tests)
docker exec silverstripe_web vendor/bin/phpcs            # coding standard (phpcs.xml.dist)
cd tests/e2e && npm install && npx playwright test       # screenshots + accessibility
```

CI (`.github/workflows/ci.yml`) runs lint, coding standard and PHP tests on
every push.

## Configuration notes

- **Timezone:** `America/Chicago` (`app/_config.php`), override with
  `SS_TIMEZONE`. Store hours and ribbon schedules depend on it.
- **Email:** set `MAILER_DSN` per environment (dev: Mailpit).
- **Scripts in Settings > Scripts** (analytics, tags) are tied to cookie
  consent groups and only load once the visitor accepts that group.
- **Security headers** are added to front-end responses
  (`App\Middleware\SecurityHeadersMiddleware`); the Content-Security-Policy
  starts in report-only mode.

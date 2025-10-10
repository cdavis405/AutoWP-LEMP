# Retaguide.com Infrastructure

Deployable WordPress stack and custom block theme for **Retaguide**, a Retatrutide research and guide hub. The repository bundles the FSE theme, must-use plugins, provisioning scripts, and CI/CD automation tuned for Azure Ubuntu 22.04 VMs using NGINX + PHP-FPM.

## Highlights
- **Custom block theme** (`wp-content/themes/retaguide`) with medical-inspired palette, sticky header, breadcrumbs, SEO meta, and responsive layouts.
- **Content model**: standard News posts plus a Guides CPT featuring Guide Level & Guide Topic taxonomies, “last reviewed” metadata, and schema-aware renderers.
- **Legal controls**: global disclaimer option, per-post overrides, editor shortcode, and automatic injection with block patterns for consistent compliance.
- **Pinned navigation**: theme settings UI (mu-plugin) enabling sortable pinned posts/pages to appear on the right edge of the primary nav.
- **Author tooling**: reusable disclaimer block, News/Guide/Callout block patterns, related-content queries, and editor-side helper scripts.
- **Security defaults**: XML-RPC disabled, login throttling, admin file edit lockout, hardened headers.
- **DevOps assets**: Ubuntu provisioning script, sample NGINX vhost, rsync deploy script, GitHub Actions workflow, Composer + PHPCS linting, and `.env` template.

## Repository Structure
```
wp-content/
 ├─ themes/retaguide/       # Block theme (templates, patterns, assets, inc/* helpers)
 └─ mu-plugins/             # Settings + security bootstraps
scripts/                    # Provision and deployment helpers
config/nginx/               # Reference NGINX configuration
.github/workflows/          # CI/CD pipeline definition
composer.json / phpcs.xml   # PHP tooling
.env.example                # Deployment environment template
```

## Provisioning a Fresh Azure VM (Ubuntu 22.04)
1. Create an Azure VM (2 vCPU / 4 GB RAM recommended) and open ports 22, 80, 443.
2. SSH to the VM and clone this repository: `git clone https://github.com/cdavis405/Retasite.git && cd Retasite`.
3. Run the LEMP provisioning script as root, passing your domain and notification email:
	```bash
	sudo ./scripts/provision.sh retaguide.com admin@retaguide.com
	```
	The script installs NGINX, PHP 8.2, MariaDB, Certbot, UFW rules, WordPress core, and writes hardening defaults. Database credentials are stored in `/root/retaguide-db.txt`.
4. Complete the WordPress installer at `https://retaguide.com/wp-admin`.
5. Copy the custom theme & mu-plugins into the WordPress install (post-clone you can symlink or `rsync` as per **Deployment** below).

### Hardening Notes
- The script sets `DISALLOW_FILE_MODS` in `wp-config.php`. Manage updates via SSH/CI.
- XML-RPC is disabled at both NGINX and WordPress levels; adjust if needed.
- Fail2ban is installed with defaults; extend jail settings for `wp-login` as desired.

## Deployment Workflow
### Prerequisites
- SSH key-based access from CI runner to the VM.
- `wp` CLI available on the VM (`sudo apt install wp-cli` or PHP phar).
- CI secrets: `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`.

### GitHub Actions
The workflow `.github/workflows/deploy.yml` runs on every push to `main` (and manually):
1. Installs PHP 8.2 and Composer dependencies.
2. Runs PHPCS against theme + mu-plugins.
3. Adds the deployment SSH key.
4. Generates an `.env` on the runner with host info and calls `scripts/deploy.sh`.
5. Reloads NGINX to purge FastCGI cache.

### Manual Deploy
1. Duplicate `.env.example` to `.env` and fill in remote values.
2. Ensure SSH access works (`ssh DEPLOY_USER@DEPLOY_HOST`).
3. Run:
	```bash
	bash scripts/deploy.sh
	```
	The script rsyncs the theme and mu-plugins, then flushes remote caches & permalinks via WP-CLI.

## WordPress Setup Checklist
1. **Activate the Retaguide theme** via Appearance → Themes.
2. Visit **Retaguide Settings** (Appearance → Retaguide Settings):
	- Navigation tab: add pinned items by ID and drag to sort. These render right-aligned in the nav.
	- Legal tab: edit the global disclaimer (rich text). Per-post overrides live in the editor sidebar meta box.
3. Create the following pages: Home, News, Guides, About, Contact, Privacy Policy, Cookie Notice.
4. Assign Home as the static front page and News page as the Posts page (Settings → Reading).
5. Add initial navigation menu (Appearance → Editor → Navigation block). Pinned items are appended automatically.
6. Verify taxonomies under Guides → Guide Levels / Guide Topics; defaults (Beginner, Protocol, Safety / Mechanism, Dosing, Monitoring) are pre-seeded.
7. Confirm permalinks under Settings → Permalinks: `/%postname%/`. Posts auto-render as `/news/{slug}` and guides as `/guides/{slug}`.

## Authoring Experience
- Use block patterns (insert via “Patterns → News/Guides/Callouts”) for consistent structure.
- News posts support categories (Research, Safety, Regulatory, Market, Reviews) and free-form tags. Archives render at `/news/category/{slug}` and `/news/tag/{slug}`.
- Guides have sidebar fields for **Last reviewed** (date) and **Version**; shortcode `[retaguide_last_reviewed]` renders a stylised badge.
- Disclaimer behaviour:
  - Global text configurable in theme settings.
  - Automatically prepends to posts & guides unless you set the “Hide global disclaimer” toggle or provide an override.
  - `[disclaimer]` shortcode inserts the effective disclaimer inline (used by patterns).

## Performance & SEO
- Lazy-loading, responsive images, and small CSS/JS footprint.
- Open Graph, Twitter card, and canonical tags inject on single entries.
- JSON-LD output for `Article`, `HowTo`, and breadcrumbs.
- `robots.txt` references the core XML sitemap.
- Suggested server cache: enable NGINX FastCGI cache (add `fastcgi_cache` directives) or layer a plugin such as Cache Enabler—remember to clear caches post-deploy (CLI handles `wp cache flush`).

## Rollback Strategy
1. Keep nightly NGINX + DB backups (e.g., Azure Backup or `mysqldump`).
2. To revert theme changes, redeploy a previous git tag via `scripts/deploy.sh` or restore from backup dir in `/var/www/retaguide/wp-content/themes/retaguide`. Reload NGINX afterward.
3. Database rollback: `mysqldump -u root -p retaguide > backup.sql` (pre-change); restore with `mysql retaguide < backup.sql`.

## Next Steps & Enhancements
- Hook Azure Monitor for uptime & log shipping.
- Expand health checks in CI (e.g., WordPress unit tests, pa11y, Lighthouse).
- Consider object cache (Redis) once traffic grows.
- Integrate privacy tooling (Cookie notice / CMP) pending legal review.

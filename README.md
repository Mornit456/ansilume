# Ansilume

<p align="center">
  <img src="ansilume.svg" alt="Ansilume" width="420">
</p>

Web-based Ansible automation platform. Manage inventories, credentials, projects, templates, and run Ansible playbooks through a UI with proper access control, execution history, logging, and auditability.

**Stack:** PHP 8.2 · Yii2 · MariaDB · Redis · Docker

---

## Quick Start

```bash
cp .env.example .env
# Edit .env — set COOKIE_VALIDATION_KEY and APP_SECRET_KEY at minimum

# Linux only, if your UID is not 1000: export host UID/GID before building
# export UID GID

docker compose up -d --build

# Run migrations
docker compose exec app php yii migrate --interactive=0

# Create the first admin user
docker compose exec app php yii setup/admin admin admin@example.com yourpassword
```

Open http://localhost:8080

---

## Development

```bash
docker compose up -d           # Start all services
docker compose logs -f app     # PHP-FPM logs
docker compose logs -f worker  # Queue worker logs

# Migrations
docker compose exec app php yii migrate

# Console
docker compose exec app php yii help
```

### Linux: user ID alignment

The PHP containers adjust `www-data` to match your host UID/GID so that
files written into the volume-mounted source tree have the right ownership.
If your host UID is not `1000` (check with `id -u`), export your IDs before
building:

```bash
export UID GID
docker compose up -d --build
```

You can add `export UID GID` to your shell profile (`.bashrc`, `.zshrc`) so
it applies automatically. If you are running as root (UID 0), this step is
not needed.

### Rebuild containers

After changes to `docker/` (Dockerfile, PHP config, installed packages):

```bash
export UID GID   # Linux only
docker compose down
docker compose build --no-cache
docker compose up -d
docker compose exec app php yii migrate --interactive=0
```

`--no-cache` ensures layers are not reused from a previous build.
Omit it when only config files changed and you want a faster rebuild.

---

## Architecture

- `controllers/`  — thin HTTP controllers
- `models/`       — ActiveRecord models + form models
- `services/`     — business logic (JobLaunchService, AuditService, CredentialService)
- `jobs/`         — async queue job handlers (RunAnsibleJob)
- `commands/`     — console commands
- `migrations/`   — forward-only schema migrations
- `views/`        — Yii view templates
- `config/`       — web, console, test configuration

### Job Status Flow

```
pending → queued → running → succeeded
                           → failed
                           → canceled
```

---

## Security Notes

- Credential secrets are AES-256-CBC encrypted (requires `APP_SECRET_KEY` in `.env`)
- Raw secrets are never logged or rendered in HTML
- RBAC roles: `viewer` (read-only) · `operator` (launch + manage) · `admin` (full)
- Superadmins set via `is_superadmin` flag
- Ansible execution is fully async — web requests never spawn processes

# Deployment

This project ships with a GitHub Actions CI/CD pipeline made of two workflows:

| Workflow | File | Trigger | Purpose |
| --- | --- | --- | --- |
| **CI** | `.github/workflows/ci.yml` | Every push and pull request | Install dependencies, run migrations against a MySQL service, run PHPUnit, and compile front-end assets. |
| **Deploy** | `.github/workflows/deploy.yml` | Automatically after **CI** succeeds on `master` (or manually via *Run workflow*) | Build a production bundle and release it to the server over SSH with zero-downtime symlink switching. |

Deploy runs only when CI passes on `master`, so broken code is never released.

## How the deploy works

1. Composer installs production-only dependencies (`--no-dev --optimize-autoloader`).
2. Node compiles the production assets (`npm run production`).
3. The build is packed into `deploy.tar.gz` and copied to `DEPLOY_PATH/incoming` on the server.
4. On the server the archive is unpacked into a timestamped directory under `releases/`.
5. The shared `.env` file and `storage/` directory are symlinked into the release so persistent state survives deploys.
6. `php artisan migrate --force`, `config:cache`, and `route:cache` run.
7. The `current` symlink is atomically flipped to the new release and PHP-FPM is reloaded.
8. Old releases are pruned, keeping the 5 most recent.

## Required GitHub secrets

Add these under **Settings → Secrets and variables → Actions** (in a `production` environment, matching `environment: production` in the workflow):

| Secret | Description | Example |
| --- | --- | --- |
| `DEPLOY_HOST` | Server hostname or IP | `203.0.113.10` |
| `DEPLOY_USER` | SSH user | `deployer` |
| `DEPLOY_SSH_KEY` | Private SSH key (PEM) authorized on the server | contents of `~/.ssh/id_ed25519` |
| `DEPLOY_PORT` | SSH port | `22` |
| `DEPLOY_PATH` | Absolute base path of the app on the server | `/var/www/booking` |

## One-time server layout

The workflow expects this directory structure at `DEPLOY_PATH`:

```
/var/www/booking/
├── current -> releases/<timestamp>   # symlink managed by the deploy
├── incoming/                         # upload staging area
├── releases/                         # timestamped releases
└── shared/
    ├── .env                          # real production environment file
    └── storage/                      # persistent storage (logs, cache, uploads)
```

Create it once before the first deploy:

```bash
sudo mkdir -p /var/www/booking/{incoming,releases,shared/storage}
# Copy .env.example, fill in production values, and place it at shared/.env
sudo cp /path/to/production.env /var/www/booking/shared/.env
# Seed Laravel's storage skeleton into shared/storage (framework/cache, framework/sessions, framework/views, logs)
sudo chown -R deployer:www-data /var/www/booking
```

Point your web server's document root at `/var/www/booking/current/public`.

> The web server user needs write access to `shared/storage`, and `DEPLOY_USER`
> needs passwordless `sudo systemctl reload php7.2-fpm` for the reload step
> (otherwise it is skipped harmlessly).

## Running the pipeline

- **Automatic:** merge/push to `master` → CI runs → on success, Deploy runs.
- **Manual:** GitHub → **Actions → Deploy → Run workflow**.

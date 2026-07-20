# Agent Instructions

## Tool Usage

- `apply_patch` is a FREEFORM tool.
- Never call `apply_patch` with JSON or a `cmd` key.
- Correct format:

```diff
*** Begin Patch
*** Update File: path/to/file
@@
-old
+new
*** End Patch
```

- Wrong format: `{"cmd":"*** Begin Patch\n..."}`

## Project Defaults

- Keep changes minimal and focused.
- Match existing Laravel Blade and Metronic style.
- Run the smallest relevant check after non-trivial edits.
- UI changes must preserve Metronic components and existing design tokens.

## Execution Policy

- Work in the main agent (single-threaded, inline) by default.
- Do not spawn subagents for code search, investigation, implementation, review, or debugging unless the user explicitly asks or a higher-priority instruction requires delegation.
- If delegation is required by a system instruction, explain why before spawning.

## Safe Local → Repo → Server Workflow

- Before any commit/push/deploy work, inspect local state:
  - `git status --short --branch`
  - `git rev-list --left-right --count origin/main...main`
  - `git stash list`
- Local worktree must be clean or every dirty file must be classified before continuing:
  - intended change → test and commit atomically
  - useful but unrelated → stash with a clear name
  - unwanted/scratch → revert or delete only after explicit confirmation
- For non-trivial changes, work from updated `main` on a task branch:
  - `git checkout main`
  - `git pull --ff-only`
  - `git checkout -b fix/descriptive-task-name`
- Keep commits atomic. Do not mix UI, tests, server config, and unrelated local changes in one commit.
- Before push, run the smallest sufficient checks for the change; production/deploy gates require full relevant checks:
  - `git diff --check`
  - targeted or full `php artisan test`
  - `npm run build` when frontend assets/CSS/JS changed
- Push only after tests/checks pass and intended changes are committed. Verify sync with `git rev-list --left-right --count origin/main...main`; target is `0 0`.
- Server deploy must use `origin/main`, never local dirty state:
  - backup `.env`
  - `git fetch origin main`
  - `git status --short --branch`
  - `git reset --hard origin/main`
- Do not delete untracked server files unless audited and explicitly approved.
- Standard server deploy sequence:
  - `composer install --no-dev --optimize-autoloader --no-interaction`
  - `npm ci --no-audit --no-fund`
  - `npm run build`
  - `php artisan migrate --force`
  - `php artisan optimize:clear`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
  - `php artisan storage:link || true`
- Post-deploy checks must include:
  - server commit matches `origin/main`
  - no pending migrations
  - no failed queue jobs
  - queue worker is running
  - scheduler cron/systemd timer is configured
  - `/up` and `/login` return HTTP 200
- Server git remotes must not contain raw access tokens. If a token is found in a remote URL, report it, rotate the token, and replace the remote with a safe URL.
- Before risky deploys, create rollback anchors:
  - backup git branch, for example `backup/pre-deploy-YYYYMMDDHHMMSS`
  - `.env` backup outside the project worktree

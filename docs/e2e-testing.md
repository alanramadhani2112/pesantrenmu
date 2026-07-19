# E2E Testing

This project uses two E2E layers:

- Laravel Feature tests for backend workflows, authorization, uploads, state transitions, security headers, and query budgets.
- Playwright tests for browser flows across Admin, Asesor, and Pesantren roles.

## Local setup

Install PHP and Node dependencies:

```bash
composer install
npm install
npx playwright install chromium
```

Prepare the application:

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=TestDataSeeder
```

`TestDataSeeder` aliases the comprehensive `BusinessFlowTestSeeder`, so it creates roles, permissions, master EDPM data, document categories, users, pesantren data, asesor assignments, akreditasi scenarios, assessment rows, scoring rows, and banding cases.

Default seeded login credentials:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `bf.admin@test.local` | `password` |
| Super Admin | `bf.superadmin@test.local` | `password` |
| Asesor | `bf.asesor1@test.local` | `password` |
| Pesantren | `bf.pesantren@test.local` | `password` |

## Laravel Feature E2E tests

Run all Feature E2E tests:

```bash
composer test:e2e
```

Run one E2E class:

```bash
php artisan test --filter=AdminWorkflowTest
```

Covered files live under `tests/Feature/E2E/` and include auth, admin workflow, asesor workflow, permissions, documents, state machine edges, banding, and security/performance checks.

## Playwright browser tests

Start the local server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Run browser E2E tests:

```bash
npm run test:e2e
```

CI-style run:

```bash
npm run test:e2e:ci
```

Interactive UI mode:

```bash
npm run test:e2e:ui
```

View the last HTML report:

```bash
npm run test:e2e:report
```

Playwright auth setup is in `tests/e2e/auth.setup.ts`. Reusable role fixtures are in `tests/e2e/fixtures.ts`.

To skip automatic seeding during auth setup:

```bash
PLAYWRIGHT_SEED=0 npm run test:e2e
```

## CI pipeline

GitHub Actions has two relevant workflows:

- `.github/workflows/ci.yml`: Pint, PHP tests on PHP 8.2/8.3 with SQLite, `composer prod:check`, and frontend build.
- `.github/workflows/playwright.yml`: installs PHP and Node dependencies, builds assets, migrates SQLite, starts Laravel on `127.0.0.1:8000`, then runs `npm run test:e2e:ci`.

Playwright screenshots, videos, and HTML report artifacts are generated on failure via the Playwright config and uploaded by the workflow.

## Debugging tips

- Use `--debug` for step-by-step browser debugging:

  ```bash
  npx playwright test tests/e2e/admin.spec.ts --debug
  ```

- Use headed mode for visual inspection:

  ```bash
  npx playwright test --headed
  ```

- Re-run only setup/auth:

  ```bash
  npx playwright test tests/e2e/auth.setup.ts --project=setup
  ```

- If browser tests fail after changing seeded data, clear auth state and re-run:

  ```bash
  rm -rf tests/e2e/.auth
  npm run test:e2e
  ```

On Windows PowerShell:

```powershell
Remove-Item -Recurse -Force tests/e2e/.auth
npm run test:e2e
```

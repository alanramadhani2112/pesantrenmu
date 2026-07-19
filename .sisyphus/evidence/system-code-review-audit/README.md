# System Code Review Audit Evidence

Audit date: 2026-07-20

## Commands / Results

| Check | Result |
|---|---|
| `git ls-files` inventory | 860 tracked files |
| `php artisan route:list --json` | 150 routes |
| `php artisan test --list-tests` | 3180 listed tests |
| `npm run build` | PASS |
| `php artisan view:cache` | PASS |
| `php artisan test tests/Feature/E2E` | PASS |
| `php artisan test tests/Feature/AsesorAkreditasiMenuContextTest.php` | PASS, 6 tests / 25 assertions |
| `npm run test:e2e:ci` | PASS, 17 Chromium tests |
| `git diff --check` | PASS |

## Failed / Non-Authoritative Attempts

- `php artisan route:list --compact`: option not supported.
- `php artisan route:list --columns=...`: option not supported.
- Missing route-name grep script failed due PowerShell quoting. Not reported as passed.

## Important Context

Working tree had source changes before audit began. Audit artifact files under `.sisyphus/` are separate from those pre-existing source changes.

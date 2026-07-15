<!-- markdownlint-disable MD013 MD032 MD060 -->

# UI Page Inventory Audit Matrix

Phase 12 output. Scope: audit/planning only. No Blade/CSS/controller/route/business workflow refactor in this phase.

Baseline standards:

- Metronic reference: `C:\laragon\www\dist\dist` (Metronic 8.1.8 demo42).
- Existing UI standard: `docs/ui-standardization.md`.
- Clean UI contract: `docs/ui-clean-metronic-development-plan.md`.
- Core reusable layer: `resources/views/components/ui/*`.
- Domain context: SPM akreditasi pesantren. Copy must preserve `akreditasi`, `pesantren`, `asesor`, `visitasi`, `EDPM`, `IPM`, `SDM`, `banding`.

## Source Inventory

| Source | Evidence | Purpose |
|---|---|---|
| `routes/web.php` | route groups for `/dashboard`, `/profile`, `/accounts`, `/admin/*`, `/asesor/*`, `/pesantren/*`, `/documents/*`, `/panduan*`, `_api/*` | Canonical URL and middleware source |
| `php artisan route:list --json` | 150 total registered routes; 62 GET non-API routes | Route count and route type source |
| `app/Services/SidebarMenuService.php` | role menu arrays: super admin, admin, pesantren, asesor | Visible sidebar/menu coverage source |
| `app/Http/Controllers/**` | `return view(...)` grep found 35 view returns in 31 controllers | Controller → Blade evidence |
| `resources/views/components/layout/app-sidebar.blade.php` | renders `SidebarMenuService::getMenuForRole(...)` | Runtime menu rendering evidence |
| `docs/ui-clean-metronic-development-plan.md` | table/list, button, dashboard, spacing, icon, sidemenu rules | UI audit criteria source |

## Coverage Count Gate

| Source | Count | Included In Matrix | Excluded With Reason | Notes |
|---|---:|---:|---:|---|
| `php artisan route:list --json` total routes | 150 | 150 classified by group/type | 0 unclassified at group level | Route list includes UI pages, actions, exports, downloads, redirects, auth/SSO, API-like endpoints |
| GET non-API routes | 62 | 43 role/page rows + public/auth/error/support groups | 19 duplicate aliases, downloads, redirects, or auth/public pages not audited as role dashboard surfaces | GET-only count includes `/`, auth, SSO, secure downloads, duplicate route aliases |
| Visible sidebar/menu items | 38 role-visible items | 38 mapped to matrix rows or duplicate role rows | 0 | Super admin inherits admin operational menu plus extra system items |
| Controller `return view(...)` evidence | 35 view returns | 31 role-app view candidates + auth/public/support views | 4 auth/public/support outside role dashboard scope | Route view pages (`panduan-*`, `/`) also included manually |
| Final role UI audit rows | 43 | 43 | 0 | Rows include duplicate role access where same route appears for admin and super admin |

## Route Type Classification Gate

| Route Group | Count / Scope | Route Type | UI Audit Treatment | Evidence |
|---|---:|---|---|---|
| `/dashboard` | 1 GET | UI page | Audited per role because view branches by role | `routes/web.php:32`, `DashboardController@return view('dashboard.index')` |
| `/profile` | 1 GET + 4 mutation routes | UI page + action-only | Audit GET only; mutations excluded from UI matrix | `routes/web.php:36-42`, `ProfileController@return view('profile.edit')` |
| `/accounts` | 1 GET + 5 mutation routes | UI page + action-only | Audit index only; store/update/delete/status/SSO action-only | `routes/web.php:56-66`, `AccountController@index` |
| `/admin/*` | Admin UI + action routes | UI page, action-only, export/download, modal action | Audit GET pages; actions summarized for context | `routes/web.php:90-250` |
| `/asesor/*` | Asesor UI + workflow actions | UI page, action-only, modal action | Audit GET pages; workflow POSTs excluded | `routes/web.php:257-311` |
| `/pesantren/*` | Pesantren UI + workflow actions | UI page, action-only, modal action | Audit GET pages; workflow POSTs excluded | `routes/web.php:318-373` |
| `/documents/*` | document index/view/download | UI page + document view/download | Audit index; view/download classified export/download | `routes/web.php:68-79`, `DocumentController@index/view/download` |
| `/secure/asesor-docs/*` | 1 GET | export/download | Excluded from UI audit | `routes/web.php:382-384` |
| `/panduan*` | redirect + 4 role pages | redirect + UI pages | Audit role pages; redirect excluded | `routes/web.php:394-416` |
| `_api/*` | 8 routes | API-like endpoint | Excluded from UI audit | `routes/web.php:423-432` |
| Auth/SSO routes | route files under `auth.php` and `sso/sso.php` | auth/support UI + action-only | Not role dashboard surfaces; optional later auth polish | `require __DIR__.'/auth.php'`, `require __DIR__.'/sso/sso.php'` |

## Role URL/Page Inventory

Legend: `UI page` rows enter UI audit matrix. `action-only`, `export/download`, `redirect`, `API-like endpoint`, and `non-UI` rows are excluded from visual refactor unless they render an app page.

| Role | Access Evidence | Menu/Section | Route Name | URL Pattern | Method | Route Type | Controller@Action | Blade View | Middleware/Role | Page Type | Menu Coverage | Source Evidence |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Public | `web` | Public landing | unnamed | `/` | GET | UI page | `ViewController` | `welcome` | `web` | Public/support | Not sidebar | `routes/web.php:30` |
| All auth roles | `auth, verified` | Monitoring | `dashboard` | `/dashboard` | GET | UI page | `DashboardController` | `dashboard.index` | `auth, verified`; role inferred inside controller/view | Dashboard | Sidebar for all roles | `routes/web.php:32-34`, `SidebarMenuService.php:136/224/363` |
| All auth roles | `auth` | Profile/settings | `profile.edit` | `/profile` | GET | UI page | `ProfileController@edit` | `profile.edit` | `auth` | Settings/profile | Header/user menu, not sidebar | `routes/web.php:36-42`, controller view return |
| All auth roles | `auth, verified` | Dokumen | `documents.index` | `/documents/{doc?}` | GET | UI page | `DocumentController@index` | `documents.index` | `auth, verified` | Documents/list | Linked from dashboard/actions, not sidebar canonical | `routes/web.php:68-79`, controller view return |
| Super Admin | `role super_admin bypass noted in panduan comment; SidebarMenuService ROLE_SUPER_ADMIN` | Manajemen Sistem | `accounts.index` | `/accounts` | GET | UI page | `AccountController@index` | `admin.accounts.index` | `permission:account.view` | Index/list | Visible | `SidebarMenuService.php:59-67`, `routes/web.php:56-66` |
| Super Admin | `SidebarMenuService ROLE_SUPER_ADMIN` | Manajemen Sistem | `admin.roles.index` | `/admin/roles` | GET | UI page | `RoleController@index` | `admin.roles.index` | `permission:master.role` | Index/list + modal | Visible | `SidebarMenuService.php:69-77`, `routes/web.php:46-54` |
| Super Admin | `SidebarMenuService ROLE_SUPER_ADMIN` | Manajemen Sistem | `admin.role-permission.index` | `/admin/master-role-permission` | GET | UI page | `RolePermissionController@index` | `admin.role-permission.index` | `role:admin`, `permission:master.role` | Matrix/settings | Visible | `SidebarMenuService.php:79-87`, `routes/web.php:147-153` |
| Super Admin | `SidebarMenuService ROLE_SUPER_ADMIN` | Manajemen Sistem | `admin.failed-notifications` | `/admin/failed-notifications` | GET | UI page | `FailedNotificationController@index` | `admin.failed-notifications.index` | `role:admin`, `permission:notification.view` | Ops/list | Visible | `SidebarMenuService.php:89-97`, `routes/web.php:228-236` |
| Super Admin | `SidebarMenuService ROLE_SUPER_ADMIN` | Manajemen Sistem | `admin.trash` | `/admin/trash` | GET | UI page | `TrashController@index` | `admin.trash.index` | `role:admin`, `permission:trash.view` | Ops/list | Visible | `SidebarMenuService.php:99-107`, `routes/web.php:238-249` |
| Super Admin | `role:super_admin` | Panduan | `panduan.superadmin` | `/panduan-superadmin` | GET | UI page | `ViewController` | `panduan.superadmin` | `role:super_admin` | Guide/support | Role panduan route | `routes/web.php:398-401` |
| Admin | `role:admin`, menu `getAdminMenu()` | Monitoring | `dashboard` | `/dashboard` | GET | UI page | `DashboardController` | `dashboard.index` | `auth, verified`; role branch admin | Dashboard | Visible | `SidebarMenuService.php:224-232`, `routes/web.php:32-34` |
| Admin | `role:admin` | Monitoring | `admin.akreditasi` | `/admin/akreditasi` | GET | UI page | `AkreditasiController@index` | `admin.akreditasi.index` | `role:admin` | Workflow list | Visible | `SidebarMenuService.php:234-242`, `routes/web.php:155-163` |
| Admin | `role:admin` | Monitoring | `admin.akreditasi-detail` | `/admin/akreditasi/{uuid}` | GET | UI page | `AkreditasiDetailController@show` | `admin.akreditasi.detail` | `role:admin` | Detail/workflow/scoring | Detail from list | `routes/web.php:165-175`, controller view return |
| Admin | `role:admin`, `permission:pesantren.view` | Operasional Akreditasi | `admin.pesantren.index` | `/admin/pesantren` | GET | UI page | `PesantrenController@index` | `admin.pesantren.index` | `role:admin`, `permission:pesantren.view` | Index/list | Visible | `SidebarMenuService.php:249-257`, `routes/web.php:217-226` |
| Admin | `role:admin`, `permission:pesantren.view` | Operasional Akreditasi | `admin.pesantren.detail` | `/admin/pesantren/{uuid}` | GET | UI page | `PesantrenController@show` | `admin.pesantren.detail` | `role:admin`, `permission:pesantren.view` | Detail | Detail from list | `routes/web.php:218-220`, controller view return |
| Admin | `role:admin` | Operasional Akreditasi | `admin.asesor.index` | `/admin/asesor` | GET | UI page | `AsesorController@index` | `admin.asesor.index` | `role:admin` | Index/list | Visible | `SidebarMenuService.php:259-267`, `routes/web.php:176-191` |
| Admin | `role:admin` | Operasional Akreditasi | `admin.asesor.detail` | `/admin/asesor/{uuid}` | GET | UI page | `AsesorController@show` | `admin.asesor.detail` | `role:admin` | Detail | Detail from list | `routes/web.php:176-191`, controller view return |
| Admin | `role:admin`, `permission:banding.view` | Operasional Akreditasi | `admin.banding` | `/admin/banding` | GET | UI page | `BandingController@index` | `admin.banding.index` | `role:admin`, `permission:banding.view` | Index/list | Visible | `SidebarMenuService.php:269-277`, `routes/web.php:193-211` |
| Admin | `role:admin`, `permission:banding.view` | Operasional Akreditasi | `admin.banding-detail` | `/admin/banding/{id}` | GET | UI page | `BandingDetailController@show` | `admin.banding.detail` | `role:admin`, `permission:banding.view` | Detail/workflow | Detail from list | `routes/web.php:193-211`, controller view return |
| Admin | `role:admin`, `permission:master.edpm` | Master Data | `admin.master-edpm` | `/admin/master-edpm` | GET | UI page | `MasterEdpmController@index` | `admin.master-edpm.index` | `role:admin`, `permission:master.edpm` | Master data/settings | Visible | `SidebarMenuService.php:284-292`, `routes/web.php:95-116` |
| Admin | `role:admin`, `permission:master.kategori` | Master Data | `admin.master-kategori-dokumen.index` | `/admin/master-kategori-dokumen` | GET | UI page | `MasterKategoriDokumenController@index` | `admin.master-kategori-dokumen.index` | `role:admin`, `permission:master.kategori` | Master data/list | Visible | `SidebarMenuService.php:294-302`, `routes/web.php:117-129` |
| Admin | `role:admin`, `permission:master.dokumen` | Master Data | `admin.master-dokumen.index` | `/admin/master-dokumen` | GET | UI page | `MasterDokumenController@index` | `admin.master-dokumen.index` | `role:admin`, `permission:master.dokumen` | Master data/list | Visible | `SidebarMenuService.php:304-312`, `routes/web.php:131-145` |
| Admin | `role:admin`, `permission:account.view` | Administrasi | `accounts.index` | `/accounts` | GET | UI page | `AccountController@index` | `admin.accounts.index` | `permission:account.view` | Index/list | Visible | `SidebarMenuService.php:319-327`, `routes/web.php:56-66` |
| Admin | `role:admin`, `permission:notification.view` | Administrasi | `admin.failed-notifications` | `/admin/failed-notifications` | GET | UI page | `FailedNotificationController@index` | `admin.failed-notifications.index` | `role:admin`, `permission:notification.view` | Ops/list | Visible | `SidebarMenuService.php:329-337`, `routes/web.php:228-236` |
| Admin | `role:admin`, `permission:trash.view` | Administrasi | `admin.trash` | `/admin/trash` | GET | UI page | `TrashController@index` | `admin.trash.index` | `role:admin`, `permission:trash.view` | Ops/list | Visible | `SidebarMenuService.php:339-347`, `routes/web.php:238-249` |
| Admin | `role:admin` | Panduan | `panduan.admin` | `/panduan-admin` | GET | UI page | `ViewController` | `panduan.admin` | `role:admin` | Guide/support | Role panduan route | `routes/web.php:403-406` |
| Pesantren | `role:pesantren` | Monitoring | `dashboard` | `/dashboard` | GET | UI page | `DashboardController` | `dashboard.index` | `auth, verified`; role branch pesantren | Dashboard | Visible | `SidebarMenuService.php:136-144`, `routes/web.php:32-34` |
| Pesantren | `role:pesantren` | Persiapan Akreditasi | `pesantren.profile` | `/pesantren/profile` | GET | UI page | `Pesantren\ProfileController@show` | `pesantren.profile` | `role:pesantren` | Form/settings | Visible | `SidebarMenuService.php:151-159`, `routes/web.php:322-327` |
| Pesantren | `role:pesantren` | Persiapan Akreditasi | `pesantren.ipm` | `/pesantren/ipm` | GET | UI page | `IpmController@show` | `pesantren.ipm` | `role:pesantren` | Form/document upload | Visible | `SidebarMenuService.php:161-169`, `routes/web.php:329-332` |
| Pesantren | `role:pesantren` | Persiapan Akreditasi | `pesantren.sdm` | `/pesantren/sdm` | GET | UI page | `SdmController@show` | `pesantren.sdm` | `role:pesantren` | Form/table matrix | Visible | `SidebarMenuService.php:171-179`, `routes/web.php:334-337` |
| Pesantren | `role:pesantren` | Persiapan Akreditasi | `pesantren.edpm` | `/pesantren/edpm` | GET | UI page | `EdpmController@show` | `pesantren.edpm` | `role:pesantren` | Form/wizard/scoring | Visible | `SidebarMenuService.php:181-189`, `routes/web.php:339-344` |
| Pesantren | `role:pesantren` | Akreditasi | `pesantren.akreditasi` | `/pesantren/akreditasi` | GET | UI page | `Pesantren\AkreditasiController@index` | `pesantren.akreditasi` | `role:pesantren` | Workflow list | Visible | `SidebarMenuService.php:196-204`, `routes/web.php:346-356` |
| Pesantren | `role:pesantren` | Akreditasi | `pesantren.akreditasi-detail` | `/pesantren/akreditasi/{uuid}` | GET | UI page | `Pesantren\AkreditasiDetailController@show` | `pesantren.akreditasi-detail` | `role:pesantren` | Detail/workflow | Detail from list | `routes/web.php:368-372`, controller view return |
| Pesantren | `role:pesantren` | Akreditasi shortcut | `pesantren.akreditasi.perbaikan` | `/pesantren/akreditasi/perbaikan` | GET | UI page alias | `Pesantren\AkreditasiController@index` | `pesantren.akreditasi` | `role:pesantren` | Workflow filtered list | Shortcut/deep link | `routes/web.php:348-350` |
| Pesantren | `role:pesantren` | Akreditasi shortcut | `pesantren.akreditasi.kartu-kendali` | `/pesantren/akreditasi/kartu-kendali` | GET | UI page alias | `Pesantren\AkreditasiController@index` | `pesantren.akreditasi` | `role:pesantren` | Workflow filtered list | Shortcut/deep link | `routes/web.php:351-353` |
| Pesantren | `role:pesantren` | Akreditasi shortcut | `pesantren.akreditasi.hasil` | `/pesantren/akreditasi/hasil` | GET | UI page alias | `Pesantren\AkreditasiController@index` | `pesantren.akreditasi` | `role:pesantren` | Workflow filtered list | Shortcut/deep link | `routes/web.php:354-356` |
| Pesantren | `role:pesantren` | Panduan | `panduan.pesantren` | `/panduan-pesantren` | GET | UI page | `ViewController` | `panduan.pesantren` | `role:pesantren` | Guide/support | Role panduan route | `routes/web.php:413-416` |
| Asesor | `role:asesor` | Monitoring | `dashboard` | `/dashboard` | GET | UI page | `DashboardController` | `dashboard.index` | `auth, verified`; role branch asesor | Dashboard | Visible | `SidebarMenuService.php:363-371`, `routes/web.php:32-34` |
| Asesor | `role:asesor` | Akun Asesor | `asesor.profile` | `/asesor/profile` | GET | UI page | `AsesorProfileController@show` | `asesor.profile` | `role:asesor` | Profile/detail/form | Visible | `SidebarMenuService.php:378-386`, `routes/web.php:261-264` |
| Asesor | `role:asesor` | Workflow Akreditasi | `asesor.akreditasi` | `/asesor/akreditasi` | GET | UI page | `Asesor\AkreditasiController@index` | `asesor.akreditasi` | `role:asesor` | Workflow list | Visible | `routes/web.php:266-283`, controller view return |
| Asesor | `role:asesor` | Workflow shortcut | `asesor.akreditasi.review` | `/asesor/akreditasi/review-berkas` | GET | UI page alias | `Asesor\AkreditasiController@index` | `asesor.akreditasi` | `role:asesor` | Workflow filtered list | Visible shortcut | `routes/web.php:268-271` |
| Asesor | `role:asesor` | Workflow shortcut | `asesor.akreditasi.jadwal` | `/asesor/akreditasi/jadwal-visitasi` | GET | UI page alias | `Asesor\AkreditasiController@index` | `asesor.akreditasi` | `role:asesor` | Workflow filtered list | Visible shortcut | `routes/web.php:272-275` |
| Asesor | `role:asesor` | Workflow shortcut | `asesor.akreditasi.nilai` | `/asesor/akreditasi/input-nilai` | GET | UI page alias | `Asesor\AkreditasiController@index` | `asesor.akreditasi` | `role:asesor` | Workflow filtered list | Visible shortcut | `routes/web.php:276-279` |
| Asesor | `role:asesor` | Workflow shortcut | `asesor.akreditasi.laporan-visitasi` | `/asesor/akreditasi/laporan-visitasi` | GET | UI page alias | `Asesor\AkreditasiController@index` | `asesor.akreditasi` | `role:asesor` | Workflow filtered list | Visible shortcut | `routes/web.php:280-283` |
| Asesor | `role:asesor` | Workflow Akreditasi | `asesor.akreditasi-detail` | `/asesor/akreditasi/{uuid}` | GET | UI page | `Asesor\AkreditasiController@show` | `asesor.akreditasi-detail` | `role:asesor` | Detail/workflow/scoring | Detail from list | `routes/web.php:291-292`, controller view return |
| Asesor | `role:asesor` | Workflow modal/deep link | `asesor.akreditasi.catatan` | `/asesor/akreditasi/catatan/{id}` | GET | modal action | `Asesor\AkreditasiController@showCatatan` | JSON/modal content | `role:asesor` | Modal/support | Triggered from list/detail | `routes/web.php:284-285` |
| Asesor | `role:asesor` | Panduan | `panduan.asesor` | `/panduan-asesor` | GET | UI page | `ViewController` | `panduan.asesor` | `role:asesor` | Guide/support | Role panduan route | `routes/web.php:408-411` |

## Non-UI Route Group Summary

| Group | Route Type | Examples | Audit Decision |
|---|---|---|---|
| Account mutations | action-only | `accounts.store`, `accounts.update`, `accounts.destroy`, `accounts.toggle-status`, `accounts.unlink-sso` | Excluded; only modal/button UX audited on parent page |
| Admin akreditasi actions | action-only/export/modal action | `admin.akreditasi.export`, `admin.akreditasi.catatan-modal`, approve/reject/reassign/reschedule/save/finalize/toggle-lock routes | Excluded; parent detail/list pages audited |
| Admin master data mutations | action-only | master EDPM/kategori/dokumen create/update/delete | Excluded; modal/form UX audited on parent pages |
| Admin banding/pesantren/asesor actions | action-only/export/download | export, toggle lock, assign, accept/reject, upload/report download | Excluded; parent list/detail pages audited |
| Pesantren workflow mutations | action-only/modal action | profile save/draft, IPM update, SDM save, EDPM save/draft, akreditasi create/delete/cancel/banding/upload | Excluded; parent form/workflow pages audited |
| Asesor workflow mutations | action-only/modal action | schedule visitasi, reject document, save EDPM/NA/NK, finalize scoring, upload laporan | Excluded; parent list/detail/scoring pages audited |
| Documents secure files | export/download | `documents.view`, `documents.download`, `secure.asesor-docs` | Excluded from UI matrix; download affordance checked on parent pages |
| Internal APIs | API-like endpoint | `_api/notifications`, `_api/sidebar-badges`, `_api/onboarding/*` | Excluded from UI audit |
| Panduan redirect | redirect | `panduan.index` | Excluded; target role panduan pages audited |

## UI Audit Matrix

Severity scale: `Pass`, `Minor`, `Major`, `Blocker`. Fix phases are proposals only; no refactor happens in Phase 12.

| Role | URL | View | Page Type | Evidence | Table/List | Filter/Search | Buttons | Cards | Spacing | Icons | Badges/Status | Modals | Empty State | Copy | Metronic Compliance | Severity | Fix Pattern | Refactor Phase | Screenshot QA Priority |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Public | `/` | `welcome` | Public/support | `routes/web.php:30` | N/A | N/A | Minor: public CTA may not match app button hierarchy | Minor | Minor | Minor | Pass | N/A | N/A | Minor: marketing surface less critical | Pass | Minor | Optional public polish after role UI | Phase 17 | Low |
| All roles | `/dashboard` | `dashboard.index` | Dashboard | `routes/web.php:32`, controller view | N/A | N/A | Minor: role sections need final one-primary check | Minor: mixed dashboard cards remain by role | Minor | Minor | Pass | N/A | Pass | Minor: some uppercase/technical copy may remain | Pass | Major | Role dashboard grammar sweep across all branches | Phase 14 | High |
| All roles | `/profile` | `profile.edit` | Settings/profile | `ProfileController@return view` | N/A | N/A | Minor: settings actions need hierarchy review | Minor | Pass after prior shadow cleanup | Pass | Pass | N/A | N/A | Pass | Pass | Minor | Settings/profile consistency sweep | Phase 16 | Medium |
| All roles | `/documents/{doc?}` | `documents.index` | Documents/list | `DocumentController@index` | Needs review against table/list contract | Needs compact filter consistency check | Minor | Minor | Minor | Pass | Pass | N/A | Needs empty/filter-empty check | Pass | Pass | Major | Standardize document list/search/filter/actions | Phase 13 | High |
| Super Admin/Admin | `/accounts` | `admin.accounts.index` | Index/list | route + sidebar `accounts.index` | Pass reference list page | Pass compact search/tabs | Pass one primary add | Pass | Pass | Pass | Pass | Pass | Pass | Pass | Pass | Pass | Keep as reference pattern | Phase 13 reference | Medium |
| Super Admin | `/admin/roles` | `admin.roles.index` | Index/list + modal | sidebar `admin.roles.index` | Needs table/list contract audit | Needs search/filter audit | Minor | Minor | Minor | Pass | Pass | Needs modal internals visual QA | Pass | Pass | Pass | Major | Align role list modal/table/actions | Phase 13 | High |
| Super Admin | `/admin/master-role-permission` | `admin.role-permission.index` | Matrix/settings | sidebar `admin.role-permission.index` | Matrix not standard table; needs bespoke rules | N/A | Minor | Major: dense permission matrix | Major | Minor | Pass | N/A | Pass | Pass | Pass | Major | Create matrix page sub-pattern | Phase 15 | High |
| Super Admin/Admin | `/admin/failed-notifications` | `admin.failed-notifications.index` | Ops/list | sidebar `admin.failed-notifications` | Needs table/list contract audit | Needs filter/action audit | Minor | Minor | Minor | Pass | Pass | N/A | Pass | Pass | Pass | Major | Standardize ops list toolbar/actions | Phase 13 | High |
| Super Admin/Admin | `/admin/trash` | `admin.trash.index` | Ops/list | sidebar `admin.trash` | Needs table/list contract audit | Needs filter/action audit | Minor | Minor | Minor | Pass | Pass | Modal/action risk | Pass | Pass | Pass | Major | Standardize archive list/actions | Phase 13 | High |
| Super Admin | `/panduan-superadmin` | `panduan.superadmin` | Guide/support | route view | N/A | N/A | Minor | Minor | Minor | Minor | Pass | N/A | N/A | Needs domain copy QA | Pass | Minor | Panduan layout/copy consistency | Phase 17 | Low |
| Admin | `/admin/akreditasi` | `admin.akreditasi.index` | Workflow list | sidebar `admin.akreditasi` | Major: workflow list differs from simple lists by necessity | Needs contract-specific workflow filters | Minor | Minor | Minor | Pass | Pass via presenter | Modal/action triggers | Pass | Pass | Pass | Major | Define workflow list variant of table contract | Phase 15 | High |
| Admin | `/admin/akreditasi/{uuid}` | `admin.akreditasi.detail` | Detail/workflow/scoring | detail route/controller | N/A | N/A | Major: many workflow actions require hierarchy review | Major: tabs/cards/score surfaces complex | Major | Minor | Pass via presenter | Needs modal/action QA | Pass | Copy must stay formal | Pass | Major | Detail/workflow/scoring standardization | Phase 15 | High |
| Admin | `/admin/pesantren` | `admin.pesantren.index` | Index/list | sidebar `admin.pesantren.index` | Pass after Phase 11 pilot | Pass compact filters | Pass export secondary | Pass | Pass | Pass | Pass | N/A | Pass | Pass | Pass | Pass | Keep as list pattern candidate | Phase 13 reference | Medium |
| Admin | `/admin/pesantren/{uuid}` | `admin.pesantren.detail` | Detail | detail route/controller | N/A | N/A | Minor | Minor | Minor | Pass | Pass | N/A | Pass | Pass | Pass | Minor | Detail page card/action consistency | Phase 15 | Medium |
| Admin | `/admin/asesor` | `admin.asesor.index` | Index/list | sidebar `admin.asesor.index` | Pass after Phase 11 pilot | Pass filter button secondary | Pass export secondary | Pass | Pass | Pass | Pass | N/A | Pass | Pass | Pass | Pass | Keep as list pattern candidate | Phase 13 reference | Medium |
| Admin | `/admin/asesor/{uuid}` | `admin.asesor.detail` | Detail | detail route/controller | N/A | N/A | Minor | Minor | Minor | Pass | Pass | N/A | Pass | Pass | Pass | Minor | Detail page card/action consistency | Phase 15 | Medium |
| Admin | `/admin/banding` | `admin.banding.index` | Index/list | sidebar `admin.banding` | Needs table/list audit | Needs filter/status chip audit | Minor | Minor: overdue row style risk | Minor | Pass | Minor: local singular status variant maybe okay but verify | N/A | Pass | Pass | Pass | Major | Standardize banding list and overdue state | Phase 13 | High |
| Admin | `/admin/banding/{id}` | `admin.banding.detail` | Detail/workflow | detail route/controller | N/A | N/A | Major: assignment/approval hierarchy | Minor | Minor | Pass | Pass | N/A | Pass | Copy/status confirmation QA | Pass | Major | Banding detail workflow actions | Phase 15 | High |
| Admin | `/admin/master-edpm` | `admin.master-edpm.index` | Master data/settings | sidebar `admin.master-edpm` | Matrix/list hybrid needs audit | N/A | Minor | Minor | Minor | Pass | Pass | Modal/form audit | Pass | Pass | Pass | Major | Master data page pattern | Phase 16 | High |
| Admin | `/admin/master-kategori-dokumen` | `admin.master-kategori-dokumen.index` | Master data/list | sidebar route | Needs table/list contract audit | Needs compact filter audit | Minor | Minor | Minor | Pass | Pass | Modal/form audit | Pass | Pass | Pass | Major | Master category table/form pattern | Phase 13/16 | High |
| Admin | `/admin/master-dokumen` | `admin.master-dokumen.index` | Master data/list | sidebar route | Needs table/list contract audit | Needs compact filter audit | Minor | Minor | Minor | Pass | Pass | Modal/form audit | Pass | Pass | Pass | Major | Master dokumen table/form pattern | Phase 13/16 | High |
| Admin | `/panduan-admin` | `panduan.admin` | Guide/support | route view | N/A | N/A | Minor | Minor | Minor | Minor | Pass | N/A | N/A | Needs domain copy QA | Pass | Minor | Panduan layout/copy consistency | Phase 17 | Low |
| Pesantren | `/pesantren/profile` | `pesantren.profile` | Form/settings | sidebar route | N/A | N/A | Major: save/draft hierarchy and form flow | Minor | Minor | Pass | Pass | N/A | Pass | Needs form helper copy audit | Pass | Major | Pesantren form/settings standard | Phase 16 | High |
| Pesantren | `/pesantren/ipm` | `pesantren.ipm` | Form/document upload | sidebar route | N/A | N/A | Major: upload/action hierarchy | Minor | Minor | Pass | Pass | N/A | Needs upload empty states | Pass | Pass | Major | IPM form/upload standard | Phase 16 | High |
| Pesantren | `/pesantren/sdm` | `pesantren.sdm` | Form/table matrix | sidebar route | Matrix/table custom | N/A | Major: save/draft hierarchy | Minor | Minor | Pass | Pass | N/A | Pass | Pass | Pass | Major | SDM matrix/form standard | Phase 16 | High |
| Pesantren | `/pesantren/edpm` | `pesantren.edpm` | Form/wizard/scoring | sidebar route | Matrix/scoring custom | N/A | Major: step/save/draft hierarchy | Major: wizard density | Major | Minor | Pass | N/A | Pass | Needs scoring copy QA | Pass | Major | EDPM/IPR wizard standard | Phase 15 | High |
| Pesantren | `/pesantren/akreditasi` | `pesantren.akreditasi` | Workflow list | sidebar route | Needs workflow list audit | Needs focus/filter consistency check | Minor | Minor | Minor | Pass | Pass via presenter | Modal/action QA | Pass | Pass | Pass | Major | Pesantren workflow list variant | Phase 15 | High |
| Pesantren | `/pesantren/akreditasi/{uuid}` | `pesantren.akreditasi-detail` | Detail/workflow | detail route | N/A | N/A | Major: workflow action hierarchy | Major: tabs/alerts/detail cards | Minor after alert cleanup | Pass | Pass | Modal/action QA | Pass | Copy/status detail QA | Pass | Major | Pesantren detail/workflow standard | Phase 15 | High |
| Pesantren | `/pesantren/akreditasi/perbaikan` | `pesantren.akreditasi` | Workflow filtered list | alias route | Same as akreditasi index | Same | Same | Same | Same | Same | Same | Same | Same | Same | Pass | Major | Covered with parent workflow page | Phase 15 | Medium |
| Pesantren | `/pesantren/akreditasi/kartu-kendali` | `pesantren.akreditasi` | Workflow filtered list | alias route | Same as akreditasi index | Same | Same | Same | Same | Same | Same | Same | Same | Same | Pass | Major | Covered with parent workflow page | Phase 15 | Medium |
| Pesantren | `/pesantren/akreditasi/hasil` | `pesantren.akreditasi` | Workflow filtered list | alias route | Same as akreditasi index | Same | Same | Same | Same | Same | Same | Same | Same | Same | Pass | Major | Covered with parent workflow page | Phase 15 | Medium |
| Pesantren | `/panduan-pesantren` | `panduan.pesantren` | Guide/support | route view | N/A | N/A | Minor | Minor | Minor | Minor | Pass | N/A | N/A | Needs domain copy QA | Pass | Minor | Panduan layout/copy consistency | Phase 17 | Low |
| Asesor | `/asesor/profile` | `asesor.profile` | Profile/detail/form | sidebar route | N/A | N/A | Minor | Minor | Pass after shadow cleanup | Pass | Pass | N/A | Pass | Pass | Pass | Minor | Asesor profile card/action review | Phase 16 | Medium |
| Asesor | `/asesor/akreditasi` | `asesor.akreditasi` | Workflow list | route/controller | Needs workflow list audit | Needs filter/modal trigger review | Minor | Minor | Minor | Pass | Pass via presenter | Modal QA needed | Pass | Pass | Pass | Major | Asesor workflow list variant | Phase 15 | High |
| Asesor | `/asesor/akreditasi/review-berkas` | `asesor.akreditasi` | Workflow filtered list | alias route | Same as asesor akreditasi | Same | Same | Same | Same | Same | Same | Same | Same | Same | Pass | Major | Covered with parent workflow page | Phase 15 | Medium |
| Asesor | `/asesor/akreditasi/jadwal-visitasi` | `asesor.akreditasi` | Workflow filtered list | alias route | Same as asesor akreditasi | Same | Same | Same | Same | Same | Same | Same | Same | Same | Pass | Major | Covered with parent workflow page | Phase 15 | Medium |
| Asesor | `/asesor/akreditasi/input-nilai` | `asesor.akreditasi` | Workflow filtered list | alias route | Same as asesor akreditasi | Same | Same | Same | Same | Same | Same | Same | Same | Same | Pass | Major | Covered with parent workflow page | Phase 15 | Medium |
| Asesor | `/asesor/akreditasi/laporan-visitasi` | `asesor.akreditasi` | Workflow filtered list | alias route | Same as asesor akreditasi | Same | Same | Same | Same | Same | Same | Same | Same | Same | Pass | Major | Covered with parent workflow page | Phase 15 | Medium |
| Asesor | `/asesor/akreditasi/{uuid}` | `asesor.akreditasi-detail` | Detail/workflow/scoring | detail route/controller | N/A | N/A | Major: scoring/upload/action hierarchy | Major: tabs/scoring density | Major | Pass | Pass | Modal/action QA | Pass | Copy/status QA | Pass | Major | Asesor detail/scoring standard | Phase 15 | High |
| Asesor | `/panduan-asesor` | `panduan.asesor` | Guide/support | route view | N/A | N/A | Minor | Minor | Minor | Minor | Pass | N/A | N/A | Needs domain copy QA | Pass | Minor | Panduan layout/copy consistency | Phase 17 | Low |

## Screenshot QA Shortlist

| Role | URL | Why Screenshot Needed | Expected Standard | Priority | Suggested Phase |
|---|---|---|---|---|---|
| Super Admin | `/dashboard` | Role dashboard first fold; user already saw visual density issues | Shared dashboard grammar, one primary action, compact neutral cards | High | Phase 14 |
| Admin | `/dashboard` | Admin branch differs from super admin; high traffic | Same dashboard grammar with admin data only | High | Phase 14 |
| Pesantren | `/dashboard` | Pesantren branch has readiness/workflow surfaces | Same dashboard grammar, role-specific content only | High | Phase 14 |
| Asesor | `/dashboard` | Asesor branch has task workflow surfaces | Same dashboard grammar, compact action hierarchy | High | Phase 14 |
| Admin | `/admin/akreditasi` | Complex workflow list; likely table/filter drift | Workflow list variant of table contract | High | Phase 15 |
| Admin | `/admin/banding` | Status/overdue list visual risk | Compact list/filter with clear status hierarchy | High | Phase 13 |
| Admin | `/admin/master-dokumen` | Master data list/modal pattern | Consistent list + modal form pattern | High | Phase 13/16 |
| Pesantren | `/pesantren/edpm` | Dense scoring/wizard page | EDPM wizard/scoring standard | High | Phase 15 |
| Pesantren | `/pesantren/akreditasi/{uuid}` | Workflow detail and alerts | Detail/workflow standard with clear actions | High | Phase 15 |
| Asesor | `/asesor/akreditasi/{uuid}` | Scoring/upload/detail heavy page | Detail/scoring standard with consistent tabs/cards | High | Phase 15 |
| All roles | `/documents/{doc?}` | Shared document list surface | Shared list/filter/empty state standard | Medium | Phase 13 |
| All roles | `/profile` | Shared settings page | Settings/form standard | Medium | Phase 16 |

## Phase 13+ Refactor Roadmap

| Phase | Scope | Source Rows | Goal | Exit Criteria |
|---|---|---|---|---|
| Phase 13 | List/table/filter pages | `documents.index`, admin list/master/ops pages, `admin.banding`, failed notifications, trash | One table/list grammar: toolbar, filters, buttons, table density, action menu | All list rows in audit matrix `Pass` or documented exception; MetronicFrontendTest passes |
| Phase 14 | Role dashboards | `dashboard.index` branches for super admin/admin/pesantren/asesor | Shared dashboard grammar, compact cards, consistent copy/action hierarchy | Screenshot QA for all dashboard roles passes |
| Phase 15 | Workflow/detail/scoring pages | admin/pesantren/asesor akreditasi detail, banding detail, EDPM | Standard workflow detail/tabs/scoring/action hierarchy | Major workflow pages have consistent card/tabs/action/empty patterns |
| Phase 16 | Forms/settings/profile/master modals | profile, pesantren profile/IPM/SDM, master data modals, asesor profile | Standard form sections, save/draft/cancel hierarchy, validation and upload empty states | Form/settings pages have consistent section/card/button grammar |
| Phase 17 | Guides/support/copy/sidemenu final QA | panduan pages, welcome, errors, sidebar/menu, copy QA | Final polish, copy consistency, screenshot checklist closeout | No Major/Blocker audit rows remain; screenshot QA checklist complete |

## Phase 13 Readiness

Phase 13 should not start from assumptions. It should start from this matrix:

1. Pick only Phase 13 rows with `Page Type` containing `Index/list`, `Documents/list`, `Ops/list`, or `Master data/list`.
2. Sort by `Severity`: `Major` first, then `Minor`.
3. Use `admin.accounts.index`, `admin.asesor.index`, and `admin.pesantren.index` as reference patterns because they already passed/piloted list grammar.
4. Keep workflow-specific pages (`akreditasi`, `EDPM`, detail/scoring) out of Phase 13 unless they are simple list-only surfaces.
5. Commit by concern: docs/matrix update, list filter/button cleanup, table/list component updates, tests/guards.

# Technical Specification: UUID Regression Guard

**Date**: 2026-06-05  
**Status**: Implemented & Verified  
**Severity**: High (production 500 errors)

---

## 1. Problem Statement

Halaman `/admin/pesantren` dan `/admin/asesor` di production return HTTP 500.

### Root Cause
5 record di tabel `users` (id 1-5) tidak memiliki nilai `uuid` (NULL).

Record tersebut dibuat sebelum Eloquent `boot()` auto-UUID berfungsi, kemungkinan via seeder atau insert SQL mentah yang bypass Eloquent.

### Trigger
Di `resources/views/livewire/pages/admin/pesantren/index.blade.php` line 194 dan `resources/views/livewire/pages/admin/asesor/index.blade.php` line 240:

```blade
<x-ui.action-menu-item :href="route('admin.pesantren.detail', $user->uuid)">
```

Laravel `route()` helper memerlukan parameter `uuid` yang valid. Saat `$user->uuid` null, terjadi `UrlGenerationException`:

```
Missing required parameter for [Route: admin.pesantren.detail]
[URI: admin/pesantren/{uuid}] [Missing parameter: uuid]
```

### Affected Routes
| Route | Error Count | View |
|-------|-------------|------|
| `admin.pesantren` | 27 | `pages/admin/pesantren/index.blade.php:194` |
| `admin.asesor` | 15 | `pages/admin/asesor/index.blade.php:240` |

### Affected Records
| id | name | email |
|----|------|-------|
| 1 | Super Admin | superadmin@spm.test |
| 2 | Admin SPM | admin@spm.test |
| 3 | Asesor Demo | asesor@spm.test |
| 4 | Pesantren Demo | pesantren@spm.test |
| 5 | Asesor Demo 2 | asesor2@spm.test |

---

## 2. Fix Applied

### Production Hotfix
```sql
UPDATE users SET uuid = UUID() WHERE uuid IS NULL OR uuid = '';
```

5 record mendapat UUID valid via MySQL `UUID()` function.

### Verification
| Halaman | Sebelum | Sesudah |
|---------|---------|---------|
| `/admin/pesantren` | ❌ 500 | ✅ OK |
| `/admin/asesor` | ❌ 500 | ✅ OK |
| `/admin/akreditasi` | — | ✅ OK |
| `/admin/banding` | — | ✅ OK |

---

## 3. Regression Test

### File: `tests/Feature/UserModelCascadeTest.php`

Tiga test ditambahkan untuk memastikan UUID selalu terisi pada setiap user baru:

#### 3.1 Factory UUID Auto-generation
```php
public function test_user_factory_auto_generates_uuid(): void
{
    $user = User::factory()->create();
    $this->assertNotNull($user->uuid);
    $this->assertIsString($user->uuid);
    $this->assertTrue(Str::isUuid($user->uuid));
}
```

#### 3.2 Eloquent Boot UUID Auto-generation
```php
public function test_user_created_via_new_auto_generates_uuid(): void
{
    $user = new User([
        'name' => 'Test User',
        'email' => 'test-uuid@spm.test',
        'password' => bcrypt('password'),
        'role_id' => 3,
    ]);
    $user->save();
    $this->assertNotNull($user->uuid);
    $this->assertTrue(Str::isUuid($user->uuid));
}
```

#### 3.3 Explicit UUID Preservation
```php
public function test_user_created_with_explicit_uuid_preserves_it(): void
{
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $user = new User([/* ... */, 'uuid' => $uuid]);
    $user->save();
    $this->assertSame($uuid, $user->uuid);
}
```

### Test Results
| Environment | Tests | Assertions | Status |
|-------------|-------|------------|--------|
| Local (Windows, PHP 8.3.27, SQLite in-memory) | 12 | 17 | ✅ PASS |
| VPS (Ubuntu, PHP 8.3.31, SQLite in-memory) | 3 | 6 | ✅ PASS |

---

## 4. Existing UUID Mechanism

`App\Models\User::boot()` auto-generates UUID saat `creating` event:

```php
protected static function boot()
{
    parent::boot();
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    });
}
```

Mekanisme ini hanya berjalan saat Eloquent lifecycle (`save()`, `create()`, `factory()`) — tidak saat insert SQL mentah.

---

## 5. Prevention

| Layer | Measure |
|-------|---------|
| **Model** | `boot()` `creating` event auto-UUID (existing) |
| **Test** | 3 regression tests — factory, new+save, explicit UUID |
| **DB** | Kolom `users.uuid` memiliki constraint `UNIQUE` (prevent duplicate) |
| **Future** | Pertimbangkan `NOT NULL` constraint + default via trigger |

---

## 6. Rollback Plan

Jika diperlukan revert:
```sql
UPDATE users SET uuid = NULL WHERE id IN (1,2,3,4,5);
```

Tidak direkomendasikan — error 500 akan kembali muncul.

---

## 7. Deployment Checklist

- [x] Fix database VPS (`UPDATE users SET uuid...`)
- [x] Verifikasi 5 halaman admin via browser
- [x] Tulis 3 regression test
- [x] Run test lokal (12/12 pass)
- [x] Run test VPS (3/3 pass)
- [x] Cleanup dev dependencies di VPS (`composer install --no-dev`)

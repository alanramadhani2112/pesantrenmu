# Enhance Feature: Modul Hak Akses + SSO Muhammadiyah ID

## Ringkasan

Dokumen ini menjelaskan rencana peningkatan modul hak akses (RBAC) dan integrasi SSO Muhammadiyah ID untuk sistem PesantrenMu.

---

## 1. Status Saat Ini

### SSO (Sudah Ada)
- OAuth2 Authorization Code flow sudah diimplementasikan
- Config: `config/sso.php` dengan `SSO_SERVER_URL`, `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`
- Flow: preflight → authorize → callback → token exchange → get user → login
- User auto-create berdasarkan `level` dari SSO (1=Admin, 2=Pesantren, 3=Asesor)
- Profile data + access_token disimpan di tabel `profiles`

### RBAC (Sudah Ada)
- 4 role: Super Admin, Admin, Asesor, Pesantren
- Tabel `roles`, `permissions`, `role_permission` (pivot)
- Middleware `role:admin|asesor|pesantren`
- Policy per resource (AkreditasiPolicy, PesantrenPolicy, dll)
- Halaman admin: `/admin/master-role-permission` untuk kelola permission

### Create Account (Sudah Ada)
- Halaman `/accounts` untuk admin kelola akun
- User bisa dibuat via SSO (auto-create) atau manual oleh admin
- Toggle status aktif/nonaktif

---

## 2. Yang Perlu Di-enhance

### 2.1 Konfigurasi SSO di .env

**Minimal yang harus ada di `.env`:**

```env
# SSO Muhammadiyah ID
SSO_SERVER_URL=https://id.muhammadiyah.or.id/
SSO_CLIENT_ID=your-client-id
SSO_CLIENT_SECRET=your-client-secret
SSO_REDIRECT_URI=https://pesantrenmu.id/sso/auth
```

**Yang perlu ditambahkan di `config/sso.php`:**

```php
return [
    'server_url' => env('SSO_SERVER_URL', 'https://id.muhammadiyah.or.id/'),
    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'redirect_uri' => env('SSO_REDIRECT_URI', null), // fallback ke route('sso.callback')
    'redirect_url' => env('SSO_REDIRECT_URL', '/dashboard'),
    'scopes' => env('SSO_SCOPES', ''),
    'enabled' => env('SSO_ENABLED', true),
];
```

### 2.2 Enhance Role & Permission

**Saat ini:**
- Permission sudah ada tapi belum sepenuhnya di-enforce di semua fitur
- Super admin bisa kelola permission via UI

**Yang perlu ditambahkan:**
1. Enforce permission check di setiap action (bukan hanya role check)
2. Granular permission per fitur:
   - `akreditasi.view`, `akreditasi.approve`, `akreditasi.reject`, `akreditasi.delete`
   - `asesor.view`, `asesor.assign`, `asesor.manage`
   - `pesantren.view`, `pesantren.lock`, `pesantren.manage`
   - `banding.view`, `banding.review`, `banding.decide`
   - `master.edpm`, `master.dokumen`, `master.role`
   - `account.view`, `account.create`, `account.toggle`
3. Default permission set per role (seeder)
4. UI: matrix checkbox di halaman `/admin/master-role-permission`

### 2.3 Enhance Create Account

**Flow yang diinginkan:**

```
┌─────────────────────────────────────────────────────────┐
│ Cara 1: Via SSO Muhammadiyah ID (Self-register)         │
│                                                         │
│ User login via SSO → System auto-create account         │
│ → Role di-assign berdasarkan level dari SSO             │
│ → Admin bisa override role jika perlu                   │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Cara 2: Admin Create Account (Manual)                   │
│                                                         │
│ Admin buat akun → Set email, nama, role                 │
│ → User login pertama kali via SSO                       │
│ → System link akun existing dengan SSO profile          │
└─────────────────────────────────────────────────────────┘
```

### 2.4 Mapping Level SSO → Role Sistem

| Level di Muhammadiyah ID | Role di PesantrenMu | Keterangan |
|--------------------------|---------------------|-----------|
| 1 | Admin | Administrator LP2M |
| 2 | Pesantren | Pengelola pesantren |
| 3 | Asesor | Penilai akreditasi |
| (Super Admin) | Super Admin | Hanya bisa di-set manual oleh super admin |

---

## 3. Konfigurasi di Sisi Muhammadiyah ID

Yang perlu didaftarkan di panel SSO Muhammadiyah ID:

| Field | Nilai |
|-------|-------|
| Nama Sistem | PesantrenMu - Sistem Penjaminan Mutu |
| Host | `https://pesantrenmu.id` |
| Redirect URI | `https://pesantrenmu.id/sso/auth` |
| Client ID | (di-generate oleh Muhammadiyah ID) |
| Client Secret | (di-generate oleh Muhammadiyah ID) |

Setelah mendapat Client ID dan Secret, masukkan ke `.env`:

```env
SSO_SERVER_URL=https://id.muhammadiyah.or.id/
SSO_CLIENT_ID=<client-id-dari-muhammadiyah-id>
SSO_CLIENT_SECRET=<client-secret-dari-muhammadiyah-id>
```

---

## 4. Data yang Dikembalikan SSO

Berdasarkan implementasi saat ini (`/api/user` endpoint), SSO mengembalikan:

```json
{
    "id": 123,
    "name": "Ahmad Fauzi",
    "email": "ahmad@pesantren.sch.id",
    "level": 2,
    // ... data lainnya
}
```

Data ini disimpan di tabel `profiles.data` (JSON) untuk referensi.

---

## 5. Checklist Implementasi

### Phase 1: Perbaikan SSO Config
- [ ] Tambah `SSO_REDIRECT_URI` ke `.env.example`
- [ ] Tambah `SSO_ENABLED` toggle di config
- [ ] Tambah fallback graceful jika SSO server down
- [ ] Tambah logging untuk setiap SSO event (login, create, link)

### Phase 2: Enhance Permission Enforcement
- [ ] Buat seeder default permission per role
- [ ] Tambah middleware `permission:xxx` atau gunakan Gate/Policy
- [ ] Enforce permission di setiap Livewire action (bukan hanya route level)
- [ ] Update UI matrix di `/admin/master-role-permission`

### Phase 3: Enhance Account Management
- [ ] Tambah kolom `sso_linked` (boolean) di users table
- [ ] Tambah UI indicator "Linked to Muhammadiyah ID" di halaman accounts
- [ ] Tambah fitur "Link existing account to SSO" untuk admin
- [ ] Tambah fitur "Unlink SSO" untuk admin
- [ ] Tambah audit log untuk setiap perubahan role/permission

### Phase 4: Testing & Security
- [ ] Test: SSO login flow end-to-end
- [ ] Test: Role assignment dari SSO level
- [ ] Test: Permission enforcement di setiap fitur
- [ ] Test: Account linking/unlinking
- [ ] Security: Validate state parameter (sudah ada)
- [ ] Security: Token expiry handling
- [ ] Security: Rate limit pada SSO callback

---

## 6. Environment Variables Lengkap

```env
# ===== SSO Muhammadiyah ID =====
SSO_ENABLED=true
SSO_SERVER_URL=https://id.muhammadiyah.or.id/
SSO_CLIENT_ID=
SSO_CLIENT_SECRET=
SSO_REDIRECT_URI=https://pesantrenmu.id/sso/auth
SSO_REDIRECT_URL=/dashboard
SSO_SCOPES=

# ===== Akreditasi =====
AKREDITASI_BANDING_LIMIT=1
AKREDITASI_BANDING_REVIEW_DAYS=14
AKREDITASI_REJECTION_LIMIT=3
AKREDITASI_PERBAIKAN_DEADLINE_DAYS=14
AKREDITASI_POLLING_INTERVAL=10
AKREDITASI_PRESENCE_ENABLED=false

# ===== Timeout =====
AKREDITASI_ASSESSMENT_DURATION_DAYS=30
AKREDITASI_VISITASI_DURATION_DAYS=14
AKREDITASI_REMINDER_DAYS_BEFORE=3
AKREDITASI_ESCALATION_INTERVAL_DAYS=1

# ===== Trash =====
TRASH_RETENTION_DAYS=90
```

---

*Dokumen ini dibuat sebagai referensi untuk pengembangan selanjutnya.*

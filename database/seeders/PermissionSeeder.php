<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the 18 canonical permission keys and the default role
     * mapping. Idempotent: safe to re-run.
     *
     * Super admin (id=4) is NOT mapped here. The hasPermission()
     * shortcut on User returns true for super admin without consulting
     * the pivot, so seeding rows for it would just be dead weight.
     */
    public function run(): void
    {
        $catalog = [
            // documents group
            ['documents.manage', 'Kelola Dokumen', Permission::GROUP_DOCUMENTS, 'Upload, edit, dan hapus dokumen di katalog dokumen wajib.'],
            ['document_categories.manage', 'Kelola Kategori Dokumen', Permission::GROUP_DOCUMENTS, 'CRUD master kategori dokumen termasuk pengaturan visibilitas.'],

            // akreditasi group
            ['edpm.manage', 'Kelola EDPM', Permission::GROUP_AKREDITASI, 'Mengisi dan mengelola Evaluasi Diri Penjaminan Mutu pesantren.'],
            ['edpm.review', 'Tinjau EDPM', Permission::GROUP_AKREDITASI, 'Memberikan catatan dan melakukan review terhadap EDPM.'],
            ['akreditasi.assign', 'Ajukan Akreditasi', Permission::GROUP_AKREDITASI, 'Mengajukan permohonan akreditasi pesantren.'],
            ['akreditasi.review', 'Review Akreditasi', Permission::GROUP_AKREDITASI, 'Melakukan visitasi, penilaian, dan pelaporan akreditasi.'],
            ['master_data.view', 'Akses Master Data', Permission::GROUP_AKREDITASI, 'Mengakses menu master data sistem akreditasi.'],

            // users group
            ['asesor.manage', 'Kelola Asesor', Permission::GROUP_USERS, 'Tambah, edit, dan kelola data asesor.'],
            ['pesantren.manage', 'Kelola Pesantren', Permission::GROUP_USERS, 'Tambah, edit, dan kelola data pesantren.'],
            ['users.manage', 'Kelola Akun Pengguna', Permission::GROUP_USERS, 'Mengelola akun pengguna lintas role.'],
            ['roles.manage', 'Kelola Role', Permission::GROUP_USERS, 'CRUD role sistem (super admin only).'],
            ['permissions.manage', 'Kelola Hak Akses', Permission::GROUP_USERS, 'Mengubah pemetaan hak akses per role (super admin only).'],

            // banding group
            ['banding.review', 'Tinjau Banding', Permission::GROUP_BANDING, 'Memproses dan memutuskan banding hasil akreditasi.'],
            ['banding.submit', 'Ajukan Banding', Permission::GROUP_BANDING, 'Mengajukan banding atas hasil akreditasi.'],

            // profile group
            ['profile.edit', 'Edit Profil Sendiri', Permission::GROUP_PROFILE, 'Mengubah profil dan kredensial sendiri.'],

            // dashboard group
            ['dashboard.view', 'Akses Dashboard', Permission::GROUP_DASHBOARD, 'Membuka halaman dashboard utama.'],

            // system group
            ['audit_log.view', 'Lihat Audit Log', Permission::GROUP_SYSTEM, 'Membuka log audit aktivitas sistem.'],
            ['system_config.manage', 'Kelola Konfigurasi Sistem', Permission::GROUP_SYSTEM, 'Mengubah konfigurasi sistem dan pengaturan global.'],
        ];

        foreach ($catalog as [$key, $label, $group, $description]) {
            Permission::updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'group' => $group,
                    'description' => $description,
                ]
            );
        }

        // Default mapping per role.
        $admin = Role::find(Role::ID_ADMIN);
        $asesor = Role::find(Role::ID_ASESOR);
        $pesantren = Role::find(Role::ID_PESANTREN);

        if ($admin) {
            // Admin = everything except RBAC config (only super admin manages it).
            $adminKeys = Permission::query()
                ->whereNotIn('key', ['roles.manage', 'permissions.manage'])
                ->pluck('id', 'key');
            $admin->syncPermissions($adminKeys->values()->all());
        }

        if ($asesor) {
            $asesorKeys = Permission::query()
                ->whereIn('key', [
                    'dashboard.view',
                    'akreditasi.review',
                    'banding.review',
                    'profile.edit',
                ])
                ->pluck('id')
                ->all();
            $asesor->syncPermissions($asesorKeys);
        }

        if ($pesantren) {
            $pesantrenKeys = Permission::query()
                ->whereIn('key', [
                    'dashboard.view',
                    'edpm.manage',
                    'akreditasi.assign',
                    'banding.submit',
                    'profile.edit',
                ])
                ->pluck('id')
                ->all();
            $pesantren->syncPermissions($pesantrenKeys);
        }
    }
}

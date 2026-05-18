<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Seed all granular permission keys into the permissions table.
     * Idempotent: safe to re-run (uses updateOrCreate).
     *
     * Super admin (id=4) is NOT mapped here. The hasPermission()
     * shortcut on User returns true for super admin without consulting
     * the pivot, so seeding rows for it would just be dead weight.
     */
    public function run(): void
    {
        $catalog = [
            // Akreditasi group
            ['akreditasi.view', 'Lihat Akreditasi', 'akreditasi', 'Melihat daftar dan detail akreditasi.'],
            ['akreditasi.approve', 'Approve Akreditasi', 'akreditasi', 'Menyetujui hasil akreditasi.'],
            ['akreditasi.reject', 'Reject Akreditasi', 'akreditasi', 'Menolak hasil akreditasi.'],
            ['akreditasi.delete', 'Hapus Akreditasi', 'akreditasi', 'Menghapus data akreditasi.'],
            ['akreditasi.finalize', 'Finalisasi Akreditasi', 'akreditasi', 'Memfinalisasi proses akreditasi.'],

            // Asesor group
            ['asesor.view', 'Lihat Asesor', 'asesor', 'Melihat daftar dan detail asesor.'],
            ['asesor.assign', 'Assign Asesor', 'asesor', 'Menugaskan asesor ke akreditasi.'],
            ['asesor.manage', 'Kelola Asesor', 'asesor', 'Tambah, edit, dan kelola data asesor.'],

            // Pesantren group
            ['pesantren.view', 'Lihat Pesantren', 'pesantren', 'Melihat daftar dan detail pesantren.'],
            ['pesantren.lock', 'Kunci Pesantren', 'pesantren', 'Mengunci data pesantren agar tidak bisa diedit.'],
            ['pesantren.manage', 'Kelola Pesantren', 'pesantren', 'Tambah, edit, dan kelola data pesantren.'],

            // Banding group
            ['banding.view', 'Lihat Banding', 'banding', 'Melihat daftar dan detail banding.'],
            ['banding.review', 'Review Banding', 'banding', 'Memproses dan meninjau banding.'],
            ['banding.decide', 'Putuskan Banding', 'banding', 'Memutuskan hasil banding akreditasi.'],

            // Master group
            ['master.edpm', 'Kelola Master EDPM', 'master', 'Mengelola master data EDPM (komponen dan butir).'],
            ['master.dokumen', 'Kelola Master Dokumen', 'master', 'Mengelola master data dokumen.'],
            ['master.kategori', 'Kelola Master Kategori', 'master', 'Mengelola master kategori dokumen.'],
            ['master.role', 'Kelola Master Role', 'master', 'Mengelola role dan hak akses sistem.'],

            // Account group
            ['account.view', 'Lihat Akun', 'account', 'Melihat daftar akun pengguna.'],
            ['account.create', 'Buat Akun', 'account', 'Membuat akun pengguna baru.'],
            ['account.toggle', 'Toggle Status Akun', 'account', 'Mengaktifkan atau menonaktifkan akun pengguna.'],
            ['account.delete', 'Hapus Akun', 'account', 'Menghapus akun pengguna.'],

            // Trash group
            ['trash.view', 'Lihat Trash', 'trash', 'Melihat data yang sudah dihapus (soft delete).'],
            ['trash.restore', 'Restore Trash', 'trash', 'Mengembalikan data yang sudah dihapus.'],
            ['trash.purge', 'Purge Trash', 'trash', 'Menghapus permanen data dari trash.'],

            // Notification group
            ['notification.view', 'Lihat Notifikasi', 'notification', 'Melihat daftar notifikasi.'],
            ['notification.retry', 'Retry Notifikasi', 'notification', 'Mengirim ulang notifikasi yang gagal.'],
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
    }
}

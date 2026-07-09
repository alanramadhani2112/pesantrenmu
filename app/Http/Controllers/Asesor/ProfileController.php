<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Services\AsesorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(private AsesorService $asesorService) {}

    public function show()
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $asesor = $this->asesorService->getProfile(auth()->id());

        return view('asesor.profile', compact('asesor'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isAsesor(), 403);

        $request->validate([
            'nama_dengan_gelar' => 'required|string|max:255',
            'nama_tanpa_gelar' => 'required|string|max:255',
            'email_pribadi' => 'nullable|email',
            'foto_upload' => 'nullable|image|max:1024',
            'ktp_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'ijazah_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'kartu_nbm_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'password' => 'nullable|min:8',
            'current_password' => 'required_with:password|current_password',
        ], [
            'required' => ':attribute wajib diisi.',
            'mimes' => ':attribute harus berformat PDF, JPG, JPEG, atau PNG.',
            'max' => 'Ukuran :attribute tidak boleh lebih dari :max KB (2MB).',
            'email' => 'Format :attribute tidak valid.',
            'uploaded' => ':attribute gagal diunggah. Kemungkinan file terlalu besar (Max 2MB) atau koneksi terputus.',
        ], [
            'nama_dengan_gelar' => 'Nama dengan Gelar',
            'nama_tanpa_gelar' => 'Nama tanpa Gelar',
            'email_pribadi' => 'Email Pribadi',
            'ktp_file_upload' => 'File KTP',
            'ijazah_file_upload' => 'File Ijazah',
            'kartu_nbm_file_upload' => 'File Kartu NBM',
        ]);

        $asesor = $this->asesorService->getProfile(auth()->id());

        $data = $request->only([
            'nama_dengan_gelar', 'nama_tanpa_gelar', 'nbm_nia', 'nomor_induk_asesor_pm',
            'whatsapp', 'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
            'email_pribadi', 'alamat_rumah', 'provinsi', 'kota_kabupaten',
            'status_perkawinan', 'unit_kerja', 'profesi', 'jabatan_utama',
            'pendidikan_terakhir', 'alamat_kantor', 'telp_kantor', 'tahun_terbit_sertifikat',
        ]);

        // Experience arrays (JSON)
        $data['riwayat_pendidikan'] = json_decode($request->input('riwayat_pendidikan', '[]'), true) ?: [];
        $data['pengalaman_pelatihan'] = json_decode($request->input('pengalaman_pelatihan', '[]'), true) ?: [];
        $data['pengalaman_bekerja'] = json_decode($request->input('pengalaman_bekerja', '[]'), true) ?: [];
        $data['pengalaman_berorganisasi'] = json_decode($request->input('pengalaman_berorganisasi', '[]'), true) ?: [];
        $data['karya_publikasi'] = json_decode($request->input('karya_publikasi', '[]'), true) ?: [];

        // Handle foto (public disk)
        $oldFotoPath = null;
        if ($request->hasFile('foto_upload')) {
            $newFotoPath = $request->file('foto_upload')->store('asesor_docs', 'public');
            if ($newFotoPath) {
                $oldFotoPath = $asesor->foto;
                $data['foto'] = $newFotoPath;
            }
        }

        // Handle private documents (local disk)
        $privateFields = [
            'ktp_file' => 'ktp_file_upload',
            'ijazah_file' => 'ijazah_file_upload',
            'kartu_nbm_file' => 'kartu_nbm_file_upload',
        ];

        $newPrivatePaths = [];
        foreach ($privateFields as $dbField => $inputName) {
            if ($request->hasFile($inputName)) {
                $newPath = $request->file($inputName)->store('asesor_private_docs', 'local');
                if ($newPath) {
                    $newPrivatePaths[$dbField] = [
                        'old' => $asesor->$dbField,
                        'new' => $newPath,
                    ];
                    $data[$dbField] = $newPath;
                }
            }
        }

        $success = $this->asesorService->updateProfile(auth()->id(), $data);

        if ($success) {
            // Delete old files only after successful DB update
            if (isset($newFotoPath) && $oldFotoPath) {
                Storage::disk('public')->delete($oldFotoPath);
            }
            foreach ($newPrivatePaths as ['old' => $oldPath]) {
                if ($oldPath) {
                    Storage::disk('local')->delete($oldPath);
                }
            }

            // Update password if provided
            if ($request->filled('password')) {
                auth()->user()->update([
                    'password' => Hash::make($request->input('password')),
                ]);
            }

            return back()->with('success', 'Profil asesor berhasil diperbarui.');
        }

        return back()->with('error', 'Gagal memperbarui profil asesor.');
    }
}

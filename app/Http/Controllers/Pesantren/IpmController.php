<?php

namespace App\Http\Controllers\Pesantren;

use App\Http\Controllers\Controller;
use App\Services\PesantrenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IpmController extends Controller
{
    public function __construct(private PesantrenService $pesantrenService) {}

    public function show()
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $ipm = $this->pesantrenService->getIpm(auth()->id());
        $pesantren = $this->pesantrenService->getProfile(auth()->id());

        $existingFiles = [
            'nsp_file' => $ipm->nsp_file,
            'lulus_santri_file' => $ipm->lulus_santri_file,
            'kurikulum_file' => $ipm->kurikulum_file,
            'buku_ajar_file' => $ipm->buku_ajar_file,
        ];

        return view('pesantren.ipm', compact('ipm', 'pesantren', 'existingFiles'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $pesantren = $this->pesantrenService->getProfile(auth()->id());

        if ($pesantren->is_locked) {
            return back()->with('error', 'Data terkunci karena sedang dalam proses akreditasi.');
        }

        $request->validate([
            'nsp_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'lulus_santri_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'kurikulum_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'buku_ajar_file_upload' => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'mimes' => ':attribute harus berformat PDF, JPG, JPEG, atau PNG.',
            'max' => 'Ukuran :attribute tidak boleh lebih dari :max KB (2MB).',
        ], [
            'nsp_file_upload' => 'File NSP',
            'lulus_santri_file_upload' => 'File Lulus Santri',
            'kurikulum_file_upload' => 'File Kurikulum',
            'buku_ajar_file_upload' => 'File Buku Ajar',
        ]);

        $ipm = $this->pesantrenService->getIpm(auth()->id());
        $data = [];
        $newlyStored = [];

        $fileFields = [
            'nsp_file_upload' => 'nsp_file',
            'lulus_santri_file_upload' => 'lulus_santri_file',
            'kurikulum_file_upload' => 'kurikulum_file',
            'buku_ajar_file_upload' => 'buku_ajar_file',
        ];

        foreach ($fileFields as $inputName => $dbField) {
            if ($request->hasFile($inputName)) {
                $newPath = $request->file($inputName)->store('ipm_docs', 'public');
                if ($newPath) {
                    $newlyStored[$dbField] = [
                        'old' => $ipm->$dbField,
                        'new' => $newPath,
                    ];
                    $data[$dbField] = $newPath;
                }
            }
        }

        if (empty($data)) {
            return back()->with('info', 'Tidak ada perubahan. Pilih dokumen baru jika ingin memperbarui data IPM.');
        }

        if ($this->pesantrenService->updateIpm(auth()->id(), $data)) {
            foreach ($newlyStored as ['old' => $oldPath]) {
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            return back()->with('success', 'Data IPM berhasil diperbarui.');
        }

        foreach ($newlyStored as ['new' => $newPath]) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
        }

        return back()->with('error', 'Data IPM gagal disimpan. Data terkunci atau terjadi kesalahan.');
    }
}

<?php

namespace App\Http\Controllers\Pesantren;

use App\Http\Controllers\Controller;
use App\Services\PesantrenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(private PesantrenService $pesantrenService) {}

    public function show()
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $pesantren = $this->pesantrenService->getProfile(auth()->id());
        $latestAkreditasi = $this->pesantrenService->getLatestAkreditasi(auth()->id());

        return view('pesantren.profile', compact('pesantren', 'latestAkreditasi'));
    }

    public function saveDraft(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $pesantren = $this->pesantrenService->getProfile(auth()->id());

        if ($pesantren->is_locked) {
            return back()->with('error', 'Data terkunci karena sedang dalam proses akreditasi.');
        }

        $request->validate($this->draftRules(), $this->validationMessages());

        $data = $this->buildProfileData($request);
        $units = $this->buildUnitsData($request);
        $newlyStored = $this->storeUploadedFiles($request, $pesantren);

        foreach ($newlyStored as $dbField => $paths) {
            $data[$dbField] = $paths['new'];
        }

        $success = $this->pesantrenService->updateProfile(auth()->id(), $data, $units);

        if ($success) {
            $this->deleteOldFiles($newlyStored);

            return back()->with('success', 'Draft profil pesantren berhasil disimpan.');
        }

        $this->rollbackNewFiles($newlyStored);

        return back()->with('error', 'Gagal menyimpan draft profil.');
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $pesantren = $this->pesantrenService->getProfile(auth()->id());

        if ($pesantren->is_locked) {
            return back()->with('error', 'Data terkunci karena sedang dalam proses akreditasi.');
        }

        $request->validate($this->finalRules($request, $pesantren), $this->validationMessages());

        $data = $this->buildProfileData($request);
        $units = $this->buildUnitsData($request);
        $newlyStored = $this->storeUploadedFiles($request, $pesantren);

        foreach ($newlyStored as $dbField => $paths) {
            $data[$dbField] = $paths['new'];
        }

        $success = $this->pesantrenService->updateProfile(auth()->id(), $data, $units);

        if ($success) {
            $this->deleteOldFiles($newlyStored);

            return back()->with('success', 'Profil pesantren berhasil disubmit.');
        }

        $this->rollbackNewFiles($newlyStored);

        return back()->with('error', 'Gagal menyimpan profil pesantren.');
    }

    protected function buildProfileData(Request $request): array
    {
        $data = $request->only([
            'nama_pesantren', 'ns_pesantren', 'alamat', 'kota_kabupaten',
            'provinsi', 'provinsi_kode', 'kabupaten_kode', 'tahun_pendirian',
            'nama_mudir', 'jenjang_pendidikan_mudir', 'telp_pesantren',
            'hp_wa', 'email_pesantren', 'persyarikatan', 'visi', 'misi',
            'luas_tanah', 'luas_bangunan',
        ]);

        $data['layanan_satuan_pendidikan'] = $request->input('layanan_satuan_pendidikan', []);

        return $data;
    }

    protected function buildUnitsData(Request $request): array
    {
        $units = [];
        $layanan = $request->input('layanan_satuan_pendidikan', []);

        foreach ($layanan as $unitName) {
            $units[] = [
                'unit' => $unitName,
                'jumlah_rombel' => (int) $request->input("units_data.{$unitName}.jumlah_rombel", 0),
            ];
        }

        return $units;
    }

    protected function storeUploadedFiles(Request $request, $pesantren): array
    {
        $stored = [];
        $fileFields = array_merge($this->mainDocFields(), $this->secondaryDocFields());

        foreach ($fileFields as $inputName => $dbField) {
            if ($request->hasFile($inputName)) {
                $newPath = $request->file($inputName)->store('pesantren_docs', 'public');
                if ($newPath) {
                    $stored[$dbField] = [
                        'old' => $pesantren->$dbField,
                        'new' => $newPath,
                    ];
                }
            }
        }

        return $stored;
    }

    protected function deleteOldFiles(array $newlyStored): void
    {
        foreach ($newlyStored as ['old' => $oldPath]) {
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }
    }

    protected function rollbackNewFiles(array $newlyStored): void
    {
        foreach ($newlyStored as ['new' => $newPath]) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
        }
    }

    protected function mainDocFields(): array
    {
        return [
            'status_kepemilikan_tanah_file' => 'status_kepemilikan_tanah',
            'sertifikat_nsp_file' => 'sertifikat_nsp',
            'rk_anggaran_file' => 'rk_anggaran',
            'silabus_rpp_file' => 'silabus_rpp',
            'peraturan_kepegawaian_file' => 'peraturan_kepegawaian',
            'file_lk_iapm_file' => 'file_lk_iapm',
            'laporan_tahunan_file' => 'laporan_tahunan',
        ];
    }

    protected function secondaryDocFields(): array
    {
        return [
            'dok_profil_file' => 'dok_profil',
            'dok_nsp_file' => 'dok_nsp',
            'dok_renstra_file' => 'dok_renstra',
            'dok_rk_anggaran_file' => 'dok_rk_anggaran',
            'dok_kurikulum_file' => 'dok_kurikulum',
            'dok_silabus_rpp_file' => 'dok_silabus_rpp',
            'dok_kepengasuhan_file' => 'dok_kepengasuhan',
            'dok_peraturan_kepegawaian_file' => 'dok_peraturan_kepegawaian',
            'dok_sarpras_file' => 'dok_sarpras',
            'dok_laporan_tahunan_file' => 'dok_laporan_tahunan',
            'dok_sop_file' => 'dok_sop',
        ];
    }

    protected function draftRules(): array
    {
        $fileRules = [];
        foreach (array_merge($this->mainDocFields(), $this->secondaryDocFields()) as $inputName => $dbField) {
            $fileRules[$inputName] = 'nullable|mimes:pdf,jpg,jpeg,png|max:2048';
        }

        return array_merge([
            'nama_pesantren' => 'nullable|string|max:255',
            'ns_pesantren' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:1000',
            'kota_kabupaten' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
            'provinsi_kode' => 'nullable|string|max:10',
            'kabupaten_kode' => 'nullable|string|max:10',
            'tahun_pendirian' => 'nullable|integer|min:1900|max:'.date('Y'),
            'nama_mudir' => 'nullable|string|max:255',
            'jenjang_pendidikan_mudir' => 'nullable|string|max:255',
            'telp_pesantren' => 'nullable|string|max:50',
            'hp_wa' => 'nullable|string|max:50',
            'email_pesantren' => 'nullable|email|max:255',
            'persyarikatan' => 'nullable|string|max:255',
            'visi' => 'nullable|string|max:2000',
            'misi' => 'nullable|string|max:2000',
            'luas_tanah' => 'nullable|string|max:50',
            'luas_bangunan' => 'nullable|string|max:50',
            'layanan_satuan_pendidikan' => 'nullable|array',
            'layanan_satuan_pendidikan.*' => 'string|in:sd,mi,smp,mts,sma,ma,smk,satuan_pesantren_muadalah_(SPM)',
        ], $fileRules);
    }

    protected function finalRules(Request $request, $pesantren): array
    {
        $rules = $this->draftRules();

        foreach ([
            'nama_pesantren', 'ns_pesantren', 'alamat', 'kota_kabupaten', 'provinsi_kode',
            'tahun_pendirian', 'nama_mudir', 'jenjang_pendidikan_mudir', 'telp_pesantren',
            'hp_wa', 'email_pesantren', 'persyarikatan', 'visi', 'misi', 'luas_tanah', 'luas_bangunan',
        ] as $field) {
            $rules[$field] = str_replace('nullable', 'required', $rules[$field]);
        }

        $rules['layanan_satuan_pendidikan'] = 'required|array|min:1';
        foreach ($request->input('layanan_satuan_pendidikan', []) as $unit) {
            $rules["units_data.{$unit}.jumlah_rombel"] = 'required|integer|min:1|max:9999';
        }

        foreach (array_merge($this->mainDocFields(), $this->secondaryDocFields()) as $inputName => $dbField) {
            if (blank($pesantren->{$dbField})) {
                $rules[$inputName] = str_replace('nullable', 'required', $rules[$inputName]);
            }
        }

        return $rules;
    }

    protected function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'mimes' => ':attribute harus berformat PDF, JPG, JPEG, atau PNG.',
            'max' => 'Ukuran :attribute tidak boleh lebih dari :max KB (2MB).',
            'email' => 'Format :attribute tidak valid.',
        ];
    }
}

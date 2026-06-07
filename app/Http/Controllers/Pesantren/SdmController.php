<?php

namespace App\Http\Controllers\Pesantren;

use App\Http\Controllers\Controller;
use App\Services\PesantrenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SdmController extends Controller
{
    public function __construct(private PesantrenService $pesantrenService) {}

    private array $fields = [
        'santri_l', 'santri_p',
        'ustadz_dirosah_l', 'ustadz_dirosah_p',
        'ustadz_non_dirosah_l', 'ustadz_non_dirosah_p',
        'pamong_l', 'pamong_p',
        'musyrif_l', 'musyrif_p',
        'tendik_l', 'tendik_p',
    ];

    private array $categories = [
        ['key' => 'santri', 'label' => 'Santri'],
        ['key' => 'ustadz_dirosah', 'label' => 'Ustadz Dirosah'],
        ['key' => 'ustadz_non_dirosah', 'label' => 'Ustadz Non Dirosah'],
        ['key' => 'pamong', 'label' => 'Pamong'],
        ['key' => 'musyrif', 'label' => 'Musyrif / Musyrifah'],
        ['key' => 'tendik', 'label' => 'Tenaga Kependidikan'],
    ];

    public function show()
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $pesantren = $this->pesantrenService->getProfile(auth()->id());
        $levels = $pesantren->units->pluck('unit')->toArray();
        $unitIds = $pesantren->units->pluck('id', 'unit')->toArray();

        $existingData = $this->pesantrenService->getSdm(auth()->id());

        $data = [];
        foreach ($levels as $level) {
            foreach ($this->fields as $field) {
                $data[$level][$field] = $existingData->has($level) ? $existingData[$level]->$field : 0;
            }
        }

        return view('pesantren.sdm', [
            'pesantren' => $pesantren,
            'levels' => $levels,
            'unitIds' => $unitIds,
            'data' => $data,
            'fields' => $this->fields,
            'categories' => $this->categories,
        ]);
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()->isPesantren(), 403);

        $pesantren = $this->pesantrenService->getProfile(auth()->id());

        if ($pesantren->is_locked) {
            return back()->with('error', 'Data terkunci karena sedang dalam proses akreditasi.');
        }

        $levels = $pesantren->units->pluck('unit')->toArray();
        $unitIds = $pesantren->units->pluck('id', 'unit')->toArray();

        if (empty($levels)) {
            return back()->with('warning', 'Pilih layanan satuan pendidikan di Profil Pesantren sebelum mengisi Data SDM.');
        }

        $rules = [];
        foreach ($levels as $level) {
            foreach ($this->fields as $field) {
                $rules["data.{$level}.{$field}"] = 'required|integer|min:0|max:999999';
            }
        }

        $request->validate($rules, [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
        ]);

        $allLevelData = [];
        foreach ($levels as $level) {
            $unitId = $unitIds[$level] ?? null;
            if (! $unitId) {
                return back()->with('error', 'Unit pendidikan tidak ditemukan. Perbarui Profil Pesantren sebelum menyimpan Data SDM.');
            }

            $values = [];
            foreach ($this->fields as $field) {
                $values[$field] = (int) $request->input("data.{$level}.{$field}", 0);
            }
            $values['pesantren_unit_id'] = $unitId;

            $allLevelData[] = [
                'tingkat' => $level,
                'data' => $values,
            ];
        }

        $success = DB::transaction(function () use ($allLevelData) {
            foreach ($allLevelData as $item) {
                $result = $this->pesantrenService->updateSdm(auth()->id(), $item['tingkat'], $item['data']);
                if (! $result) {
                    return false;
                }
            }
            return true;
        });

        if ($success) {
            return back()->with('success', 'Data SDM berhasil disimpan.');
        }

        return back()->with('error', 'Data SDM gagal disimpan. Data mungkin terkunci atau terjadi kesalahan.');
    }
}

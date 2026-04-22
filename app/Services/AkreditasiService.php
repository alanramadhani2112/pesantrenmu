<?php

namespace App\Services;

use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use App\Models\Asesor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Akreditasi;
use Illuminate\Support\Collection;

class AkreditasiService
{
    protected $akreditasiRepository;

    public function __construct(AkreditasiRepositoryInterface $akreditasiRepository)
    {
        $this->akreditasiRepository = $akreditasiRepository;
    }

    public function getPaginatedAkreditasis(string $statusFilter, ?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        return $this->akreditasiRepository->getPaginatedAkreditasis($statusFilter, $search, $perPage, $sortField, $sortAsc);
    }

    public function getStatusCounts(): array
    {
        return [
            'pengajuan' => $this->akreditasiRepository->getCountByStatus(6),
            'assessment' => $this->akreditasiRepository->getCountByStatus(5),
            'visitasi' => $this->akreditasiRepository->getCountByStatus([1, 2, 3, 4]), // This logic from blade was status <= 4
        ];
    }

    public function findAkreditasi(string $uuid, array $relations = []): ?Akreditasi
    {
        return $this->akreditasiRepository->findByUuid($uuid, $relations);
    }

    public function findAkreditasiById(int $id, array $relations = []): ?Akreditasi
    {
        return $this->akreditasiRepository->find($id, $relations);
    }

    public function deleteAkreditasi(int $id): bool
    {
        return $this->akreditasiRepository->delete($id);
    }

    public function getAvailableAsesors(): Collection
    {
        return $this->akreditasiRepository->getAvailableAsesors();
    }

    public function approvePengajuan(int $id, array $data): void
    {
        Assessment::where('akreditasi_id', $id)->delete();

        // Create Asesor 1
        Assessment::create([
            'akreditasi_id' => $id,
            'asesor_id' => $data['asesor_id1'],
            'tipe' => 1,
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_berakhir' => $data['tanggal_berakhir'],
        ]);

        // Create Asesor 2 if selected
        if (!empty($data['asesor_id2'])) {
            Assessment::create([
                'akreditasi_id' => $id,
                'asesor_id' => $data['asesor_id2'],
                'tipe' => 2,
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_berakhir' => $data['tanggal_berakhir'],
            ]);
        }

        $akreditasi = $this->akreditasiRepository->find($id);
        if ($akreditasi) {
            $akreditasi->update(['status' => 5]); // Assessment stage

            // Notifications logic...
            $this->notifyApprove($akreditasi, $data);
        }
    }

    protected function notifyApprove(Akreditasi $akreditasi, array $data)
    {
        $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification('assessment', 'Update Status: Assessment', 'Pengajuan akreditasi Anda telah diverifikasi dan masuk tahap Assessment.', route('pesantren.akreditasi')));

        $asesor1 = Asesor::with('user')->find($data['asesor_id1']);
        if ($asesor1 && $asesor1->user) {
            $asesor1->user->notify(new \App\Notifications\AkreditasiNotification('tugas_baru', 'Tugas Assessment Baru', 'Anda telah ditugaskan sebagai asesor 1 untuk pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name), route('asesor.akreditasi')));
        }

        if (!empty($data['asesor_id2'])) {
            $asesor2 = Asesor::with('user')->find($data['asesor_id2']);
            if ($asesor2 && $asesor2->user) {
                $asesor2->user->notify(new \App\Notifications\AkreditasiNotification('tugas_baru', 'Tugas Assessment Baru', 'Anda telah ditugaskan sebagai asesor 2 untuk pesantren ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name), route('asesor.akreditasi')));
            }
        }
    }

    public function rejectPengajuan(int $id, string $reason): void
    {
        $akreditasi = $this->akreditasiRepository->find($id);
        if ($akreditasi) {
            $akreditasi->update(['status' => 6]); // Back to pengajuan with notes

            AkreditasiCatatan::create([
                'akreditasi_id' => $id,
                'user_id' => Auth::id(),
                'tipe' => 'pengajuan',
                'catatan' => $reason,
            ]);

            $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification('di stop', 'Pengajuan Perlu Perbaikan', 'Pengajuan akreditasi Anda ditolak oleh admin. Catatan: ' . $reason . '. Silahkan perbaiki dokumen dan ajukan kembali.', route('pesantren.akreditasi')));
        }
    }

    public function rescheduleVisitasi(int $id, string $start, string $end): bool
    {
        $akreditasi = $this->akreditasiRepository->find($id);
        if ($akreditasi && !in_array($akreditasi->status, [1, 2])) {
            return $akreditasi->update([
                'tgl_visitasi' => $start,
                'tgl_visitasi_akhir' => $end,
            ]);
        }
        return false;
    }

    public function finalizeAkreditasi(int $id, array $data, bool $isApprove): bool
    {
        $akreditasi = $this->akreditasiRepository->find($id);
        if (!$akreditasi) return false;

        if ($isApprove) {
            $updateData = [
                'status' => 1,
                'nomor_sk' => $data['nomor_sk'],
                'masa_berlaku' => $data['masa_berlaku'],
                'masa_berlaku_akhir' => $data['masa_berlaku_akhir'],
                'nilai' => $data['nilai'],
                'peringkat' => $data['peringkat'],
            ];

            if (isset($data['sertifikat_file'])) {
                $path = $data['sertifikat_file']->store('akreditasi/sertifikat', 'public');
                $updateData['sertifikat_path'] = $path;
            }

            $akreditasi->update($updateData);
            
            // Notifications...
            $this->notifyFinalize($akreditasi, true);
        } else {
            $akreditasi->update([
                'status' => 2,
                'catatan' => $data['catatan'],
            ]);
            $this->notifyFinalize($akreditasi, false);
        }
        return true;
    }

    protected function notifyFinalize(Akreditasi $akreditasi, bool $isApprove)
    {
        if ($isApprove) {
            $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification('validasi', 'Akreditasi Disetujui', 'Selamat! Pengajuan akreditasi Anda telah disetujui dengan nomor SK: ' . $akreditasi->nomor_sk, route('pesantren.akreditasi-detail', $akreditasi->uuid)));
        } else {
             $akreditasi->user->notify(new \App\Notifications\AkreditasiNotification('ditolak', 'Akreditasi Ditolak', 'Pengajuan akreditasi Anda ditolak. Catatan: ' . $akreditasi->catatan, route('pesantren.akreditasi-detail', $akreditasi->uuid)));
        }
        
        $asesors = $akreditasi->assessments()->with('asesor.user')->get();
        foreach($asesors as $a) {
             if($a->asesor && $a->asesor->user) {
                 $a->asesor->user->notify(new \App\Notifications\AkreditasiNotification('validasi', 'Akreditasi Divalidasi', 'Hasil assessment for ' . ($akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name) . ' telah divalidasi oleh Admin.', route('asesor.akreditasi')));
             }
        }
    }

    public function getAsesorEdpmData(int $akreditasiId, int $asesorId): array
    {
        $edpms = \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasiId)->where('asesor_id', $asesorId)->get();
        $catatansModels = \App\Models\AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasiId)->where('asesor_id', $asesorId)->get();

        return [
            'evaluasis' => $edpms->pluck('isian', 'butir_id'),
            'nks' => $edpms->pluck('nk', 'butir_id'),
            'nvs' => $edpms->pluck('nv', 'butir_id'),
            'butirCatatans' => $edpms->pluck('catatan', 'butir_id'),
            'catatans' => $catatansModels->pluck('catatan', 'komponen_id'),
            'catatanNks' => $catatansModels->pluck('nk', 'komponen_id'),
        ];
    }

    public function updateAdminNv(int $akreditasiId, int $asesor1Id, array $nvs): void
    {
        foreach ($nvs as $butirId => $nv) {
            \App\Models\AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('butir_id', $butirId)
                ->where('asesor_id', $asesor1Id)
                ->update(['nv' => $nv]);
        }
    }
}

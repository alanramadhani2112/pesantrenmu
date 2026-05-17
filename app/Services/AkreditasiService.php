<?php

namespace App\Services;

use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use App\Models\Asesor;
use App\Services\Concerns\ChecksOptimisticLock;
use App\Services\DeadlineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Akreditasi;
use Illuminate\Support\Collection;

class AkreditasiService
{
    use ChecksOptimisticLock;
    protected $akreditasiRepository;
    protected AuditTrailService $auditTrailService;

    public function __construct(AkreditasiRepositoryInterface $akreditasiRepository, AuditTrailService $auditTrailService)
    {
        $this->akreditasiRepository = $akreditasiRepository;
        $this->auditTrailService = $auditTrailService;
    }

    public function getPaginatedAkreditasis(string $statusFilter, ?string $search = null, int $perPage = 10, string $sortField = 'created_at', bool $sortAsc = false): LengthAwarePaginator
    {
        return $this->akreditasiRepository->getPaginatedAkreditasis($statusFilter, $search, $perPage, $sortField, $sortAsc);
    }

    public function getStatusCounts(): array
    {
        $deadlineService = app(DeadlineService::class);
        return [
            'pengajuan' => $this->akreditasiRepository->getCountByStatus(6),
            'assessment' => $this->akreditasiRepository->getCountByStatus(5),
            'visitasi' => $this->akreditasiRepository->getCountByStatus([3, 4]), // Status 3 (Validasi) + 4 (Visitasi); 1=Berhasil & 2=Ditolak excluded.
            'overdue' => $deadlineService->getOverdueCount(),
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

    public function deleteAkreditasi(int $id, bool $force = false): bool
    {
        if (!$force) {
            $akreditasi = $this->akreditasiRepository->find($id);
            if ($akreditasi && $akreditasi->status === 1) {
                return false; // Tolak hapus akreditasi yang sudah Berhasil kecuali force=true
            }
        }
        return $this->akreditasiRepository->delete($id);
    }

    public function getAvailableAsesors(): Collection
    {
        return $this->akreditasiRepository->getAvailableAsesors();
    }

    public function approvePengajuan(int $id, array $data, string $clientUpdatedAt = ''): void
    {
        $akreditasi = $this->akreditasiRepository->find($id);
        if (!$akreditasi || $akreditasi->status !== 6) {
            throw new \DomainException('Status bukan Pengajuan');
        }

        // If tanggal_berakhir not explicitly provided, calculate from config
        if (empty($data['tanggal_berakhir'])) {
            $duration = (int) config('akreditasi-timeout.assessment.default_duration_days', 30);
            $data['tanggal_berakhir'] = Carbon::parse($data['tanggal_mulai'])
                ->addDays($duration)
                ->toDateString();
        }

        DB::transaction(function () use ($akreditasi, $id, $data, $clientUpdatedAt) {
            if ($clientUpdatedAt !== '') {
                $this->assertNotStale($id, $clientUpdatedAt);
            }
            // Capture existing assessments before deletion for reassignment detection
            $existingAssessments = Assessment::where('akreditasi_id', $id)->with('asesor')->get();

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

            // Audit logging for asesor assignment/reassignment
            $this->logAsesorAssignments($id, $data, $existingAssessments);

            $akreditasi->update(['status' => 5]); // Assessment stage
        });

        // Dispatch notifications AFTER transaction commits (non-blocking)
        $this->notifyApprove($akreditasi, $data);
    }

    /**
     * Log asesor assignment or reassignment audit entries.
     */
    protected function logAsesorAssignments(int $akreditasiId, array $data, \Illuminate\Database\Eloquent\Collection $existingAssessments): void
    {
        $asesor1 = Asesor::find($data['asesor_id1']);
        $asesor1Name = $asesor1->nama_dengan_gelar ?? $asesor1->user->name ?? 'Unknown';

        // Check if there was a previous asesor 1 (reassignment case)
        $previousAsesor1 = $existingAssessments->firstWhere('tipe', 1);

        if ($previousAsesor1 && $previousAsesor1->asesor_id != $data['asesor_id1']) {
            // Reassignment for asesor 1
            $oldAsesorName = $previousAsesor1->asesor->nama_dengan_gelar ?? $previousAsesor1->asesor->user->name ?? 'Unknown';
            $this->auditTrailService->log(
                $akreditasiId,
                'asesor_reassigned',
                $oldAsesorName,
                $asesor1Name,
                [
                    'old_asesor_id' => $previousAsesor1->asesor_id,
                    'new_asesor_id' => $data['asesor_id1'],
                    'tipe' => 1,
                    'tanggal_mulai' => $data['tanggal_mulai'],
                    'tanggal_berakhir' => $data['tanggal_berakhir'],
                ]
            );
        } else {
            // New assignment for asesor 1
            $this->auditTrailService->log(
                $akreditasiId,
                'asesor_assigned',
                null,
                $asesor1Name,
                [
                    'asesor_id' => $data['asesor_id1'],
                    'tipe' => 1,
                    'tanggal_mulai' => $data['tanggal_mulai'],
                    'tanggal_berakhir' => $data['tanggal_berakhir'],
                ]
            );
        }

        // Handle asesor 2
        if (!empty($data['asesor_id2'])) {
            $asesor2 = Asesor::find($data['asesor_id2']);
            $asesor2Name = $asesor2->nama_dengan_gelar ?? $asesor2->user->name ?? 'Unknown';

            $previousAsesor2 = $existingAssessments->firstWhere('tipe', 2);

            if ($previousAsesor2 && $previousAsesor2->asesor_id != $data['asesor_id2']) {
                // Reassignment for asesor 2
                $oldAsesor2Name = $previousAsesor2->asesor->nama_dengan_gelar ?? $previousAsesor2->asesor->user->name ?? 'Unknown';
                $this->auditTrailService->log(
                    $akreditasiId,
                    'asesor_reassigned',
                    $oldAsesor2Name,
                    $asesor2Name,
                    [
                        'old_asesor_id' => $previousAsesor2->asesor_id,
                        'new_asesor_id' => $data['asesor_id2'],
                        'tipe' => 2,
                        'tanggal_mulai' => $data['tanggal_mulai'],
                        'tanggal_berakhir' => $data['tanggal_berakhir'],
                    ]
                );
            } else {
                // New assignment for asesor 2
                $this->auditTrailService->log(
                    $akreditasiId,
                    'asesor_assigned',
                    null,
                    $asesor2Name,
                    [
                        'asesor_id' => $data['asesor_id2'],
                        'tipe' => 2,
                        'tanggal_mulai' => $data['tanggal_mulai'],
                        'tanggal_berakhir' => $data['tanggal_berakhir'],
                    ]
                );
            }
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

    public function rejectPengajuan(int $id, string $reason, string $clientUpdatedAt = ''): void
    {
        throw new \DomainException('Rejection at status 6 (Pengajuan) is no longer permitted.');
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

    public function finalizeAkreditasi(int $id, array $data, bool $isApprove, string $clientUpdatedAt = ''): bool
    {
        $akreditasi = $this->akreditasiRepository->find($id);
        if (!$akreditasi) return false;

        if ($akreditasi->status !== 3) {
            return false;
        }

        $result = DB::transaction(function () use ($akreditasi, $data, $isApprove, $id, $clientUpdatedAt) {
            if ($clientUpdatedAt !== '') {
                $this->assertNotStale($id, $clientUpdatedAt);
            }
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

                // Audit log: approved
                $this->auditTrailService->log(
                    $akreditasi->id,
                    'approved',
                    null,
                    $data['peringkat'],
                    [
                        'nomor_sk' => $data['nomor_sk'],
                        'nilai' => $data['nilai'],
                        'masa_berlaku' => $data['masa_berlaku'],
                        'masa_berlaku_akhir' => $data['masa_berlaku_akhir'],
                    ]
                );
            } else {
                // Audit log: rejected
                $catatan = '';
                if (!empty($data['rejection_categories'])) {
                    $categoryLabels = config('akreditasi.final_rejection_categories', []);
                    $catatan = collect($data['rejection_categories'])->map(function ($entry) use ($categoryLabels) {
                        $label = $categoryLabels[$entry['category']] ?? $entry['category'];
                        return $label . ': ' . $entry['explanation'];
                    })->implode('; ');
                }

                $this->auditTrailService->log(
                    $akreditasi->id,
                    'rejected',
                    null,
                    null,
                    [
                        'catatan' => $catatan,
                    ]
                );

                $rejectionService = app(RejectionService::class);
                $result = $rejectionService->createFinalRejection(
                    $akreditasi->id,
                    Auth::id(),
                    $data['rejection_categories']
                );
                if (!$result['success']) {
                    return false;
                }
            }
            return true;
        });

        // Dispatch notifications AFTER transaction commits (non-blocking)
        if ($result && $isApprove) {
            $this->notifyFinalize($akreditasi, true);
        }

        return (bool) $result;
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

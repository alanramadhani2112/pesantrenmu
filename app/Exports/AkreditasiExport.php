<?php

namespace App\Exports;

use App\Models\Akreditasi;
use App\Services\DeadlineService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AkreditasiExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected string  $statusFilter,
        protected ?string $search,
        protected string  $sortField,
        protected bool    $sortAsc,
    ) {}

    public function query()
    {
        if ($this->statusFilter === 'overdue') {
            $overdueIds = app(DeadlineService::class)->getOverdueAkreditasi()->pluck('id')->toArray();

            $query = Akreditasi::with(['user.pesantren', 'assessments', 'catatans.user', 'assessment1'])
                ->whereIn('id', $overdueIds);

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('pesantren', function ($q2) {
                            $q2->where('nama_pesantren', 'like', '%' . $this->search . '%');
                        });
                });
            }

            return $query->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc');
        }

        return app(\App\Services\AkreditasiService::class)->getPaginatedAkreditasis(
            $this->statusFilter,
            $this->search,
            10, // unused — we override pagination via FromQuery
            $this->sortField,
            $this->sortAsc,
        );
    }

    public function headings(): array
    {
        return [
            'Pesantren',
            'Tahap Akreditasi',
            'Nilai',
            'Peringkat',
            'Status',
            'Catatan',
        ];
    }

    public function map($item): array
    {
        $item = $item instanceof Akreditasi ? $item : Akreditasi::find($item->id);

        /** @var Akreditasi $item */
        $stage = match ((int) $item->status) {
            Akreditasi::STATUS_PENGAJUAN         => 'Pengajuan',
            Akreditasi::STATUS_VERIFIKASI_BERKAS => 'Verifikasi Berkas',
            Akreditasi::STATUS_ASSESSMENT        => 'Review Asesor',
            Akreditasi::STATUS_VISITASI          => 'Visitasi',
            Akreditasi::STATUS_PASCA_VISITASI    => 'Pasca Visitasi',
            Akreditasi::STATUS_VALIDASI_ADMIN    => 'Validasi Admin',
            Akreditasi::STATUS_SELESAI           => 'Selesai',
            Akreditasi::STATUS_DITOLAK           => 'Ditolak',
            Akreditasi::STATUS_BANDING           => 'Banding',
            default                              => 'Tidak Diketahui',
        };

        $statusLabel = match ((int) $item->status) {
            Akreditasi::STATUS_SELESAI  => 'Tersertifikasi',
            Akreditasi::STATUS_DITOLAK  => 'Ditolak',
            Akreditasi::STATUS_BANDING  => 'Banding',
            default                     => 'Dalam Proses',
        };

        return [
            $item->user?->pesantren?->nama_pesantren ?? $item->user?->name ?? '-',
            $stage,
            $item->nilai ?? '-',
            $item->peringkat ?? '-',
            $statusLabel,
            $item->catatan ?? '-',
        ];
    }
}

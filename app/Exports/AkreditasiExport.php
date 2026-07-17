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
        protected string $statusFilter,
        protected ?string $search,
        protected string $sortField,
        protected bool $sortAsc,
    ) {}

    public function query()
    {
        $sortField = $this->sortField();

        if ($this->statusFilter === 'overdue') {
            $overdueIds = app(DeadlineService::class)->getOverdueAkreditasi()->pluck('id')->toArray();

            $query = Akreditasi::with(['user.pesantren', 'assessments', 'catatans.user', 'assessment1'])
                ->whereIn('id', $overdueIds);

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhereHas('pesantren', function ($q2) {
                            $q2->where('nama_pesantren', 'like', '%'.$this->search.'%');
                        });
                });
            }

            return $query->orderBy($sortField, $this->sortAsc ? 'asc' : 'desc');
        }

        $query = Akreditasi::with(['user.pesantren', 'assessment1', 'assessment2', 'catatans.user']);

        match ($this->statusFilter) {
            'pengajuan' => $query->where('status', Akreditasi::STATUS_PENGAJUAN),
            'verifikasi' => $query->where('status', Akreditasi::STATUS_VERIFIKASI_BERKAS),
            'assessment' => $query->where('status', Akreditasi::STATUS_ASSESSMENT),
            'visitasi' => $query->whereIn('status', [Akreditasi::STATUS_VISITASI, Akreditasi::STATUS_PASCA_VISITASI]),
            'validasi' => $query->where('status', Akreditasi::STATUS_VALIDASI_ADMIN),
            'selesai' => $query->where('status', Akreditasi::STATUS_SELESAI),
            'ditolak' => $query->where('status', Akreditasi::STATUS_DITOLAK),
            'banding' => $query->where('status', Akreditasi::STATUS_BANDING),
            default => null,
        };

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('pesantren', function ($q2) {
                        $q2->where('nama_pesantren', 'like', '%'.$this->search.'%');
                    });
            });
        }

        return $query->orderBy($sortField, $this->sortAsc ? 'asc' : 'desc');
    }

    private function sortField(): string
    {
        return in_array($this->sortField, ['created_at', 'user_id', 'status', 'id'], true) ? $this->sortField : 'created_at';
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
            Akreditasi::STATUS_PENGAJUAN => 'Pengajuan',
            Akreditasi::STATUS_VERIFIKASI_BERKAS => 'Verifikasi Berkas',
            Akreditasi::STATUS_ASSESSMENT => 'Review Asesor',
            Akreditasi::STATUS_VISITASI => 'Visitasi',
            Akreditasi::STATUS_PASCA_VISITASI => 'Pasca Visitasi',
            Akreditasi::STATUS_VALIDASI_ADMIN => 'Validasi Admin',
            Akreditasi::STATUS_SELESAI => 'Selesai',
            Akreditasi::STATUS_DITOLAK => 'Ditolak',
            Akreditasi::STATUS_BANDING => 'Banding',
            default => 'Tidak Diketahui',
        };

        $statusLabel = match ((int) $item->status) {
            Akreditasi::STATUS_SELESAI => 'Tersertifikasi',
            Akreditasi::STATUS_DITOLAK => 'Ditolak',
            Akreditasi::STATUS_BANDING => 'Banding',
            default => 'Dalam Proses',
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

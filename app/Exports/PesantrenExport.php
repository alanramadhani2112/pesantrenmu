<?php

namespace App\Exports;

use App\Models\Akreditasi;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PesantrenExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected ?string $search,
        protected string  $filterStatus,
        protected string  $filterAkreditasi,
        protected string  $sortField,
        protected bool    $sortAsc,
    ) {}

    public function query()
    {
        return User::where('role_id', 3)
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereHas('pesantren', function ($pq) use ($search) {
                            $pq->where('nama_pesantren', 'like', '%' . $search . '%')
                                ->orWhere('ns_pesantren', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterAkreditasi, function ($query) {
                $match = $this->filterAkreditasi;
                if ($match === 'belum') {
                    $query->whereDoesntHave('akreditasis');
                } elseif ($match === 'proses') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->whereIn('status', Akreditasi::activeStatuses());
                    });
                } elseif ($match === 'terakreditasi') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->where('status', Akreditasi::STATUS_SELESAI);
                    });
                } elseif ($match === 'ditolak') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->where('status', Akreditasi::STATUS_DITOLAK);
                    });
                }
            })
            ->with(['pesantren', 'akreditasis'])
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc');
    }

    public function headings(): array
    {
        return [
            'Nama Pesantren',
            'Status Akreditasi',
            'Status Akun',
        ];
    }

    public function map($user): array
    {
        $latestAkreditasi = $user->akreditasis->sortByDesc('created_at')->first();

        $statusAkreditasi = ! $latestAkreditasi
            ? 'Belum Terakreditasi'
            : match ((int) $latestAkreditasi->status) {
                Akreditasi::STATUS_SELESAI => $latestAkreditasi->peringkat ?? 'Unggul',
                Akreditasi::STATUS_DITOLAK => 'Ditolak',
                default => 'Proses',
            };

        return [
            $user->pesantren->nama_pesantren ?? $user->name,
            $statusAkreditasi,
            $user->status == 1 ? 'Aktif' : 'Non-Aktif',
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PesantrenExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $filterStatus;
    protected $filterAkreditasi;
    protected $sortField;
    protected $sortAsc;

    public function __construct($search = '', $filterStatus = '', $filterAkreditasi = '', $sortField = 'name', $sortAsc = true)
    {
        $this->search = $search;
        $this->filterStatus = $filterStatus;
        $this->filterAkreditasi = $filterAkreditasi;
        $this->sortField = $sortField;
        $this->sortAsc = $sortAsc;
    }

    public function collection()
    {
        return User::where('role_id', 3)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhereHas('pesantren', function ($pq) {
                            $pq->where('nama_pesantren', 'like', '%' . $this->search . '%')
                                ->orWhere('ns_pesantren', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterAkreditasi, function ($query) {
                if ($this->filterAkreditasi === 'belum') {
                    $query->whereDoesntHave('akreditasis');
                } elseif ($this->filterAkreditasi === 'proses') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->whereNotIn('status', [1, 2]);
                    });
                } elseif ($this->filterAkreditasi === 'terakreditasi') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->where('status', 1);
                    });
                } elseif ($this->filterAkreditasi === 'ditolak') {
                    $query->whereHas('akreditasis', function ($q) {
                        $q->where('status', 2);
                    });
                }
            })
            ->with(['pesantren', 'akreditasis'])
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Pesantren',
            'NS Pesantren',
            'Email',
            'Status Akreditasi',
            'Peringkat',
            'Status Akun',
            'Tanggal Daftar',
        ];
    }

    public function map($user): array
    {
        $latestAkreditasi = $user->akreditasis->sortByDesc('created_at')->first();

        $akreditasiStatus = 'Belum Terakreditasi';
        $peringkat = '-';
        if ($latestAkreditasi) {
            if ($latestAkreditasi->status == 1) {
                $akreditasiStatus = 'Terakreditasi';
                $peringkat = $latestAkreditasi->peringkat ?? 'Unggul';
            } elseif ($latestAkreditasi->status == 2) {
                $akreditasiStatus = 'Ditolak';
            } else {
                $akreditasiStatus = 'Proses';
            }
        }

        return [
            $user->id,
            $user->pesantren->nama_pesantren ?? $user->name,
            $user->pesantren->ns_pesantren ?? '-',
            $user->email,
            $akreditasiStatus,
            $peringkat,
            $user->status == 1 ? 'Aktif' : 'Non-Aktif',
            $user->created_at->format('d/m/Y'),
        ];
    }
}

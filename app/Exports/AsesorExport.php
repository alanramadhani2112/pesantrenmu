<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AsesorExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $filterPeran;
    protected $filterPenugasan;
    protected $filterStatus;
    protected $sortField;
    protected $sortAsc;

    public function __construct($search = '', $filterPeran = '', $filterPenugasan = '', $filterStatus = '', $sortField = 'name', $sortAsc = true)
    {
        $this->search = $search;
        $this->filterPeran = $filterPeran;
        $this->filterPenugasan = $filterPenugasan;
        $this->filterStatus = $filterStatus;
        $this->sortField = $sortField;
        $this->sortAsc = $sortAsc;
    }

    public function collection()
    {
        return User::where('role_id', 2)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterPeran, function ($query) {
                $query->whereHas('asesor.assessments', function ($q) {
                    $q->where('tipe', $this->filterPeran);
                });
            })
            ->when($this->filterPenugasan, function ($query) {
                if ($this->filterPenugasan === 'bertugas') {
                    $query->whereHas('asesor.assessments.akreditasi', function ($q) {
                        $q->whereNotIn('status', [1, 2]);
                    });
                } elseif ($this->filterPenugasan === 'bebas') {
                    $query->whereDoesntHave('asesor.assessments', function ($q) {
                        $q->whereHas('akreditasi', function ($sq) {
                            $sq->whereNotIn('status', [1, 2]);
                        });
                    });
                }
            })
            ->with(['asesor.assessments.akreditasi.user.pesantren'])
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Asesor',
            'Email',
            'Pesantren Ditangani',
            'Peran Asesor',
            'Status Penugasan',
            'Status Akun',
            'Tanggal Daftar',
        ];
    }

    public function map($user): array
    {
        $latestTask = $user->asesor?->assessments->sortByDesc('created_at')->first();
        $pesantrenName = $latestTask?->akreditasi?->user?->pesantren?->nama_pesantren ?? '-';

        $peran = '-';
        if ($latestTask) {
            $peran = $latestTask->tipe == 1 ? 'Ketua' : 'Anggota';
        }

        $penugasan = '-';
        if ($latestTask) {
            $statusAkreditasi = $latestTask->akreditasi?->status;
            $penugasan = 'Sedang Bertugas';
            if ($statusAkreditasi == 4) {
                $penugasan = 'Visitasi';
            } elseif (in_array($statusAkreditasi, [1, 2])) {
                $penugasan = 'Selesai';
            }
        }

        return [
            $user->id,
            $user->name,
            $user->email,
            $pesantrenName,
            $peran,
            $penugasan,
            $user->status == 1 ? 'Aktif' : 'Non-Aktif',
            $user->created_at->format('d/m/Y'),
        ];
    }
}

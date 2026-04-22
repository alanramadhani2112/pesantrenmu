<?php

namespace App\Exports;

use App\Models\Akreditasi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AkreditasiExport implements FromCollection, WithHeadings, WithMapping
{
    protected $status;
    protected $search;
    protected $sortField;
    protected $sortAsc;

    public function __construct($status = null, $search = null, $sortField = 'created_at', $sortAsc = false)
    {
        $this->status = $status;
        $this->search = $search;
        $this->sortField = $sortField;
        $this->sortAsc = $sortAsc;
    }

    public function collection()
    {
        $query = Akreditasi::with(['user.pesantren', 'assessments', 'catatans.user']);

        if ($this->status === 'pengajuan') {
            $query->where('status', 6);
        } elseif ($this->status === 'assessment') {
            $query->where('status', 5);
        } elseif ($this->status === 'visitasi') {
            $query->where('status', '<=', 4);
        }

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('pesantren', function ($q2) {
                        $q2->where('nama_pesantren', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Pesantren',
            'Tahap Akreditasi',
            'Nilai',
            'Peringkat',
            'Status',
            'Tanggal Pengajuan',
        ];
    }

    public function map($akreditasi): array
    {
        static $no = 0;
        $no++;

        $statusLabel = Akreditasi::getStatusLabel($akreditasi->status);
        if ($akreditasi->status >= 3) {
            $statusLabel = 'Proses';
        }

        $tahap = '';
        if ($akreditasi->status == 6) {
            $tahap = 'Pengajuan: ' . $akreditasi->created_at->format('d/m/Y');
        } elseif ($akreditasi->status == 5) {
            $tahap = 'Assessment: ' . ($akreditasi->assessment1 ? \Carbon\Carbon::parse($akreditasi->assessment1->tanggal_mulai)->format('d/m/Y') : '-');
        } else {
            $tahap = 'Visitasi: ' . ($akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d/m/Y') : '-');
        }

        return [
            $no,
            $akreditasi->user->pesantren->nama_pesantren ?? $akreditasi->user->name,
            $tahap,
            $akreditasi->nilai ?? '-',
            $akreditasi->peringkat ?? '-',
            $statusLabel,
            $akreditasi->created_at->format('d/m/Y'),
        ];
    }
}

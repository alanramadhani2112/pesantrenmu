<?php

namespace App\Exports;

use App\Models\Akreditasi;
use Carbon\Carbon;
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

        match ($this->status) {
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

        if ((int) $akreditasi->status === Akreditasi::STATUS_PENGAJUAN) {
            $tahap = 'Pengajuan: '.$akreditasi->created_at->format('d/m/Y');
        } elseif ((int) $akreditasi->status === Akreditasi::STATUS_ASSESSMENT) {
            $tahap = 'Review Asesor: '.($akreditasi->assessment1 ? Carbon::parse($akreditasi->assessment1->tanggal_mulai)->format('d/m/Y') : '-');
        } elseif ((int) $akreditasi->status === Akreditasi::STATUS_PASCA_VISITASI) {
            $tahap = 'Penilaian Pasca Visitasi: '.($akreditasi->visitasi_confirmed_at ? Carbon::parse($akreditasi->visitasi_confirmed_at)->format('d/m/Y') : ($akreditasi->tgl_visitasi ? Carbon::parse($akreditasi->tgl_visitasi)->format('d/m/Y') : '-'));
        } elseif ((int) $akreditasi->status === Akreditasi::STATUS_VISITASI) {
            $tahap = Akreditasi::getStatusLabel($akreditasi->status).': '.($akreditasi->tgl_visitasi ? Carbon::parse($akreditasi->tgl_visitasi)->format('d/m/Y') : '-');
        } else {
            $tahap = Akreditasi::getStatusLabel($akreditasi->status).': '.$akreditasi->updated_at->format('d/m/Y');
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

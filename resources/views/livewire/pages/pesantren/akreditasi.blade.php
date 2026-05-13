<?php

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\Ipm;
use App\Models\SdmPesantren;
use App\Models\Edpm;
use App\Models\MasterEdpmButir;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;
    use \Livewire\WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortAsc = false;
    public $selectedAkreditasiNotes;
    public $periodeFilter = '';
    public $statusFilter = '';
    public $tahapanFilter = '';

    public function openCatatanModal($id)
    {
        $this->selectedAkreditasiNotes = Akreditasi::with(['catatans.user'])->find($id);
        $this->dispatch('open-modal', 'catatan-modal');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
    }

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isPesantren()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        return $pesantrenService->getAkreditasis(
            Auth::id(),
            $this->search,
            $this->periodeFilter,
            $this->statusFilter,
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }

    public function create($parentId = null)
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $userId = Auth::id();

        $missingData = $pesantrenService->checkDataCompleteness($userId);

        if (!empty($missingData)) {
            $errorMessage = "<ul class='text-left list-disc pl-5 mt-2'><li>" . implode("</li><li>", $missingData) . "</li></ul>";
            $this->dispatch('show-validation-alert', title: 'Data Belum Lengkap!', html: "Mohon lengkapi data berikut sebelum mengajukan akreditasi:<br>" . $errorMessage);
            return;
        }

        if ($pesantrenService->createSubmission($userId, $parentId)) {
            session()->flash('status', 'Pengajuan akreditasi berhasil dibuat.');
        } else {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal!', message: 'Pengajuan ini sudah pernah diajukan ulang sebelumnya.');
        }
    }

    public function delete($id)
    {
        $akreditasi = Akreditasi::where('user_id', Auth::id())->findOrFail($id);
        $akreditasi->delete();

        // Unlock Pesantren Data
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->pesantren->update(['is_locked' => false]);

        session()->flash('status', 'Pengajuan akreditasi berhasil dihapus. Data profil telah dibuka kunci.');
    }

    public function cancelSubmission($id)
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $pesantrenService->cancelSubmission($id, Auth::id());

        $this->dispatch('notification-received', type: 'success', title: 'Dibatalkan!', message: 'Pengajuan akreditasi telah berhasil dibatalkan.');
    }

    public function banding($id, $alasan)
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        if ($pesantrenService->submitAppeals($id, Auth::id(), $alasan)) {
            session()->flash('status', 'Pengajuan banding berhasil dikirim. Status berubah menjadi Validasi.');
        } else {
            session()->flash('error', 'Gagal mengajukan banding. Pastikan status pengajuan adalah Ditolak dan sudah melalui tahap Assessment.');
        }
    }
}; ?>

<div x-data="akreditasiPesantren" data-module-page="pesantren-akreditasi">
    <x-slot name="header">{{ __('Akreditasi') }}</x-slot>

    <x-ui.page
        title="Akreditasi"
        subtitle="Pantau pengajuan, status proses, catatan, dan tindak lanjut akreditasi pesantren."
    >
        @php
            $akreditasiRecords = $this->akreditasis;
            $akreditasiCollection = method_exists($akreditasiRecords, 'getCollection')
                ? $akreditasiRecords->getCollection()
                : collect($akreditasiRecords);
            $totalPengajuan = method_exists($akreditasiRecords, 'total')
                ? $akreditasiRecords->total()
                : $akreditasiCollection->count();
            $pengajuanProses = $akreditasiCollection->whereNotIn('status', [1, 2])->count();
            $pengajuanDitolak = $akreditasiCollection->where('status', 2)->count();
        @endphp

        <div class="row g-6 mb-6">
            <div class="col-12 col-xl-8">
                <x-ui.card
                    title="Ruang Kendali Pengajuan"
                    subtitle="Ikuti progres akreditasi pesantren dari kesiapan data sampai tindak lanjut catatan."
                    class="h-100"
                >
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <x-ui.badge variant="primary" class="mb-4">Total Pengajuan</x-ui.badge>
                                <div class="fs-2 fw-bold text-gray-900 mb-1">{{ $totalPengajuan }}</div>
                                <div class="text-muted fw-semibold fs-7">Riwayat pengajuan akreditasi yang tampil pada daftar.</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <x-ui.badge variant="info" class="mb-4">Sedang Diproses</x-ui.badge>
                                <div class="fs-2 fw-bold text-gray-900 mb-1">{{ $pengajuanProses }}</div>
                                <div class="text-muted fw-semibold fs-7">Pengajuan yang masih diproses atau belum ditolak.</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <x-ui.badge variant="warning" class="mb-4">Perlu Tindak Lanjut</x-ui.badge>
                                <div class="fs-2 fw-bold text-gray-900 mb-1">{{ $pengajuanDitolak }}</div>
                                <div class="text-muted fw-semibold fs-7">Cek catatan lalu ajukan ulang bila masih dibuka.</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-12 col-xl-4">
                <x-ui.card
                    title="Langkah Pesantren"
                    subtitle="Aksi utama tetap mengikuti tombol dan menu yang sudah tersedia di proses akreditasi pesantren."
                    class="h-100"
                >
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">1</span>
                            <div>
                                <div class="fw-bold text-gray-900">Lengkapi data</div>
                                <div class="text-muted fs-7">Pastikan profil, IPM, SDM, dan EDPM siap sebelum membuat pengajuan akreditasi.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">2</span>
                            <div>
                                <div class="fw-bold text-gray-900">Pantau status</div>
                                <div class="text-muted fs-7">Gunakan filter periode, status, dan tahapan untuk membaca progres pengajuan.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">3</span>
                            <div>
                                <div class="fw-bold text-gray-900">Tindak lanjuti catatan</div>
                                <div class="text-muted fs-7">Buka catatan, unggah dokumen, atau ajukan ulang dari menu aksi.</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-datatable.layout title="Pengajuan Akreditasi" :records="$this->akreditasis">
            <x-slot name="filters">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <x-ui.filter-select model="periodeFilter" placeholder="Periode">
                            @for($i = date('Y'); $i >= 2024; $i--)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                    </x-ui.filter-select>

                    <x-ui.filter-select
                        model="statusFilter"
                        placeholder="Status"
                        :options="['1' => 'Selesai', '2' => 'Ditolak']"
                    />

                    <x-ui.filter-select
                        model="tahapanFilter"
                        placeholder="Tahapan"
                        :options="['visitasi' => 'Visitasi']"
                    />

                    <x-ui.button x-on:click="confirmCreate" variant="primary" size="sm">
                        <x-ui.icon name="plus" class="fs-4 me-1" />
                        Buat Pengajuan
                    </x-ui.button>
                </div>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Periode</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="center">Tahap Akreditasi</x-ui.table-th>
                <x-ui.table-th align="center">Nilai</x-ui.table-th>
                <x-ui.table-th align="center">Peringkat</x-ui.table-th>
                <x-ui.table-th align="center">Catatan</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->akreditasis as $index => $item)
                <tr wire:key="akred-{{ $item->id }}">
                    <td>
                        <span class="text-gray-900 fw-bold fs-6">{{ $item->created_at->format('Y') }}</span>
                    </td>
                    <td class="text-center">
                        @if($item->status == 1)
                        @if($item->masa_berlaku_akhir && \Carbon\Carbon::parse($item->masa_berlaku_akhir)->isPast())
                        <x-ui.status-badge variant="danger">
                            Masa Berlaku Habis
                        </x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="success">
                            Selesai
                        </x-ui.status-badge>
                        @endif
                        @elseif($item->status == 2)
                        <x-ui.status-badge variant="danger">
                            Ditolak
                        </x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="primary">
                            Proses Akreditasi
                        </x-ui.status-badge>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($item->status == 1 || $item->status == 2)
                        <x-ui.status-badge variant="success">Selesai</x-ui.status-badge>
                        @elseif($item->status == 5 && $item->catatans->whereNotNull('perbaikan')->filter(fn($c) => !empty($c->perbaikan))->isNotEmpty())
                        <x-ui.status-badge variant="warning">Perlu Perbaikan Dokumen</x-ui.status-badge>
                        @elseif($item->tgl_visitasi)
                        <x-ui.status-badge variant="info">Visitasi Dijadwalkan</x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($item->nilai)
                        <span class="fw-bold text-success fs-5">{{ $item->nilai }}</span>
                        @else
                        <span class="text-muted fw-bold">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($item->peringkat)
                        <x-ui.status-badge variant="success">
                            {{ $item->peringkat == 'Unggul' ? 'Unggul / Mumtaz' : $item->peringkat }}
                        </x-ui.status-badge>
                        @else
                        <span class="text-muted fw-bold">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <x-ui.button wire:click="openCatatanModal({{ $item->id }})" variant="light" size="sm">
                            <x-ui.icon name="document" class="fs-5 me-1" />
                            {{ $item->catatans->count() > 0 ? $item->catatans->count() . ' Catatan' : 'Catatan' }}
                        </x-ui.button>
                    </td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item :href="route('pesantren.akreditasi-detail', $item->uuid)">
                                <x-ui.icon name="eye" class="fs-5 text-gray-500" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            @if ($item->status == 3 && !$item->kartu_kendali)
                                <x-ui.action-menu-item
                                    :href="route('pesantren.akreditasi-detail', ['uuid' => $item->uuid, 'activeTab' => 'kartu'])"
                                    variant="success"
                                >
                                    <x-ui.icon name="arrow-up" class="fs-5" />
                                    Unggah Kartu Kendali
                                </x-ui.action-menu-item>
                            @endif

                            @if ($item->status == 1 && $item->sertifikat_path)
                                <x-ui.action-menu-item href="{{ Storage::url($item->sertifikat_path) }}" target="_blank" variant="success">
                                    <x-ui.icon name="document" class="fs-5" />
                                    Download Sertifikat
                                </x-ui.action-menu-item>
                            @endif

                            @if ($item->status == 6)
                                <x-ui.action-menu-item
                                    variant="danger"
                                    x-on:click="confirmCancel({{ $item->id }}, '{{ $item->created_at->format('Y') }}')"
                                >
                                    <x-ui.icon name="cross-circle" class="fs-5" />
                                    Batalkan Pengajuan
                                </x-ui.action-menu-item>
                            @endif

                            @if ($item->status == 2)
                                @php
                                    $isAlreadyResubmitted = \App\Models\Akreditasi::where('parent', $item->id)->exists();
                                @endphp

                                @if (!$isAlreadyResubmitted)
                                    <x-ui.action-menu-item variant="success" x-on:click="confirmResubmit({{ $item->id }})">
                                        <x-ui.icon name="check-circle" class="fs-5" />
                                        Ajukan Ulang
                                    </x-ui.action-menu-item>
                                @else
                                    <div class="menu-item">
                                        <span class="menu-link px-3 py-2 text-muted">
                                            <x-ui.icon name="check-circle" class="fs-5" />
                                            Sudah Diajukan Ulang
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state title="Belum ada data pengajuan akreditasi" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.page>

    <!-- Modal Catatan (View Only) -->
    <x-ui.modal name="catatan-modal" focusable>
        <div class="p-0 overflow-hidden rounded-3xl spm-modal-content-scroll">
            @if($selectedAkreditasiNotes)
            @php
            $latestCatatan = $selectedAkreditasiNotes->catatans->sortByDesc('created_at')->first();
            $isRejection = $latestCatatan && !empty($latestCatatan->perbaikan);
            @endphp

            <div class="p-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-xl font-bold text-[#1e3a5f]">
                        {{ $isRejection ? 'Catatan Penolakan Visitasi' : 'Catatan Penerimaan Visitasi' }}
                    </h2>
                    <x-ui.button type="button" variant="light" size="sm" x-on:click="$dispatch('close')" class="btn-icon btn-active-light-primary text-slate-300 hover:text-slate-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-ui.button>
                </div>

                <div class="space-y-8 pr-2 custom-scrollbar">
                    @forelse($selectedAkreditasiNotes->catatans->sortByDesc('created_at') as $catatan)
                    @php
                    $isNoteRejection = !empty($catatan->perbaikan);
                    @endphp
                    <div class="bg-white border-b border-slate-50 last:border-0 pb-8 last:pb-0">
                        <div class="flex items-center gap-4 mb-6">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($catatan->user->name) }}&color=1e3a5f&background=f1f5f9"
                                class="w-12 h-12 rounded-2xl border-2 border-white shadow-sm object-cover" alt="Asesor">
                            <div>
                                <h3 class="text-sm font-black text-[#1e3a5f]">{{ $catatan->user->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    {{ $catatan->user->isAsesor() ? 'Ketua Asesor' : ($catatan->user->isAdmin() ? 'Administrator Pusat' : 'Pihak Berwenang') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Status:</p>
                                @if($isNoteRejection)
                                <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-tight">Perlu Perbaikan Dokumen</span>
                                @else
                                <span class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-tight">Visitasi Dijadwalkan</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                                    {{ $isNoteRejection ? 'Tanggal Review:' : 'Jadwal Visitasi:' }}
                                </p>
                                <p class="text-[11px] font-black text-slate-700">
                                    @if($isNoteRejection)
                                    {{ $catatan->created_at->translatedFormat('d F Y') }}
                                    @else
                                    @if($selectedAkreditasiNotes->tgl_visitasi)
                                    {{ \Carbon\Carbon::parse($selectedAkreditasiNotes->tgl_visitasi)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($selectedAkreditasiNotes->tgl_visitasi_akhir)->format('d/m/y') }}
                                    @else
                                    {{ $catatan->created_at->translatedFormat('d F Y') }}
                                    @endif
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($isNoteRejection)
                        <div class="mb-6">
                            <p class="text-[11px] font-black text-slate-800 mb-3">Dokumen yang memerlukan perbaikan</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach(explode(', ', $catatan->perbaikan) as $p)
                                <div class="flex items-center gap-2 px-3 py-2 bg-amber-500 rounded-xl text-white">
                                    @switch($p)
                                    @case('Profil Pesantren')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('IPM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('Data SDM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('EPDM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @default
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @endswitch
                                    <span class="text-[10px] font-black uppercase tracking-tight">{{ $p }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="rounded-3xl p-6 {{ $isNoteRejection ? 'bg-amber-50 text-slate-700' : 'bg-emerald-50 text-slate-700' }}">
                            <div class="text-xs leading-relaxed font-medium space-y-4 prose-sm prose-slate max-w-none">
                                {!! nl2br(e($catatan->catatan)) !!}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-400 font-medium font-bold text-xs">Tidak ada catatan ditemukan.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </x-ui.modal>
</div>

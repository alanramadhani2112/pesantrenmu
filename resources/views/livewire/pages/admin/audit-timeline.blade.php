<div>
    {{-- Filter Controls --}}
    <div class="card card-flush mb-5">
        <div class="card-body py-4">
            <div class="row g-4 align-items-end">
                {{-- Action Type Filter --}}
                <div class="col-md-3">
                    <label class="form-label fs-7 fw-semibold text-gray-600">Tipe Aksi</label>
                    <select wire:model.live="filterActionType" class="form-select form-select-sm">
                        <option value="">Semua Tipe</option>
                        @foreach($actionTypes as $type)
                            <option value="{{ $type }}">
                                {{ \App\Models\AkreditasiAuditLog::getActionTypeLabel($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Actor Filter --}}
                <div class="col-md-3">
                    <label class="form-label fs-7 fw-semibold text-gray-600">Aktor</label>
                    <select wire:model.live="filterUserId" class="form-select form-select-sm">
                        <option value="">Semua Pengguna</option>
                        @foreach($actors as $actor)
                            <option value="{{ $actor->id }}">{{ $actor->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div class="col-md-2">
                    <label class="form-label fs-7 fw-semibold text-gray-600">Dari Tanggal</label>
                    <input type="date" wire:model.live="filterDateFrom" class="form-control form-control-sm" />
                </div>

                {{-- Date To --}}
                <div class="col-md-2">
                    <label class="form-label fs-7 fw-semibold text-gray-600">Sampai Tanggal</label>
                    <input type="date" wire:model.live="filterDateTo" class="form-control form-control-sm" />
                </div>

                {{-- Reset Button --}}
                <div class="col-md-2">
                    <button wire:click="resetFilters" class="btn btn-sm btn-light-danger w-100">
                        <i class="ki-outline ki-cross fs-5 me-1"></i>
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline List --}}
    <div class="card card-flush">
        <div class="card-body pt-5">
            @if($logs->isEmpty())
                <div class="text-center py-10">
                    <i class="ki-outline ki-document fs-3x text-gray-400 mb-3"></i>
                    <p class="text-gray-500 fs-6">Belum ada riwayat audit untuk akreditasi ini.</p>
                </div>
            @else
                <div class="timeline-label">
                    @foreach($logs as $log)
                        <div class="timeline-item mb-7" x-data="{ expanded: false }">
                            <div class="d-flex align-items-start">
                                {{-- Timestamp --}}
                                <div class="timeline-label fw-semibold text-gray-600 fs-7 me-4" style="min-width: 130px;">
                                    {{ $log->created_at->format('d M Y') }}
                                    <br>
                                    <span class="text-gray-400 fs-8">{{ $log->created_at->format('H:i') }}</span>
                                </div>

                                {{-- Timeline dot --}}
                                <div class="timeline-badge me-4">
                                    <i class="fa fa-genderless fs-1 text-{{ match($log->action_type) {
                                        'status_changed' => 'primary',
                                        'asesor_assigned' => 'info',
                                        'asesor_reassigned' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'finalized' => 'primary',
                                        'banding_submitted' => 'warning',
                                        'deleted' => 'danger',
                                        default => 'gray-400',
                                    } }}"></i>
                                </div>

                                {{-- Content --}}
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        {{-- Action Type Badge --}}
                                        <span class="badge {{ \App\Models\AkreditasiAuditLog::getActionTypeBadgeClass($log->action_type) }} me-3">
                                            {{ \App\Models\AkreditasiAuditLog::getActionTypeLabel($log->action_type) }}
                                        </span>

                                        {{-- Actor Name --}}
                                        <span class="text-gray-700 fw-semibold fs-7">
                                            {{ $log->user?->name ?? 'Sistem' }}
                                        </span>
                                    </div>

                                    {{-- Old/New Values --}}
                                    @if($log->old_value || $log->new_value)
                                        <div class="fs-7 text-gray-600 mt-1">
                                            @if($log->old_value && $log->new_value)
                                                <span class="text-danger text-decoration-line-through">{{ $log->old_value }}</span>
                                                <i class="ki-outline ki-arrow-right fs-7 mx-1 text-gray-400"></i>
                                                <span class="text-success">{{ $log->new_value }}</span>
                                            @elseif($log->new_value)
                                                <span class="text-success">{{ $log->new_value }}</span>
                                            @elseif($log->old_value)
                                                <span class="text-danger">{{ $log->old_value }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Expandable Metadata --}}
                                    @if($log->metadata && count($log->metadata) > 0)
                                        <div class="mt-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light-primary py-1 px-3 fs-8"
                                                x-on:click="expanded = !expanded"
                                            >
                                                <i class="ki-outline fs-7 me-1" :class="expanded ? 'ki-minus' : 'ki-plus'"></i>
                                                <span x-text="expanded ? 'Sembunyikan Detail' : 'Lihat Detail'"></span>
                                            </button>

                                            <div x-show="expanded" x-collapse class="mt-3">
                                                <div class="bg-light-primary rounded p-3">
                                                    <table class="table table-sm table-borderless mb-0">
                                                        <tbody>
                                                            @foreach($log->metadata as $key => $value)
                                                                <tr>
                                                                    <td class="text-gray-600 fw-semibold fs-8 py-1" style="width: 180px;">
                                                                        {{ str_replace('_', ' ', ucfirst($key)) }}
                                                                    </td>
                                                                    <td class="text-gray-800 fs-8 py-1">
                                                                        @if(is_array($value))
                                                                            {{ json_encode($value) }}
                                                                        @else
                                                                            {{ $value }}
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-5">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

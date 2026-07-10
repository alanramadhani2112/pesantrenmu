@if($activeTab === 'audit_trail')
@php
    $latestLog = $auditLogs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $auditLogs->getCollection()->first() : null;
    $activeFilterCount = collect([
        $auditFilterActionType,
        $auditFilterUserId,
        $auditFilterDateFrom,
        $auditFilterDateTo,
    ])->filter(fn ($value) => filled($value))->count();
@endphp

<div data-ui-audit-timeline="metronic" class="spm-audit-timeline">
    {{-- Summary Grid --}}
    <div class="spm-audit-summary-grid mb-6">
        <div class="spm-audit-summary-item">
            <span class="spm-audit-summary-label">Total Riwayat</span>
            <span class="spm-audit-summary-value">{{ $auditLogs instanceof \Illuminate\Pagination\LengthAwarePaginator ? $auditLogs->total() : 0 }}</span>
        </div>
        <div class="spm-audit-summary-item">
            <span class="spm-audit-summary-label">Aktor Terlibat</span>
            <span class="spm-audit-summary-value">{{ $auditActors->count() }}</span>
        </div>
        <div class="spm-audit-summary-item">
            <span class="spm-audit-summary-label">Filter Aktif</span>
            <span class="spm-audit-summary-value">{{ $activeFilterCount }}</span>
        </div>
        <div class="spm-audit-summary-item spm-audit-summary-item--wide">
            <span class="spm-audit-summary-label">Aktivitas Terakhir</span>
            <span class="spm-audit-summary-value spm-audit-summary-value--text">
                {{ $latestLog ? \App\Models\AkreditasiAuditLog::getActionTypeLabel($latestLog->action_type) : 'Belum ada aktivitas' }}
            </span>
        </div>
    </div>

    {{-- Filter Controls (GET form) --}}
    <x-ui.section-card title="Filter Riwayat" subtitle="Saring berdasarkan tipe aksi, aktor, atau rentang tanggal." class="spm-audit-filter-card">
        <div class="p-5">
            <form method="GET" action="{{ request()->url() }}">
                <input type="hidden" name="tab" value="audit_trail">
                <div class="row g-4 align-items-end">
                    <div class="col-md-3">
                        <x-ui.form-field label="Tipe Aksi">
                            <select name="audit_action_type" class="form-select form-select-solid" data-ui-select="metronic">
                                <option value="">Semua Tipe</option>
                                @foreach($auditActionTypes as $type)
                                    <option value="{{ $type }}" @selected($auditFilterActionType === $type)>
                                        {{ \App\Models\AkreditasiAuditLog::getActionTypeLabel($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </x-ui.form-field>
                    </div>

                    <div class="col-md-3">
                        <x-ui.form-field label="Aktor">
                            <select name="audit_user_id" class="form-select form-select-solid" data-ui-select="metronic">
                                <option value="">Semua Pengguna</option>
                                @foreach($auditActors as $actor)
                                    <option value="{{ $actor->id }}" @selected($auditFilterUserId == $actor->id)>
                                        {{ $actor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </x-ui.form-field>
                    </div>

                    <div class="col-md-2">
                        <x-ui.form-field label="Dari Tanggal">
                            <input type="date" name="audit_date_from" value="{{ $auditFilterDateFrom }}" class="form-control form-control-solid">
                        </x-ui.form-field>
                    </div>

                    <div class="col-md-2">
                        <x-ui.form-field label="Sampai Tanggal">
                            <input type="date" name="audit_date_to" value="{{ $auditFilterDateTo }}" class="form-control form-control-solid">
                        </x-ui.form-field>
                    </div>

                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <x-ui.button type="submit" variant="primary" class="flex-grow-1">
                                <x-ui.icon name="filter" class="fs-5 me-1" />
                                Filter
                            </x-ui.button>
                            <a href="{{ request()->url() }}?tab=audit_trail" class="btn btn-light-danger btn-icon d-inline-flex align-items-center">
                                <x-ui.icon name="cross" class="fs-5" />
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </x-ui.section-card>

    {{-- Timeline List --}}
    <x-ui.section-card title="Riwayat Audit" subtitle="Kronologi perubahan status dan tindakan pada akreditasi ini.">
        <div class="p-5">
            @if(!($auditLogs instanceof \Illuminate\Pagination\LengthAwarePaginator) || $auditLogs->isEmpty())
                <x-ui.empty-state
                    title="Belum ada riwayat audit"
                    description="Belum ada perubahan yang tercatat untuk akreditasi ini."
                    icon="document"
                />
            @else
                @php
                    $actionIcons = [
                        'status_changed'    => 'arrow-right',
                        'asesor_assigned'   => 'profile-circle',
                        'asesor_reassigned' => 'refresh',
                        'approved'          => 'verify',
                        'rejected'          => 'cross',
                        'finalized'         => 'verify',
                        'banding_submitted' => 'information',
                        'document_uploaded' => 'document',
                        'document_replaced' => 'refresh',
                        'nv_changed'        => 'edit',
                        'deleted'           => 'trash',
                    ];

                    $actionColors = [
                        'status_changed'    => 'primary',
                        'asesor_assigned'   => 'info',
                        'asesor_reassigned' => 'warning',
                        'approved'          => 'success',
                        'rejected'          => 'danger',
                        'finalized'         => 'primary',
                        'banding_submitted' => 'warning',
                        'document_uploaded' => 'success',
                        'document_replaced' => 'info',
                        'nv_changed'        => 'warning',
                        'deleted'           => 'danger',
                    ];
                @endphp

                <x-ui.stepper
                    direction="column"
                    class="spm-audit-stepper"
                    data-ui-audit-stepper="metronic"
                    aria-label="Riwayat audit akreditasi"
                >
                    @foreach($auditLogs as $log)
                        @php
                            $color = $actionColors[$log->action_type] ?? 'secondary';
                            $icon = $actionIcons[$log->action_type] ?? 'information';
                            $stateClass = $loop->first ? 'current' : 'completed';
                            $isLast = $loop->last;
                        @endphp

                        <div
                            class="stepper-item {{ $stateClass }} spm-audit-stepper-item spm-audit-stepper-item--{{ $color }} {{ $isLast ? 'spm-audit-stepper-item--last' : '' }}"
                            data-kt-stepper-element="nav"
                            x-data="{ expanded: false }"
                        >
                            <div class="stepper-wrapper d-flex align-items-start">
                                <div class="stepper-icon spm-audit-stepper-icon w-40px h-40px">
                                    <i class="stepper-check">
                                        <x-ui.icon :name="$icon" class="fs-6" />
                                    </i>
                                </div>

                                <div class="stepper-label min-w-0 flex-grow-1">
                                    <div class="spm-audit-stepper-panel">
                                        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-lg-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="stepper-title mb-1">
                                                    {{ \App\Models\AkreditasiAuditLog::getActionTypeLabel($log->action_type) }}
                                                </h3>
                                                <div class="stepper-desc d-flex flex-wrap align-items-center gap-2">
                                                    <span>{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                                                    <span class="bullet-dot h-5px w-5px bg-gray-300"></span>
                                                    <span>{{ $log->user?->name ?? 'Sistem' }}</span>
                                                </div>
                                            </div>
                                            <x-ui.badge :variant="$color" class="spm-audit-stepper-badge">
                                                {{ $loop->first ? 'Terbaru' : \App\Models\AkreditasiAuditLog::getActionTypeLabel($log->action_type) }}
                                            </x-ui.badge>
                                        </div>

                                        @if($log->old_value || $log->new_value)
                                            <div class="spm-audit-stepper-change">
                                                @if($log->old_value && $log->new_value)
                                                    <span class="badge badge-light-danger">{{ $log->old_value }}</span>
                                                    <x-ui.icon name="arrow-right" class="fs-7 text-gray-500 mx-2" />
                                                    <span class="badge badge-light-success">{{ $log->new_value }}</span>
                                                @elseif($log->new_value)
                                                    <span class="badge badge-light-success">{{ $log->new_value }}</span>
                                                @elseif($log->old_value)
                                                    <span class="badge badge-light-danger">{{ $log->old_value }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if($log->metadata && count($log->metadata) > 0)
                                            <div class="spm-audit-stepper-meta">
                                                <x-ui.button
                                                    type="button"
                                                    variant="light-primary"
                                                    size="sm"
                                                    class="spm-audit-stepper-toggle"
                                                    x-on:click="expanded = !expanded"
                                                    x-bind:aria-expanded="expanded.toString()"
                                                >
                                                    <x-ui.icon name="plus" class="fs-7 me-1" x-show="!expanded" />
                                                    <x-ui.icon name="minus" class="fs-7 me-1" x-show="expanded" x-cloak />
                                                    <span x-text="expanded ? 'Sembunyikan Detail' : 'Lihat Detail'"></span>
                                                </x-ui.button>

                                                <div x-show="expanded" x-transition.opacity.duration.120ms class="spm-audit-stepper-detail" x-cloak>
                                                    <x-ui.simple-table dense class="mb-0">
                                                        <tbody>
                                                            @foreach($log->metadata as $key => $value)
                                                                <tr>
                                                                    <td class="text-gray-600 fw-semibold fs-8 py-2 pe-4" style="width: 180px;">
                                                                        {{ str($key)->replace('_', ' ')->headline() }}
                                                                    </td>
                                                                    <td class="text-gray-800 fs-8 py-2">
                                                                        @if(is_array($value))
                                                                            {{ json_encode($value) }}
                                                                        @else
                                                                            {{ $value }}
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </x-ui.simple-table>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </x-ui.stepper>

                {{-- Pagination preserves all query params (tab + filters) --}}
                <div class="mt-5">
                    <x-ui.pagination :paginator="$auditLogs->appends(request()->query())" />
                </div>
            @endif
        </div>
    </x-ui.section-card>
</div>
@endif

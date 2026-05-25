<div>
    {{-- Filter Controls --}}
    <x-ui.section-card title="Filter Riwayat" subtitle="Saring berdasarkan tipe aksi, aktor, atau rentang tanggal.">
        <div class="p-5">
            <div class="row g-4 align-items-end">
                <div class="col-md-3">
                    <x-ui.form-field label="Tipe Aksi">
                        <x-ui.select model="filterActionType" modifier="live">
                            <option value="">Semua Tipe</option>
                            @foreach($actionTypes as $type)
                                <option value="{{ $type }}">
                                    {{ \App\Models\AkreditasiAuditLog::getActionTypeLabel($type) }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.form-field>
                </div>

                <div class="col-md-3">
                    <x-ui.form-field label="Aktor">
                        <x-ui.select model="filterUserId" modifier="live">
                            <option value="">Semua Pengguna</option>
                            @foreach($actors as $actor)
                                <option value="{{ $actor->id }}">{{ $actor->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.form-field>
                </div>

                <div class="col-md-2">
                    <x-ui.form-field label="Dari Tanggal">
                        <x-ui.input model="filterDateFrom" modifier="live" type="date" />
                    </x-ui.form-field>
                </div>

                <div class="col-md-2">
                    <x-ui.form-field label="Sampai Tanggal">
                        <x-ui.input model="filterDateTo" modifier="live" type="date" />
                    </x-ui.form-field>
                </div>

                <div class="col-md-2">
                    <x-ui.button wire:click="resetFilters" variant="light-danger" class="w-100 text-nowrap justify-content-center">
                        <x-ui.icon name="cross" class="fs-5 me-1" />
                        Atur Ulang
                    </x-ui.button>
                </div>
            </div>
        </div>
    </x-ui.section-card>

    {{-- Timeline List --}}
    <x-ui.section-card title="Riwayat Audit" subtitle="Kronologi perubahan status dan tindakan pada akreditasi ini.">
        <div class="p-5">
            @if($logs->isEmpty())
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
                        'banding_submitted'  => 'information',
                        'document_uploaded'  => 'document',
                        'document_replaced'  => 'refresh',
                        'nv_changed'         => 'edit',
                        'deleted'           => 'trash',
                    ];

                    $actionColors = [
                        'status_changed'    => 'primary',
                        'asesor_assigned'   => 'info',
                        'asesor_reassigned' => 'warning',
                        'approved'          => 'success',
                        'rejected'          => 'danger',
                        'finalized'         => 'primary',
                        'banding_submitted'  => 'warning',
                        'document_uploaded'  => 'success',
                        'document_replaced'  => 'info',
                        'nv_changed'         => 'warning',
                        'deleted'           => 'danger',
                    ];
                    $total = $logs->count();
                    $idx = 0;
                @endphp

                <x-ui.stepper
                    direction="column"
                    class="spm-audit-stepper"
                    data-ui-audit-stepper="metronic"
                    aria-label="Riwayat audit akreditasi"
                >
                    @foreach($logs as $log)
                        @php
                            $color   = $actionColors[$log->action_type] ?? 'secondary';
                            $icon    = $actionIcons[$log->action_type] ?? 'information';
                            $isLast  = ($idx === $total - 1);
                            $idx++;
                        @endphp

                        <div
                            class="stepper-item completed spm-audit-stepper-item spm-audit-stepper-item--{{ $color }} {{ $isLast ? 'spm-audit-stepper-item--last' : '' }}"
                            data-kt-stepper-element="nav"
                            x-data="{ expanded: false }"
                        >
                            <div class="stepper-wrapper d-flex align-items-start">
                                <div class="stepper-icon w-40px h-40px">
                                    <i class="stepper-check">
                                        <x-ui.icon :name="$icon" class="fs-6" />
                                    </i>
                                    <span class="stepper-number">{{ $idx }}</span>
                                </div>

                                <div class="stepper-label min-w-0 flex-grow-1">
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
                                            {{ \App\Models\AkreditasiAuditLog::getActionTypeLabel($log->action_type) }}
                                        </x-ui.badge>
                                    </div>

                                    @if($log->old_value || $log->new_value)
                                        <div class="spm-audit-stepper-change">
                                            @if($log->old_value && $log->new_value)
                                                <span class="badge badge-light-danger text-decoration-line-through">{{ $log->old_value }}</span>
                                                <x-ui.icon name="arrow-right" class="fs-8 text-gray-400" />
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

                                            <div x-show="expanded" x-collapse class="spm-audit-stepper-detail" x-cloak>
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

                            @unless($isLast)
                                <div class="stepper-line h-40px"></div>
                            @endunless
                        </div>
                    @endforeach
                </x-ui.stepper>

                <div class="mt-5">
                    {{ $logs->links('livewire.datatable-pagination') }}
                </div>
            @endif
        </div>
    </x-ui.section-card>
</div>

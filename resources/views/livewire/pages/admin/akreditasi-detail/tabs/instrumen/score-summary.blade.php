                    @if ($akreditasi->status == 1 || $akreditasi->status == 0)
                        <x-ui.section-card title="Ringkasan Data Penilaian" subtitle="Perhitungan skor komponen dan hasil akhir.">
                            <div class="p-6">
                                <x-ui.simple-table tableClass="spm-wide-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Komponen</th>
                                            <th class="text-center">Cmaks</th>
                                            <th class="text-center">CI</th>
                                            <th class="text-center">BK</th>
                                            <th class="text-center">Skor Komponen</th>
                                            <th class="text-center pe-4">Total Skor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->adminScoreSummaryViewData()['rows'] as $row)
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $row['name'] }}</td>
                                                <td class="text-center fw-semibold">{{ $row['cmaks'] }}</td>
                                                <td class="text-center fw-semibold text-primary">{{ $row['ci'] }}</td>
                                                <td class="text-center fw-semibold text-warning">{{ $row['bk'] }}</td>
                                                <td class="text-center fw-semibold">{{ $row['score'] }}</td>

                                                @if (! is_null($row['total_score']))
                                                    <td rowspan="{{ $row['total_rowspan'] }}" class="text-center pe-4 fw-semibold fs-4 text-success align-middle">
                                                        {{ $row['total_score'] }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </x-ui.simple-table>

                                <div class="row g-5 mt-2">
                                    <div class="col-md-6">
                                        <div class="spm-result-metric">
                                            <div class="spm-detail-label">Nilai Akreditasi</div>
                                            <div class="fs-2 fw-semibold text-primary">
                                                {{ $this->adminScoreSummaryViewData()['result']['nilai_akreditasi'] }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="spm-result-metric">
                                            <div class="spm-detail-label">Peringkat Akreditasi</div>
                                            <div class="fs-2 fw-semibold text-gray-900">
                                                {{ $this->adminScoreSummaryViewData()['result']['peringkat'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

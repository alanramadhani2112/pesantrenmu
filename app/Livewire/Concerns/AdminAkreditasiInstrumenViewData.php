<?php

namespace App\Livewire\Concerns;

use App\Services\ProgressTracker;
use App\Services\ScoreCalculationService;

trait AdminAkreditasiInstrumenViewData
{
    public function adminScoringProgressCards(): array
    {
        $cards = [
            ['progress' => $this->asesor1NaProgress, 'label' => 'Nilai Ketua'],
            ['progress' => $this->asesor1NkProgress, 'label' => 'Nilai Kelompok'],
            ['progress' => $this->asesor2NaProgress, 'label' => 'Nilai Anggota'],
        ];

        return array_values(array_filter(array_map(function (array $card): ?array {
            if (empty($card['progress'])) {
                return null;
            }

            $card['color'] = app(ProgressTracker::class)->getColorClass((float) ($card['progress']['percentage'] ?? 0));

            return $card;
        }, $cards)));
    }

    public function adminScoringBlockers(): array
    {
        return array_values(array_map(
            fn (array $card): string => 'Menunggu '.$card['label'],
            array_filter(
                $this->adminScoringProgressCards(),
                fn (array $card): bool => (float) ($card['progress']['percentage'] ?? 0) < 100.0
            )
        ));
    }

    public function adminScoreSummaryViewData(): array
    {
        $scoreService = app(ScoreCalculationService::class);
        $rows = [];
        $totalSkorIk = 0.0;
        $totalSkorIpr = 0.0;
        $ikRowCount = 0;
        $iprRowCount = 0;

        foreach ($this->komponens ?? [] as $komponen) {
            $isIpr = ! is_null($komponen->ipr);
            $butirs = $komponen->butirs ?? collect();
            $cmaks = count($butirs) * ScoreCalculationService::SCORE_MAX;
            $ci = 0;

            foreach ($butirs as $butir) {
                $ci += (int) ($this->adminNvs[$butir->id] ?? 0);
            }

            $bobot = $isIpr
                ? 97
                : (ScoreCalculationService::KOMPONEN_CONFIG[$komponen->nama]['bobot'] ?? 0);
            $factor = $isIpr ? 100 : $bobot;
            $score = $cmaks > 0 ? round(($ci / $cmaks) * $factor) : 0;

            if ($isIpr) {
                $totalSkorIpr += $score;
                $iprRowCount++;
            } else {
                $totalSkorIk += $score;
                $ikRowCount++;
            }

            $rows[] = [
                'name' => $komponen->nama,
                'cmaks' => $cmaks,
                'ci' => $ci,
                'bk' => $bobot,
                'score' => $score,
                'is_ipr' => $isIpr,
                'total_score' => null,
                'total_rowspan' => null,
            ];
        }

        $ikTotalAssigned = false;
        $iprTotalAssigned = false;

        foreach ($rows as &$row) {
            if (! $row['is_ipr'] && ! $ikTotalAssigned) {
                $row['total_score'] = $totalSkorIk;
                $row['total_rowspan'] = $ikRowCount;
                $ikTotalAssigned = true;
            }

            if ($row['is_ipr'] && ! $iprTotalAssigned) {
                $row['total_score'] = $totalSkorIpr;
                $row['total_rowspan'] = max(1, $iprRowCount);
                $iprTotalAssigned = true;
            }
        }

        unset($row);

        $nilaiAkhir = $scoreService->calculateNilaiAkhir($totalSkorIk, $totalSkorIpr);
        $peringkatCode = $scoreService->determinePeringkat($nilaiAkhir);

        return [
            'rows' => $rows,
            'result' => [
                'nilai_akreditasi' => $nilaiAkhir,
                'peringkat' => match ($peringkatCode) {
                    'A' => 'Unggul',
                    'B' => 'Baik',
                    default => 'Cukup',
                },
            ],
        ];
    }
}

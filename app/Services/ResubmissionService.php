<?php

namespace App\Services;

use App\Models\Akreditasi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ResubmissionService
{
    /**
     * Count resubmissions in the chain containing the given akreditasi.
     * Traverses parent links to root, counts records with non-null parent.
     * Includes soft-deleted records.
     *
     * @return int Number of resubmissions (excludes the original submission)
     */
    public function countChainResubmissions(int $parentId): int
    {
        // First, traverse up from parentId to find the chain root
        $chainMembers = $this->collectChainFromParent($parentId);

        if ($chainMembers === null) {
            // Circular reference detected
            return 0;
        }

        // Count records with non-null parent (resubmissions)
        return $chainMembers->filter(fn(Akreditasi $a) => $a->parent !== null)->count();
    }

    /**
     * Get the full chain from root to leaf for display purposes.
     * Returns ordered collection of Akreditasi (with trashed).
     *
     * @return Collection<Akreditasi>
     */
    public function getChainTimeline(int $akreditasiId): Collection
    {
        $akreditasi = Akreditasi::withTrashed()->find($akreditasiId);

        if (!$akreditasi) {
            return collect();
        }

        // Traverse up to root
        $current = $akreditasi;
        $visited = [$current->id];
        $depth = 0;

        while ($current->parent !== null && $depth < 50) {
            $parent = Akreditasi::withTrashed()->find($current->parent);
            if (!$parent || in_array($parent->id, $visited)) {
                break;
            }
            $visited[] = $parent->id;
            $current = $parent;
            $depth++;
        }

        $root = $current;

        // Now traverse down from root collecting all chain members
        $chain = collect();
        $this->collectDescendants($root, $chain, []);

        return $chain->sortBy('created_at')->values();
    }

    /**
     * Check whether a resubmission is allowed for the given parent.
     * Returns a status array with keys: allowed, error_code, error_data.
     *
     * Error codes: 'limit_reached', 'cooling_period', null (allowed)
     *
     * @return array{allowed: bool, error_code: ?string, error_data: array}
     */
    public function checkResubmissionEligibility(int $parentId): array
    {
        $limit = (int) config('akreditasi.resubmission_limit');
        $coolingDays = (int) config('akreditasi.cooling_period_days');

        // Check limit
        $count = $this->countChainResubmissions($parentId);

        if ($count >= $limit) {
            return [
                'allowed' => false,
                'error_code' => 'limit_reached',
                'error_data' => [
                    'count' => $count,
                    'limit' => $limit,
                ],
            ];
        }

        // Check cooling period
        if ($coolingDays > 0) {
            $parentAkreditasi = Akreditasi::withTrashed()->find($parentId);

            if ($parentAkreditasi && (int) $parentAkreditasi->status === 2) {
                $rejectionDate = Carbon::parse($parentAkreditasi->updated_at)->startOfDay();
                $coolingEndDate = $rejectionDate->copy()->addDays($coolingDays);
                $now = Carbon::now()->startOfDay();

                if ($now->lt($coolingEndDate)) {
                    $remainingDays = (int) $now->diffInDays($coolingEndDate);

                    return [
                        'allowed' => false,
                        'error_code' => 'cooling_period',
                        'error_data' => [
                            'cooling_end_date' => $coolingEndDate->format('Y-m-d'),
                            'remaining_days' => $remainingDays,
                        ],
                    ];
                }
            }
        }

        return [
            'allowed' => true,
            'error_code' => null,
            'error_data' => [],
        ];
    }

    /**
     * Get resubmission status info for UI display.
     * Returns: count, limit, cooling_remaining_days, cooling_end_date, can_resubmit
     *
     * @return array{count: int, limit: int, cooling_remaining_days: int, cooling_end_date: ?string, can_resubmit: bool}
     */
    public function getResubmissionStatus(int $parentId): array
    {
        $limit = (int) config('akreditasi.resubmission_limit');
        $coolingDays = (int) config('akreditasi.cooling_period_days');

        $count = $this->countChainResubmissions($parentId);

        $coolingRemainingDays = 0;
        $coolingEndDate = null;

        $parentAkreditasi = Akreditasi::withTrashed()->find($parentId);

        if ($parentAkreditasi && (int) $parentAkreditasi->status === 2 && $coolingDays > 0) {
            $rejectionDate = Carbon::parse($parentAkreditasi->updated_at)->startOfDay();
            $coolingEnd = $rejectionDate->copy()->addDays($coolingDays);
            $now = Carbon::now()->startOfDay();

            if ($now->lt($coolingEnd)) {
                $coolingRemainingDays = (int) $now->diffInDays($coolingEnd);
                $coolingEndDate = $coolingEnd->format('Y-m-d');
            }
        }

        $canResubmit = $count < $limit && $coolingRemainingDays === 0;

        // Special case: limit = 0 means no resubmissions allowed
        if ($limit === 0) {
            $canResubmit = false;
        }

        return [
            'count' => $count,
            'limit' => $limit,
            'cooling_remaining_days' => $coolingRemainingDays,
            'cooling_end_date' => $coolingEndDate,
            'can_resubmit' => $canResubmit,
        ];
    }

    /**
     * Generate a formatted Indonesian-language error message based on the error code
     * and error data returned by checkResubmissionEligibility().
     *
     * @param string $errorCode The error code ('limit_reached' or 'cooling_period')
     * @param array $errorData The error data with interpolation values
     * @return string The formatted error message in Indonesian
     */
    public function getErrorMessage(string $errorCode, array $errorData): string
    {
        return match ($errorCode) {
            'limit_reached' => sprintf(
                'Batas pengajuan ulang telah tercapai (%d/%d). Anda tidak dapat mengajukan ulang untuk akreditasi ini.',
                $errorData['count'],
                $errorData['limit']
            ),
            'cooling_period' => sprintf(
                'Masa tunggu pengajuan ulang belum berakhir. Anda dapat mengajukan ulang pada tanggal %s (%d hari lagi).',
                $errorData['cooling_end_date'],
                $errorData['remaining_days']
            ),
            default => '',
        };
    }

    /**
     * Collect all chain members starting from the given parent ID, traversing up to root.
     * Returns null if circular reference detected.
     *
     * @return Collection|null
     */
    private function collectChainFromParent(int $parentId): ?Collection
    {
        // Traverse up to find root
        $current = Akreditasi::withTrashed()->find($parentId);

        if (!$current) {
            return collect();
        }

        $visited = [$current->id];
        $depth = 0;

        while ($current->parent !== null && $depth < 50) {
            $parent = Akreditasi::withTrashed()->find($current->parent);
            if (!$parent) {
                break;
            }
            if (in_array($parent->id, $visited)) {
                // Circular reference detected
                Log::error('Circular reference detected in akreditasi chain', [
                    'akreditasi_id' => $current->id,
                    'parent_id' => $current->parent,
                    'visited' => $visited,
                ]);
                return null;
            }
            $visited[] = $parent->id;
            $current = $parent;
            $depth++;
        }

        if ($depth >= 50) {
            Log::error('Max depth reached in akreditasi chain traversal', [
                'starting_id' => $parentId,
                'depth' => $depth,
            ]);
            return null;
        }

        $root = $current;

        // Now collect all descendants from root
        $chain = collect();
        $this->collectDescendants($root, $chain, []);

        return $chain;
    }

    /**
     * Recursively collect all descendants of a given akreditasi.
     */
    private function collectDescendants(Akreditasi $node, Collection &$chain, array $visited): void
    {
        if (in_array($node->id, $visited)) {
            return;
        }

        $chain->push($node);
        $visited[] = $node->id;

        $children = Akreditasi::withTrashed()->where('parent', $node->id)->get();
        foreach ($children as $child) {
            $this->collectDescendants($child, $chain, $visited);
        }
    }
}

<?php

namespace App\Services;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResubmissionService
{
    // =========================================================================
    // New spec-compliant methods (Req 13.4-13.10)
    // =========================================================================

    /**
     * Check whether a resubmission is allowed for the given akreditasi.
     *
     * Returns:
     *   {
     *     can: bool,
     *     reason: ?string,
     *     days_remaining: ?int
     *   }
     *
     * Checks:
     *  - 30-day cooling period from rejection timestamp (status -1 updated_at)
     *  - Chain count (max 3 resubmissions)
     *
     * Validates Requirements 13.4, 13.5, 13.6, 13.9, 13.10
     */
    public function canResubmit(int $akreditasiId): array
    {
        $akreditasi = Akreditasi::withTrashed()->find($akreditasiId);

        if (!$akreditasi) {
            return [
                'can' => false,
                'reason' => 'Akreditasi tidak ditemukan.',
                'days_remaining' => null,
            ];
        }

        // Check chain count (max 3 resubmissions)
        $chainCount = $this->getChainCount($akreditasiId);
        if ($chainCount >= 3) {
            return [
                'can' => false,
                'reason' => 'Batas maksimum pengajuan ulang (3 kali) telah tercapai.',
                'days_remaining' => null,
            ];
        }

        // Check 30-day cooling period from rejection timestamp
        $rejectionTimestamp = Carbon::parse($akreditasi->updated_at);
        $coolingEnd = $rejectionTimestamp->copy()->addDays(30);
        $now = Carbon::now();

        if ($now->lt($coolingEnd)) {
            $daysRemaining = (int) $now->diffInDays($coolingEnd, false);
            // diffInDays with false gives negative when $coolingEnd is in the future
            // We want positive remaining days
            $daysRemaining = (int) ceil($coolingEnd->diffInDays($now, false) * -1);
            if ($daysRemaining <= 0) {
                $daysRemaining = (int) ceil($now->floatDiffInDays($coolingEnd));
            }

            return [
                'can' => false,
                'reason' => "Masa tunggu pengajuan ulang belum berakhir. Sisa {$daysRemaining} hari.",
                'days_remaining' => $daysRemaining,
            ];
        }

        return [
            'can' => true,
            'reason' => null,
            'days_remaining' => null,
        ];
    }

    /**
     * Count the total number of resubmissions in the chain containing the
     * given akreditasi, by following parent_id links to the root.
     *
     * The count is the number of records in the chain that have a non-null
     * parent field (i.e., all records except the root).
     *
     * Validates Requirements 13.5
     */
    public function getChainCount(int $akreditasiId): int
    {
        $chain = $this->collectChainFromAny($akreditasiId);

        if ($chain === null) {
            return 0;
        }

        // Count records with non-null parent (resubmissions)
        return $chain->filter(fn (Akreditasi $a) => $a->parent !== null)->count();
    }

    /**
     * Create a new resubmission akreditasi.
     *
     * - Creates new Akreditasi at status 6 (Pengajuan) with parent_id set to
     *   the root (first record in the chain with no parent_id of its own)
     * - Unlocks pesantren data sections (Profil, IPM, SDM, EDPM)
     *
     * Validates Requirements 13.7, 13.8
     */
    public function createResubmission(int $akreditasiId, int $pesantrenId): Akreditasi
    {
        $akreditasi = Akreditasi::withTrashed()->find($akreditasiId);

        if (!$akreditasi) {
            throw new \InvalidArgumentException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        // Find the root akreditasi (first record in chain with no parent)
        $root = $this->findRoot($akreditasiId);

        return DB::transaction(function () use ($akreditasi, $pesantrenId, $root) {
            // Create new Akreditasi at status 6 with parent_id set to root
            $newAkreditasi = Akreditasi::create([
                'user_id' => $pesantrenId,
                'parent' => $root->id,
                'status' => AkreditasiStateMachine::STATUS_PENGAJUAN,
            ]);

            // Unlock pesantren data sections (Profil, IPM, SDM, EDPM)
            // This is done by unlocking the Pesantren record associated with the user
            $pesantren = Pesantren::where('user_id', $pesantrenId)->first();
            if ($pesantren) {
                $pesantren->update(['is_locked' => false]);
            }

            return $newAkreditasi;
        });
    }

    // =========================================================================
    // Legacy methods (preserved for backward compatibility)
    // =========================================================================

    /**
     * Count resubmissions in the chain containing the given akreditasi.
     * Traverses parent links to root, counts records with non-null parent.
     * Includes soft-deleted records.
     *
     * @return int Number of resubmissions (excludes the original submission)
     */
    public function countChainResubmissions(int $parentId): int
    {
        $chainMembers = $this->collectChainFromParent($parentId);

        if ($chainMembers === null) {
            return 0;
        }

        return $chainMembers->filter(fn (Akreditasi $a) => $a->parent !== null)->count();
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
     * Generate a formatted Indonesian-language error message.
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

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Find the root akreditasi in the chain (the one with no parent).
     */
    private function findRoot(int $akreditasiId): Akreditasi
    {
        $current = Akreditasi::withTrashed()->find($akreditasiId);
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

        return $current;
    }

    /**
     * Collect all chain members starting from any akreditasi in the chain.
     * Traverses up to root, then collects all descendants.
     * Returns null if circular reference detected.
     *
     * @return Collection|null
     */
    private function collectChainFromAny(int $akreditasiId): ?Collection
    {
        $current = Akreditasi::withTrashed()->find($akreditasiId);

        if (!$current) {
            return collect();
        }

        $visited = [$current->id];
        $depth = 0;

        // Traverse up to root
        while ($current->parent !== null && $depth < 50) {
            $parent = Akreditasi::withTrashed()->find($current->parent);
            if (!$parent) {
                break;
            }
            if (in_array($parent->id, $visited)) {
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
                'starting_id' => $akreditasiId,
                'depth' => $depth,
            ]);
            return null;
        }

        $root = $current;

        $chain = collect();
        $this->collectDescendants($root, $chain, []);

        return $chain;
    }

    /**
     * Collect all chain members starting from the given parent ID, traversing up to root.
     * Returns null if circular reference detected.
     *
     * @return Collection|null
     */
    private function collectChainFromParent(int $parentId): ?Collection
    {
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

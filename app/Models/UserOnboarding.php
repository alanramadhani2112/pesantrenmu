<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOnboarding extends Model
{
    protected $fillable = [
        'user_id',
        'completed_at',
        'skipped_at',
        'visited_steps',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
        'visited_steps' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null || $this->skipped_at !== null;
    }

    public function hasVisitedStep(string $stepKey): bool
    {
        return in_array($stepKey, $this->visited_steps ?? []);
    }

    public function markStepVisited(string $stepKey): void
    {
        $visited = $this->visited_steps ?? [];
        if (! in_array($stepKey, $visited)) {
            $visited[] = $stepKey;
            $this->update(['visited_steps' => $visited]);
        }
    }
}

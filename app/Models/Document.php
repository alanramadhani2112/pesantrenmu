<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Master document template uploaded by admin (and optionally by other roles
 * once `pesantren_can_upload` / `asesor_can_upload` are enabled on the
 * category).
 *
 * @property int $id
 * @property string $title
 * @property string|null $type legacy ENUM (iapm|kartu_kendali|visitasi). Kept for backward compat.
 * @property int|null $category_id
 * @property int|null $uploaded_by_role
 * @property int|null $uploaded_by_user_id
 * @property string|null $description
 * @property string $file_path
 * @property int $status 0|1
 * @property bool $is_pesantren legacy flag, kept for backward compat
 * @property bool $is_asesor legacy flag, kept for backward compat
 */
class Document extends Model
{
    /**
     * Explicitly listed fillable fields.
     *
     * Replaces the previous `$guarded = []` which was a mass-assignment
     * security smell. Add new fields here intentionally.
     */
    protected $fillable = [
        'title',
        'type',
        'category_id',
        'uploaded_by_role',
        'uploaded_by_user_id',
        'description',
        'file_path',
        'status',
        'is_pesantren',
        'is_asesor',
    ];

    protected $casts = [
        'status' => 'integer',
        'is_pesantren' => 'boolean',
        'is_asesor' => 'boolean',
        'uploaded_by_role' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Active documents = status=1 AND a still-active category.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 1)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    /**
     * Filter documents by what a role is allowed to see.
     * Reads visibility from the category, never from the legacy boolean columns.
     */
    public function scopeVisibleToRole(Builder $query, ?string $role): Builder
    {
        return match ($role) {
            'pesantren' => $query->whereHas('category', fn (Builder $q) => $q->visibleToPesantren()),
            'asesor' => $query->whereHas('category', fn (Builder $q) => $q->visibleToAsesor()),
            'admin' => $query, // admin sees everything
            default => $query->where('id', 0),
        };
    }

    /**
     * Filter by the human-readable category slug ("iapm", "kartu_kendali", ...).
     */
    public function scopeCategorySlug(Builder $query, string $slug): Builder
    {
        return $query->whereHas('category', fn (Builder $q) => $q->where('slug', $slug));
    }
}

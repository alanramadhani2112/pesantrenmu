<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master taxonomy of document categories.
 *
 * Visibility is the single source of truth for "who can see templates"
 * under this category. The ENUM is mutually exclusive at DB level so a
 * sensitive category cannot be accidentally exposed to multiple roles.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $icon
 * @property string $visibility one of public|pesantren_secret|asesor_secret
 * @property bool $pesantren_can_upload
 * @property bool $asesor_can_upload
 * @property bool $is_active
 * @property int $sort_order
 */
class DocumentCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PESANTREN_SECRET = 'pesantren_secret';

    public const VISIBILITY_ASESOR_SECRET = 'asesor_secret';

    public const VISIBILITIES = [
        self::VISIBILITY_PUBLIC,
        self::VISIBILITY_PESANTREN_SECRET,
        self::VISIBILITY_ASESOR_SECRET,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'visibility',
        'pesantren_can_upload',
        'asesor_can_upload',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'pesantren_can_upload' => 'boolean',
        'asesor_can_upload' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Categories a Pesantren user is allowed to see.
     * Pesantren can see public + pesantren_secret only.
     */
    public function scopeVisibleToPesantren(Builder $query): Builder
    {
        return $query->whereIn('visibility', [
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_PESANTREN_SECRET,
        ]);
    }

    /**
     * Categories an Asesor user is allowed to see.
     * Asesor can see public + asesor_secret only.
     */
    public function scopeVisibleToAsesor(Builder $query): Builder
    {
        return $query->whereIn('visibility', [
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_ASESOR_SECRET,
        ]);
    }

    /**
     * Convenience filter for the resolved role string used by DocumentService.
     */
    public function scopeVisibleToRole(Builder $query, ?string $role): Builder
    {
        return match ($role) {
            'pesantren' => $query->visibleToPesantren(),
            'asesor' => $query->visibleToAsesor(),
            'admin', null => $query, // admin sees everything; null = guest, no extra filter here
            default => $query->where('id', 0), // unknown role -> empty result
        };
    }

    /**
     * Returns a human-friendly label for the visibility value.
     */
    public function getVisibilityLabelAttribute(): string
    {
        return match ($this->visibility) {
            self::VISIBILITY_PUBLIC => 'Publik (Semua Role)',
            self::VISIBILITY_PESANTREN_SECRET => 'Rahasia Pesantren',
            self::VISIBILITY_ASESOR_SECRET => 'Rahasia Asesor',
            default => 'Tidak diketahui',
        };
    }
}

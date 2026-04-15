<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
#[Fillable(['key','locale','value','group','is_active'])]
class Translation extends Model
{
    use SoftDeletes;
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeByGroup($query, ?string $group)
    {
        if ($group) {
            return $query->where('group', $group);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $searchTerm)
    {
        if ($searchTerm) {
            return $query->where(function ($q) use ($searchTerm) {
                $q->where('key', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('value', 'LIKE', "%{$searchTerm}%");
            });
        }
        return $query;
    }
}

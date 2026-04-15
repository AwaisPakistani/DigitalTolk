<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\{Tag, Translation};

class TranslationRepository
{
    public function create(array $data): Translation
    {
        $translation = Translation::create([
            'key' => $data['key'],
            'locale' => $data['locale'],
            'value' => $data['value'],
            'group' => $data['group'] ?? null,
            'is_active' => $data['is_active'] ?? true
        ]);

        if (isset($data['tags'])) {
            $this->syncTags($translation, $data['tags']);
        }

        return $translation->load('tags');
    }

    public function update(Translation $translation, array $data): Translation
    {
        $translation->update([
            'value' => $data['value'] ?? $translation->value,
            'group' => $data['group'] ?? $translation->group,
            'is_active' => $data['is_active'] ?? $translation->is_active
        ]);

        if (isset($data['tags'])) {
            $this->syncTags($translation, $data['tags']);
        }

        return $translation->load('tags');
    }

    public function findById(int $id): ?Translation
    {
        return Translation::with('tags')->find($id);
    }

    public function findByKeyAndLocale(string $key, string $locale): ?Translation
    {
        return Translation::with('tags')
            ->where('key', $key)
            ->where('locale', $locale)
            ->first();
    }

    public function search(array $filters): LengthAwarePaginator
    {
        $query = Translation::with('tags');

        if (!empty($filters['locale'])) {
            $query->byLocale($filters['locale']);
        }

        if (!empty($filters['group'])) {
            $query->byGroup($filters['group']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : explode(',', $filters['tags']);
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('slug', $tags);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('locale')
            ->orderBy('key')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getAllTranslationsForExport(?string $locale = null, ?array $tags = null): Collection
    {
        $query = Translation::with('tags')->active();

        if ($locale) {
            $query->byLocale($locale);
        }

        if ($tags) {
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('slug', $tags);
            });
        }

        return $query->get();
    }

    private function syncTags(Translation $translation, array $tagNames): void
    {
        $tagIds = [];
        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate(
                ['slug' => \Str::slug($tagName)],
                ['name' => $tagName]
            );
            $tagIds[] = $tag->id;
        }
        $translation->tags()->sync($tagIds);
    }

    public function delete(Translation $translation): bool
    {
        return $translation->delete();
    }
}

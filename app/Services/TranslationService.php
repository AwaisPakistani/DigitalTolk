<?php

namespace App\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\{Cache, Log};
use App\Repositories\TranslationRepository;

class TranslationService
{
    protected TranslationRepository $repository;

    public function __construct(TranslationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createTranslation(array $data): array
    {
        try {
            // Validate unique key+locale combination
            $existing = $this->repository->findByKeyAndLocale($data['key'], $data['locale']);
            if ($existing) {
                throw new \Exception("Translation with key '{$data['key']}' for locale '{$data['locale']}' already exists.");
            }

            $translation = $this->repository->create($data);

            // Clear cache for this locale
            $this->clearCache($data['locale']);

            return [
                'success' => true,
                'data' => $translation->toArray(),
                'message' => 'Translation created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create translation: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updateTranslation(int $id, array $data): array
    {
        try {
            $translation = $this->repository->findById($id);
            if (!$translation) {
                throw new ModelNotFoundException("Translation not found with ID: {$id}");
            }

            $updatedTranslation = $this->repository->update($translation, $data);

            // Clear cache for this locale
            $this->clearCache($translation->locale);

            return [
                'success' => true,
                'data' => $updatedTranslation->toArray(),
                'message' => 'Translation updated successfully'
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update translation: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while updating the translation'
            ];
        }
    }

    public function getTranslation(int $id): array
    {
        try {
            $translation = $this->repository->findById($id);
            if (!$translation) {
                throw new ModelNotFoundException("Translation not found with ID: {$id}");
            }

            return [
                'success' => true,
                'data' => $translation->toArray()
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function searchTranslations(array $filters): array
    {
        try {
            $translations = $this->repository->search($filters);

            return [
                'success' => true,
                'data' => $translations->items(),
                'meta' => [
                    'current_page' => $translations->currentPage(),
                    'last_page' => $translations->lastPage(),
                    'per_page' => $translations->perPage(),
                    'total' => $translations->total()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to search translations: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while searching translations'
            ];
        }
    }

    public function exportTranslations(?string $locale = null, ?array $tags = null): array
    {
        try {
            $cacheKey = $this->generateCacheKey($locale, $tags);

            // Return cached version if available (optional, but recommended)
            // For "always return updated" requirement, we can bypass cache or use short TTL
            // Using cache with 5 minutes TTL for performance while still being relatively fresh
            $translations = Cache::remember($cacheKey, 300, function () use ($locale, $tags) {
                $collection = $this->repository->getAllTranslationsForExport($locale, $tags);

                // Format for frontend (Vue.js friendly format)
                $formatted = [];
                foreach ($collection as $translation) {
                    if (!isset($formatted[$translation->locale])) {
                        $formatted[$translation->locale] = [];
                    }
                    $formatted[$translation->locale][$translation->key] = $translation->value;
                }

                return $formatted;
            });

            return [
                'success' => true,
                'data' => $translations,
                'exported_at' => now()->toIso8601String()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to export translations: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while exporting translations'
            ];
        }
    }

    public function deleteTranslation(int $id): array
    {
        try {
            $translation = $this->repository->findById($id);
            if (!$translation) {
                throw new ModelNotFoundException("Translation not found with ID: {$id}");
            }

            $locale = $translation->locale;
            $this->repository->delete($translation);

            // Clear cache for this locale
            $this->clearCache($locale);

            return [
                'success' => true,
                'message' => 'Translation deleted successfully'
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete translation: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while deleting the translation'
            ];
        }
    }

    private function clearCache(?string $locale = null): void
    {
        if ($locale) {
            Cache::forget($this->generateCacheKey($locale));
            Cache::forget($this->generateCacheKey($locale, null, true));
        } else {
            Cache::flush(); // Be careful with this in production
        }
    }

    private function generateCacheKey(?string $locale = null, ?array $tags = null, $wildcard = false): string
    {
        $parts = ['translations'];

        if ($locale) {
            $parts[] = $locale;
        }

        if ($tags) {
            sort($tags);
            $parts[] = implode('_', $tags);
        }

        if ($wildcard) {
            $parts[] = 'all';
        }

        return implode(':', $parts);
    }
}

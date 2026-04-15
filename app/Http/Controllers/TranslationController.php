<?php

namespace App\Http\Controllers;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Requests\TranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Services\TranslationService;
class TranslationController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Create a new translation
     */
    public function store(TranslationRequest $request): JsonResponse
    {
        $result = $this->translationService->createTranslation($request->validated());

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 201);
    }

    /**
     * Update an existing translation
     */
    public function update(TranslationRequest $request, int $id): JsonResponse
    {
        $result = $this->translationService->updateTranslation($id, $request->validated());

        if (!$result['success']) {
            $statusCode = str_contains($result['message'], 'not found') ? 404 : 400;
            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }

    /**
     * Get a specific translation by ID
     */
    public function show(int $id): JsonResponse
    {
        // dd($id);
        $result = $this->translationService->getTranslation($id);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    /**
     * Search translations with filters
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->only(['locale', 'group', 'search', 'tags', 'is_active', 'per_page']);

        $result = $this->translationService->searchTranslations($filters);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Export translations for frontend (Vue.js)
     */
    public function export(Request $request): JsonResponse
    {
        $locale = $request->query('locale');
        $tags = $request->query('tags') ? explode(',', $request->query('tags')) : null;

        $result = $this->translationService->exportTranslations($locale, $tags);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Delete a translation
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->translationService->deleteTranslation($id);

        if (!$result['success']) {
            $statusCode = str_contains($result['message'], 'not found') ? 404 : 400;
            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }

    /**
     * Get available locales
     */
    public function getAvailableLocales(): JsonResponse
    {
        $locales = config('app.available_locales', ['en', 'fr', 'es']);

        return response()->json([
            'success' => true,
            'data' => $locales
        ]);
    }

    /**
     * Get all tags
     */
    public function getTags(): JsonResponse
    {
        $tags = \App\Models\Tag::all(['id', 'name', 'slug', 'description']);

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }
}

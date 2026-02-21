<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Verse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VerseController extends Controller
{
    /**
     * List all verses with pagination.
     */
    public function index(): JsonResponse
    {
        $verses = Verse::orderBy('date', 'desc')->paginate(15);

        return response()->json($verses);
    }

    /**
     * Get today's verse of the day.
     */
    public function today(): JsonResponse
    {
        $verse = Verse::where('date', now()->toDateString())->first();

        if (!$verse) {
            return response()->json([
                'message' => 'No verse found for today.',
            ], 404);
        }

        return response()->json([
            'verse' => $verse,
        ]);
    }

    /**
     * Store a new verse.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference'   => ['required', 'string', 'max:255'],
            'text'        => ['required', 'string'],
            'date'        => ['required', 'date', 'unique:verses,date'],
            'translation' => ['nullable', 'string', 'max:50'],
        ]);

        $verse = Verse::create($validated);

        return response()->json([
            'message' => 'Verse created successfully.',
            'verse'   => $verse,
        ], 201);
    }

    /**
     * Update an existing verse.
     */
    public function update(Request $request, Verse $verse): JsonResponse
    {
        $validated = $request->validate([
            'reference'   => ['sometimes', 'required', 'string', 'max:255'],
            'text'        => ['sometimes', 'required', 'string'],
            'date'        => ['sometimes', 'required', 'date', 'unique:verses,date,' . $verse->id],
            'translation' => ['nullable', 'string', 'max:50'],
        ]);

        $verse->update($validated);

        return response()->json([
            'message' => 'Verse updated successfully.',
            'verse'   => $verse->fresh(),
        ]);
    }

    /**
     * Delete a verse.
     */
    public function destroy(Verse $verse): JsonResponse
    {
        $verse->delete();

        return response()->json([
            'message' => 'Verse deleted successfully.',
        ]);
    }
}

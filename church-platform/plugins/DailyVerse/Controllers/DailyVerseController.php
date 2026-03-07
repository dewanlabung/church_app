<?php

namespace Plugins\DailyVerse\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;
use League\Csv\Writer;
use Plugins\DailyVerse\Models\DailyVerse;
use Plugins\DailyVerse\Jobs\ImportDailyVersesJob;
use SplTempFileObject;

class DailyVerseController extends Controller
{
    public function today(): JsonResponse
    {
        $verse = Cache::remember('daily_verse_today', now()->secondsUntilEndOfDay(), function () {
            return DailyVerse::forDate(today())->first()
                ?? DailyVerse::active()->inRandomOrder()->first();
        });

        return response()->json(['verse' => $verse]);
    }

    public function random(): JsonResponse
    {
        return response()->json([
            'verse' => DailyVerse::active()->inRandomOrder()->first(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $verses = DailyVerse::query()
            ->when($request->search, fn ($q) => $q->where('reference', 'like', "%{$request->search}%")
                                                   ->orWhere('text', 'like', "%{$request->search}%"))
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json($verses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date'      => 'required|date|unique:daily_verses,date',
            'reference' => 'required|string|max:100',
            'text'      => 'required|string',
            'version'   => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $verse = DailyVerse::create($data);
        return response()->json($verse, 201);
    }

    public function update(Request $request, DailyVerse $verse): JsonResponse
    {
        $data = $request->validate([
            'date'      => "sometimes|date|unique:daily_verses,date,{$verse->id}",
            'reference' => 'sometimes|string|max:100',
            'text'      => 'sometimes|string',
            'version'   => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $verse->update($data);
        Cache::forget('daily_verse_today');

        return response()->json($verse);
    }

    public function destroy(DailyVerse $verse): JsonResponse
    {
        $verse->delete();
        Cache::forget('daily_verse_today');
        return response()->json(['message' => 'Verse deleted.']);
    }

    // -------------------------------------------------------------------------
    // CSV Import
    // -------------------------------------------------------------------------

    /**
     * Import verses from CSV file.
     * Expected columns: date, reference, text, version (optional)
     */
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $path = $request->file('file')->getRealPath();

        // Preview first 5 rows synchronously, then queue the full import
        $csv     = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $preview = [];
        $errors  = [];
        $row     = 0;

        foreach ($csv->getRecords() as $record) {
            $row++;
            if (empty($record['date']) || empty($record['reference']) || empty($record['text'])) {
                $errors[] = "Row {$row}: missing required columns (date, reference, text)";
                continue;
            }
            if ($row <= 5) {
                $preview[] = $record;
            }
        }

        if (! empty($errors) && count($errors) === $row) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        // Store file and queue import job
        $storedPath = $request->file('file')->store('imports', 'local');
        ImportDailyVersesJob::dispatch($storedPath, $request->user()->id)
            ->onQueue('imports');

        return response()->json([
            'message'    => "Import queued. {$row} rows found. You'll receive an email summary when complete.",
            'preview'    => $preview,
            'total_rows' => $row,
            'errors'     => $errors,
        ]);
    }

    /**
     * Download a sample CSV with correct column headers.
     */
    public function sampleCsv()
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne(['date', 'reference', 'text', 'version']);
        $csv->insertOne(['2025-01-01', 'John 3:16', 'For God so loved the world...', 'KJV']);
        $csv->insertOne(['2025-01-02', 'Psalm 23:1', 'The Lord is my shepherd...', 'NIV']);
        $csv->insertOne(['2025-01-03', 'Romans 8:28', 'And we know that in all things God works for the good...', 'ESV']);

        return response((string) $csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="daily-verses-sample.csv"',
        ]);
    }

    /**
     * Export all verses as CSV.
     */
    public function exportCsv()
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne(['date', 'reference', 'text', 'version', 'is_active']);

        DailyVerse::orderBy('date')->chunk(200, function ($verses) use ($csv) {
            foreach ($verses as $verse) {
                $csv->insertOne([
                    $verse->date->format('Y-m-d'),
                    $verse->reference,
                    $verse->text,
                    $verse->version,
                    $verse->is_active ? '1' : '0',
                ]);
            }
        });

        return response((string) $csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="daily-verses-export.csv"',
        ]);
    }
}

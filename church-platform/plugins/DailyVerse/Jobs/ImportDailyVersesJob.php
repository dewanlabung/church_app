<?php

namespace Plugins\DailyVerse\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Plugins\DailyVerse\Models\DailyVerse;

class ImportDailyVersesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(
        protected string $storedPath,
        protected int    $adminUserId
    ) {}

    public function handle(): void
    {
        $path = Storage::disk('local')->path($this->storedPath);
        $csv  = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 1;

        foreach ($csv->getRecords() as $record) {
            $row++;

            if (empty($record['date']) || empty($record['reference']) || empty($record['text'])) {
                $errors[] = "Row {$row}: missing required fields";
                continue;
            }

            // Skip duplicates
            if (DailyVerse::where('date', $record['date'])->exists()) {
                $skipped++;
                continue;
            }

            try {
                DailyVerse::create([
                    'date'      => $record['date'],
                    'reference' => trim($record['reference']),
                    'text'      => trim($record['text']),
                    'version'   => trim($record['version'] ?? 'KJV'),
                    'is_active' => true,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: {$e->getMessage()}";
            }
        }

        // Clean up
        Storage::disk('local')->delete($this->storedPath);

        // Email summary to admin
        $admin = User::find($this->adminUserId);
        if ($admin) {
            $this->sendSummaryEmail($admin, $imported, $skipped, $errors);
        }
    }

    protected function sendSummaryEmail(User $admin, int $imported, int $skipped, array $errors): void
    {
        $errorList = implode('<br>', $errors);
        $html = <<<HTML
<h3>Daily Verse Import Complete</h3>
<p><strong>Imported:</strong> {$imported}</p>
<p><strong>Skipped (duplicates):</strong> {$skipped}</p>
<p><strong>Errors:</strong> {$errorList}</p>
HTML;

        Mail::send([], [], function ($mail) use ($admin, $html) {
            $mail->to($admin->email, $admin->name)
                 ->subject('Daily Verse Import Summary')
                 ->html($html);
        });
    }
}

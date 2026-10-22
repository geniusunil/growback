<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PurgeCompletedActivities extends Command
{
    protected $signature = 'activities:purge-completed
                            {--execute : Permanently delete eligible activities}';

    protected $description = 'Delete completed activities older than 60 days';

    public function handle(): int
    {
        $cutoff = now('Asia/Kolkata')->subDays(60);

        $this->info('Cutoff: ' . $cutoff->toDateTimeString());

        $query = Activity::withTrashed()
            ->where('is_completed', true)
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $cutoff);

        if (!$this->option('execute')) {
            $this->warn('DRY RUN: no data or files will be deleted.');

            $query->orderBy('id')->chunkById(100, function ($activities) {
                foreach ($activities as $activity) {
                    $this->line("Eligible ID: {$activity->id}");
                }
            });

            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;

        $query->orderBy('id')->chunkById(100, function ($activities) use (&$deleted, &$failed) {
            foreach ($activities as $activity) {
                $id = $activity->id;
                $thumbnail = $activity->thumbnail;
                $attachments = $activity->attachments()->get();

                try {
                    DB::transaction(function () use ($activity) {
                        $activity->attachments()->delete();
                        $activity->forceDelete();
                    });

                    foreach ($attachments as $attachment) {
                        $filename = $attachment->file_name;

                        if (!$filename) {
                            continue;
                        }

                        $shared = Attachment::where('file_name', $filename)
                            ->where('activity_id', '!=', $id)
                            ->exists();

                        if (!$shared) {
                            $path = 'attachments/' . $filename;

                            if (Storage::disk('public')->exists($path)
                                && !Storage::disk('public')->delete($path)) {
                                Log::warning('Attachment file cleanup failed', [
                                    'activity_id' => $id,
                                    'path' => $path,
                                ]);
                            }
                        }
                    }

                    if ($thumbnail) {
                        $shared = Activity::withTrashed()
                            ->where('thumbnail', $thumbnail)
                            ->where('id', '!=', $id)
                            ->exists();

                        if (!$shared) {
                            $path = 'thumbnails/' . $thumbnail;

                            if (Storage::disk('public')->exists($path)
                                && !Storage::disk('public')->delete($path)) {
                                Log::warning('Thumbnail cleanup failed', [
                                    'activity_id' => $id,
                                    'path' => $path,
                                ]);
                            }
                        }
                    }

                    $this->info("Deleted activity ID: {$id}");
                    $deleted++;
                } catch (Throwable $e) {
                    $failed++;

                    Log::error('Completed activity purge failed', [
                        'activity_id' => $id,
                        'error' => $e->getMessage(),
                    ]);

                    $this->error("Failed activity ID: {$id}; check Laravel log.");
                }
            }
        });

        $this->info("Deleted: {$deleted}; failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

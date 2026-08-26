<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class IndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup Sunil data, refresh database and restore Sunil data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Find Sunil by Email
            |--------------------------------------------------------------------------
            */

            $sunil = User::where(
                'email',
                'geniusunil@gmail.com'
            )->first();

            if (!$sunil) {

                Log::error(
                    'Sunil user not found. Migration refresh cancelled.'
                );

                $this->error(
                    'Sunil user not found. Migration refresh cancelled.'
                );

                return Command::FAILURE;
            }

            $userId = $sunil->id;

            Log::info(
                "Sunil found. User ID: {$userId}"
            );

            /*
            |--------------------------------------------------------------------------
            | Backup ONLY Sunil
            |--------------------------------------------------------------------------
            */

            Log::info(
                "Running backup for Sunil. User ID: {$userId}"
            );

            $backupExitCode = Artisan::call('user:backup', [
                '--users' => (string) $userId,
            ]);

            $backupOutput = Artisan::output();

            Log::info(
                'Sunil backup output: ' . $backupOutput
            );

            /*
            |--------------------------------------------------------------------------
            | Verify Backup Command
            |--------------------------------------------------------------------------
            */

            if ($backupExitCode !== Command::SUCCESS) {

                Log::error(
                    'Sunil backup command failed.'
                );

                $this->error(
                    'Sunil backup failed. Migration refresh cancelled.'
                );

                return Command::FAILURE;
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Backup ZIP Exists
            |--------------------------------------------------------------------------
            */

            $backupFile = storage_path(
                'app/private/backups/users_' . $userId . '.zip'
            );

            if (!file_exists($backupFile)) {

                Log::error(
                    "Sunil backup ZIP was not created: {$backupFile}"
                );

                $this->error(
                    'Sunil backup ZIP was not created. Migration refresh cancelled.'
                );

                return Command::FAILURE;
            }

            Log::info(
                "Sunil backup verified: {$backupFile}"
            );

            /*
            |--------------------------------------------------------------------------
            | Run migrate:refresh
            |--------------------------------------------------------------------------
            */

            Log::info(
                'Running migrate:refresh...'
            );

            $migrationExitCode = Artisan::call(
                'migrate:refresh',
                [
                    '--force' => true,
                ]
            );

            $migrationOutput = Artisan::output();

            Log::info(
                'Migration output: ' . $migrationOutput
            );

            /*
            |--------------------------------------------------------------------------
            | Verify Migration
            |--------------------------------------------------------------------------
            */

            if ($migrationExitCode !== Command::SUCCESS) {

                Log::error(
                    'Migration refresh failed. Restore was not attempted.'
                );

                $this->error(
                    'Migration refresh failed. Restore was not attempted.'
                );

                return Command::FAILURE;
            }

            Log::info(
                'Migration completed successfully.'
            );

            /*
            |--------------------------------------------------------------------------
            | Restore ONLY Sunil
            |--------------------------------------------------------------------------
            */

            $restoreFileName = 'users_' . $userId;

            Log::info(
                "Restoring Sunil backup: {$restoreFileName}"
            );

            $restoreExitCode = Artisan::call(
                'user:backup',
                [
                    '--restore' => $restoreFileName,
                ]
            );

            $restoreOutput = Artisan::output();

            Log::info(
                'Sunil restore output: ' . $restoreOutput
            );

            /*
            |--------------------------------------------------------------------------
            | Verify Restore
            |--------------------------------------------------------------------------
            */

            if ($restoreExitCode !== Command::SUCCESS) {

                Log::error(
                    'Sunil restore command failed.'
                );

                $this->error(
                    'Sunil restore failed.'
                );

                return Command::FAILURE;
            }

            Log::info(
                'Sunil data restore completed successfully.'
            );

            $this->info(
                "Sunil's data successfully backed up, database refreshed and restored."
            );

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            Log::error(
                'Index command failed: ' . $e->getMessage(),
                [
                    'trace' => $e->getTraceAsString(),
                ]
            );

            $this->error(
                'Operation failed: ' . $e->getMessage()
            );

            return Command::FAILURE;
        }
    }
}
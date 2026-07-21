<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Run all scheduled commands';

    /**
     * Execute the console command.
     */
   public function handle()
{
    try {

        Log::info('Running backup command...');

        Artisan::call('user:backup', [
            '--users' => 'all',
        ]);

        Log::info('Backup completed.');

        Log::info('Running migrate:refresh...');

        Artisan::call('migrate:refresh', [
            '--force' => true,
        ]);

        Log::info('Migration completed.');

        Log::info('Running restore...');

        Artisan::call('user:backup', [
            '--restore' => 'users_all',
        ]);

        Log::info('Restore completed.');

    } catch (\Throwable $e) {

        Log::error($e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
}
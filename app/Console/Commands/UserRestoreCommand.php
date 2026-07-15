<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UserBackup\BackupRestoreService;

class UserRestoreCommand extends Command
{
    /**
     * Command signature.
     */
    protected $signature = 'user:restore {file}';

    /**
     * Command description.
     */
    protected $description = 'Restore user backup from zip file';

    protected BackupRestoreService $restoreService;

    public function __construct(BackupRestoreService $restoreService)
    {
        parent::__construct();

        $this->restoreService = $restoreService;
    }

    public function handle()
    {
        $file = $this->argument('file');

        $message = $this->restoreService->restore($file);

        $this->info($message);

        return Command::SUCCESS;
    }
}
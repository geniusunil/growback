<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UserBackup\BackupExportService;
use App\Services\UserBackup\BackupRestoreService;

class UserBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
 protected $signature = 'user:backup
                        {--users= : User IDs or all}
                        {--guests= : Guest IDs or all}
                        {--restore= : Backup filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export and restore user backup';

    protected BackupExportService $exportService;
    protected BackupRestoreService $restoreService;

    public function __construct(
        BackupExportService $exportService,
        BackupRestoreService $restoreService
    ) {
        parent::__construct();

        $this->exportService = $exportService;
        $this->restoreService = $restoreService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
      $users = $this->option('users');
$guests = $this->option('guests');
$restore = $this->option('restore');

if ($restore) {
    $this->info($this->restoreService->restore($restore));
    return Command::SUCCESS;
}

if ($users || $guests) {

    $zipFile = $this->exportService->export(
        $users ?? '',
        $guests
    );

    $filePath = storage_path('app/private/backups/' . $zipFile);

    $this->info("Backup created: " . $filePath);

    // Windows me ZIP file open/select karne ke liye
    exec('start "" "' . $filePath . '"');

    return Command::SUCCESS;
}

$this->error('Please provide --users, --guests or --restore option.');

return Command::FAILURE;
    }
}
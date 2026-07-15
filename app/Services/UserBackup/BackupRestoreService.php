<?php

namespace App\Services\UserBackup;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupRestoreService
{
    protected BackupImportService $importService;

    public function __construct(BackupImportService $importService)
    {
        $this->importService = $importService;
    }

    public function restore(string $fileName)
    {
        $zipPath = storage_path('app/private/backups/' . $fileName . '.zip');

        if (!file_exists($zipPath)) {
            return "Backup ZIP not found.";
        }

        $extractPath = storage_path('app/private/backups/' . $fileName);

        // Delete old extracted folder if exists
        if (is_dir($extractPath)) {
            Storage::deleteDirectory('backups/' . $fileName);
        }

        // Create extraction folder
        Storage::makeDirectory('backups/' . $fileName);

        $zip = new ZipArchive();

        $result = $zip->open($zipPath);

        // Debug


        if ($result !== true) {
            return "Unable to open ZIP file.";
        }

        $zip->extractTo($extractPath);

        $zip->close();

 

        // Import backup.json
        $message = $this->importService->import(
            'backups/' . $fileName
        );

        // Delete extracted folder
        Storage::deleteDirectory('backups/' . $fileName);

        return $message;
    }
}
<?php

namespace App\Services\UserBackup;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupRestoreService
{
    protected BackupImportService $importService;

    public function __construct(
        BackupImportService $importService
    ) {
        $this->importService = $importService;
    }

    public function restore(string $fileName)
    {
        /*
        |--------------------------------------------------------------------------
        | ZIP Path
        |--------------------------------------------------------------------------
        */

        $zipPath =
            storage_path(
                'app/private/backups/' .
                $fileName .
                '.zip'
            );

        if (!file_exists($zipPath)) {
            return "Backup ZIP not found.";
        }

        /*
        |--------------------------------------------------------------------------
        | Extraction Path
        |--------------------------------------------------------------------------
        */

        $extractPath =
            storage_path(
                'app/private/backups/' .
                $fileName
            );

        /*
        |--------------------------------------------------------------------------
        | Remove Existing Extraction
        |--------------------------------------------------------------------------
        */

        if (is_dir($extractPath)) {

            Storage::deleteDirectory(
                'backups/' . $fileName
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Extraction Directory
        |--------------------------------------------------------------------------
        */

        Storage::makeDirectory(
            'backups/' . $fileName
        );

        /*
        |--------------------------------------------------------------------------
        | Open ZIP
        |--------------------------------------------------------------------------
        */

        $zip = new ZipArchive();

        $result = $zip->open($zipPath);

        if ($result !== true) {

            return "Unable to open ZIP file.";
        }

        /*
        |--------------------------------------------------------------------------
        | Extract
        |--------------------------------------------------------------------------
        */

        if (!$zip->extractTo($extractPath)) {

            $zip->close();

            return "Unable to extract ZIP file.";
        }

        $zip->close();

        /*
        |--------------------------------------------------------------------------
        | Import
        |--------------------------------------------------------------------------
        */

        $message = $this->importService->import(
            'backups/' . $fileName
        );

        /*
        |--------------------------------------------------------------------------
        | Cleanup
        |--------------------------------------------------------------------------
        */

        Storage::deleteDirectory(
            'backups/' . $fileName
        );

        return $message;
    }
}

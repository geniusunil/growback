<?php

namespace App\Services\UserBackup;

use ZipArchive;
use Illuminate\Support\Facades\Storage;

class BackupZipService
{
    public function createZip(string $folderName): string
    {
        $folderPath = storage_path('app/private/backups/' . $folderName);

        if (!is_dir($folderPath)) {
            throw new \Exception("Backup folder not found: " . $folderPath);
        }

        $zipFileName = $folderName . '.zip';

        $zipPath = storage_path('app/private/backups/' . $zipFileName);


        // Remove old zip
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }


        $zip = new ZipArchive();

        $result = $zip->open(
            $zipPath,
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        );


        if ($result !== true) {
            throw new \Exception(
                "Unable to create zip file. Error code: " . $result
            );
        }


        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $folderPath,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );


        foreach ($files as $file) {

            if (!$file->isDir()) {

                $filePath = $file->getRealPath();

                $relativePath = substr(
                    $filePath,
                    strlen($folderPath) + 1
                );


                $zip->addFile(
                    $filePath,
                    $relativePath
                );
            }
        }


        // Important
        $zip->close();


        // Verify zip created
        if (!file_exists($zipPath)) {
            throw new \Exception("Zip file was not created.");
        }


        return $zipFileName;
    }
}
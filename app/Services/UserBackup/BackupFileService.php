<?php

namespace App\Services\UserBackup;

use Illuminate\Support\Facades\Storage;

class BackupFileService
{
    public function copyFiles(
        array $backup,
        string $folderName
    ): void {

        Storage::makeDirectory(
            'backups/' . $folderName . '/thumbnails'
        );

        Storage::makeDirectory(
            'backups/' . $folderName . '/attachments'
        );

        /*
        |--------------------------------------------------------------------------
        | Registered User Activities
        |--------------------------------------------------------------------------
        */

        foreach ($backup['users'] ?? [] as $user) {

            foreach ($user['activities'] ?? [] as $activity) {

                $this->copyThumbnail(
                    $activity['thumbnail'] ?? null,
                    $folderName
                );

                $this->copyAttachments(
                    $activity['attachments'] ?? [],
                    $folderName
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Guest Activities
        |--------------------------------------------------------------------------
        */

        foreach ($backup['guests'] ?? [] as $guest) {

            foreach ($guest['activities'] ?? [] as $activity) {

                $this->copyThumbnail(
                    $activity['thumbnail'] ?? null,
                    $folderName
                );

                $this->copyAttachments(
                    $activity['attachments'] ?? [],
                    $folderName
                );
            }
        }
    }

    private function copyThumbnail(
        ?string $thumbnail,
        string $folderName
    ): void {

        if (empty($thumbnail)) {
            return;
        }

        $source = 'thumbnails/' . $thumbnail;

        $destination =
            'backups/' .
            $folderName .
            '/thumbnails/' .
            $thumbnail;

        if (
            Storage::disk('public')
                ->exists($source)
        ) {

            Storage::put(
                $destination,
                Storage::disk('public')->get($source)
            );
        }
    }

    private function copyAttachments(
        array $attachments,
        string $folderName
    ): void {

        foreach ($attachments as $attachment) {

            if (empty($attachment['file_name'])) {
                continue;
            }

            $fileName =
                $attachment['file_name'];

            $source =
                'attachments/' . $fileName;

            $destination =
                'backups/' .
                $folderName .
                '/attachments/' .
                $fileName;

            if (
                Storage::disk('public')
                    ->exists($source)
            ) {

                Storage::put(
                    $destination,
                    Storage::disk('public')->get($source)
                );
            }
        }
    }
}

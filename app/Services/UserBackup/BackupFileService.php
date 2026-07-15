<?php

namespace App\Services\UserBackup;

use Illuminate\Support\Facades\Storage;

class BackupFileService
{
    public function copyFiles(array $backup, string $folderName): void
    {
        Storage::makeDirectory('backups/' . $folderName . '/thumbnails');
        Storage::makeDirectory('backups/' . $folderName . '/attachments');

        /*
        |--------------------------------------------------------------------------
        | User Activities
        |--------------------------------------------------------------------------
        */

        foreach ($backup['users'] as $user) {

            foreach ($user['activities'] as $activity) {

                /*
                |--------------------------------------------------------------------------
                | Thumbnail
                |--------------------------------------------------------------------------
                */

                if (!empty($activity['thumbnail'])) {

                    $source = 'thumbnails/' . $activity['thumbnail'];

                    $destination = 'backups/' .
                        $folderName .
                        '/thumbnails/' .
                        $activity['thumbnail'];

                    if (Storage::disk('public')->exists($source)) {

                        Storage::put(
                            $destination,
                            Storage::disk('public')->get($source)
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Attachments
                |--------------------------------------------------------------------------
                */

                if (!empty($activity['attachments'])) {

                    foreach ($activity['attachments'] as $attachment) {

                        if (!empty($attachment['file_name'])) {

                            $source = 'attachments/' . $attachment['file_name'];

                            $destination = 'backups/' .
                                $folderName .
                                '/attachments/' .
                                $attachment['file_name'];

                            if (Storage::disk('public')->exists($source)) {

                                Storage::put(
                                    $destination,
                                    Storage::disk('public')->get($source)
                                );
                            }
                        }
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Guest Activities
        |--------------------------------------------------------------------------
        */

        if (!empty($backup['guests'])) {

            foreach ($backup['guests'] as $guest) {

                foreach ($guest['activities'] as $activity) {

                    /*
                    |--------------------------------------------------------------------------
                    | Guest Thumbnail
                    |--------------------------------------------------------------------------
                    */

                    if (!empty($activity['thumbnail'])) {

                        $source = 'thumbnails/' . $activity['thumbnail'];

                        $destination = 'backups/' .
                            $folderName .
                            '/thumbnails/' .
                            $activity['thumbnail'];

                        if (Storage::disk('public')->exists($source)) {

                            Storage::put(
                                $destination,
                                Storage::disk('public')->get($source)
                            );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Guest Attachments
                    |--------------------------------------------------------------------------
                    */

                    if (!empty($activity['attachments'])) {

                        foreach ($activity['attachments'] as $attachment) {

                            if (!empty($attachment['file_name'])) {

                                $source = 'attachments/' . $attachment['file_name'];

                                $destination = 'backups/' .
                                    $folderName .
                                    '/attachments/' .
                                    $attachment['file_name'];

                                if (Storage::disk('public')->exists($source)) {

                                    Storage::put(
                                        $destination,
                                        Storage::disk('public')->get($source)
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
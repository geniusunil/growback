<?php

namespace App\Services\UserBackup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupImportService
{
    public function import(string $folderPath)
    {
        $jsonPath = $folderPath . '/backup.json';

        if (!Storage::exists($jsonPath)) {
            return "Backup file not found.";
        }

        $backup = json_decode(
            Storage::get($jsonPath),
            true
        );

        if (!is_array($backup)) {
            return "Invalid backup file.";
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Restore Registered Users
            |--------------------------------------------------------------------------
            */

            foreach ($backup['users'] ?? [] as $userData) {

                $userData = $userData['user'];

                /*
                |--------------------------------------------------------------------------
                | Preserve Original User ID
                |--------------------------------------------------------------------------
                */

                DB::table('users')->updateOrInsert(
                    [
                        'id' => $userData['id'],
                    ],
                    [
                        'username' =>
                            $userData['username'],

                        'email' =>
                            $userData['email'],

                        'email_verified_at' =>
                            $userData['email_verified_at'],

                        'password' =>
                            $userData['password'],

                        'remember_token' =>
                            $userData['remember_token'],

                        'is_deletion_scheduled' =>
                            $userData['is_deletion_scheduled'],

                        'deletion_scheduled_at' =>
                            $userData['deletion_scheduled_at'],

                        'deletion_due_at' =>
                            $userData['deletion_due_at'],

                        'created_at' =>
                            $userData['created_at'],

                        'updated_at' =>
                            $userData['updated_at'],
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Restore User Activities
            |--------------------------------------------------------------------------
            */

            foreach ($backup['users'] ?? [] as $userBackup) {

                $originalUserId =
                    $userBackup['user']['id'];

                foreach (
                    $userBackup['activities'] ?? []
                    as $activityData
                ) {

                    $this->restoreActivity(
                        $activityData,
                        $originalUserId
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Restore Guest Activities
            |--------------------------------------------------------------------------
            */

            foreach ($backup['guests'] ?? [] as $guestData) {

                foreach (
                    $guestData['activities'] ?? []
                    as $activityData
                ) {

                    $this->restoreActivity(
                        $activityData,
                        null,
                        $guestData['guest_id']
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Restore Physical Files
            |--------------------------------------------------------------------------
            */

            $this->restoreFiles(
                basename($folderPath)
            );

            DB::commit();

            return "Import completed.";

        } catch (\Throwable $e) {

            DB::rollBack();

            return "Import failed: " . $e->getMessage();
        }
    }

    /**
     * Restore one activity and its attachments.
     */
    private function restoreActivity(
        array $activityData,
        ?int $userId = null,
        ?string $guestId = null
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Determine Owner
        |--------------------------------------------------------------------------
        */

        if ($guestId !== null) {

            $activityUserId = null;
            $activityGuestId = $guestId;

        } else {

            $activityUserId = $userId;
            $activityGuestId = $activityData['guest_id'] ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | Restore Activity
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Original activity ID is preserved.
        |
        */

        DB::table('activities')->updateOrInsert(
            [
                'id' => $activityData['id'],
            ],
            [
                'user_id' =>
                    $activityUserId,

                'guest_id' =>
                    $activityGuestId,

                'title' =>
                    $activityData['title'],

                'description' =>
                    $activityData['description'],

                'category' =>
                    $activityData['category'],

                'duration_value' =>
                    $activityData['duration_value'],

                'duration_unit' =>
                    $activityData['duration_unit'],

                'due_date' =>
                    $activityData['due_date'],

                'is_completed' =>
                    $activityData['is_completed'],

                'completed_at' =>
                    $activityData['completed_at'],

                'reminder_times' =>
                    $this->jsonValue(
                        $activityData['reminder_times'] ?? null
                    ),

                'frequency_unit' =>
                    $activityData['frequency_unit'],

                'frequency_value' =>
                    $activityData['frequency_value'],

                'repeat_enabled' =>
                    $activityData['repeat_enabled'],

                'reminder_sound' =>
                    $activityData['reminder_sound'],

                'custom_sound_path' =>
                    $activityData['custom_sound_path'],

                'reminder_vibration' =>
                    $activityData['reminder_vibration'],

                'priority' =>
                    $activityData['priority'],

                'thumbnail' =>
                    $activityData['thumbnail'],

                'show_in_drawer' =>
                    $activityData['show_in_drawer'],

                'notification_sound' =>
                    $activityData['notification_sound'],

                'notification_vibration' =>
                    $activityData['notification_vibration'],

                'show_full_screen' =>
                    $activityData['show_full_screen'],

                'urls' =>
                    $this->jsonValue(
                        $activityData['urls'] ?? null
                    ),

                'created_at' =>
                    $activityData['created_at'],

                'updated_at' =>
                    $activityData['updated_at'],

                'deleted_at' =>
                    $activityData['deleted_at'] ?? null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Restore Attachments
        |--------------------------------------------------------------------------
        */

        foreach (
            $activityData['attachments'] ?? []
            as $attachmentData
        ) {

            DB::table('attachments')->updateOrInsert(
                [
                    'id' => $attachmentData['id'],
                ],
                [
                    'user_id' =>
                        $guestId !== null
                            ? null
                            : $userId,

                    'guest_id' =>
                        $guestId !== null
                            ? $guestId
                            : ($attachmentData['guest_id'] ?? null),

                    'activity_id' =>
                        $activityData['id'],

                    'file_name' =>
                        $attachmentData['file_name'],

                    'file_size' =>
                        $attachmentData['file_size'],

                    'created_at' =>
                        $attachmentData['created_at'],

                    'updated_at' =>
                        $attachmentData['updated_at'],
                ]
            );
        }
    }

    /**
     * Convert array values to JSON for DB.
     */
    private function jsonValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Restore thumbnails and attachments.
     */
    private function restoreFiles(string $folderName): void
    {
        Storage::disk('public')
            ->makeDirectory('thumbnails');

        Storage::disk('public')
            ->makeDirectory('attachments');

        /*
        |--------------------------------------------------------------------------
        | Thumbnails
        |--------------------------------------------------------------------------
        */

        $thumbnailFolder =
            'backups/' .
            $folderName .
            '/thumbnails';

        if (Storage::exists($thumbnailFolder)) {

            foreach (
                Storage::files($thumbnailFolder)
                as $file
            ) {

                Storage::disk('public')->put(
                    'thumbnails/' . basename($file),
                    Storage::get($file)
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Attachments
        |--------------------------------------------------------------------------
        */

        $attachmentFolder =
            'backups/' .
            $folderName .
            '/attachments';

        if (Storage::exists($attachmentFolder)) {

            foreach (
                Storage::files($attachmentFolder)
                as $file
            ) {

                Storage::disk('public')->put(
                    'attachments/' . basename($file),
                    Storage::get($file)
                );
            }
        }
    }
}

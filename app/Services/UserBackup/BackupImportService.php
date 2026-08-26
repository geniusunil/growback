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

            foreach ($backup['users'] ?? [] as $userBackup) {

                $userData = $userBackup['user'];

                /*
                |--------------------------------------------------------------------------
                | Preserve Original User ID
                |--------------------------------------------------------------------------
                */

                DB::table('users')->updateOrInsert(
                    [
                        'id' =>
                            $userData['id'],
                    ],
                    [
                        'username' =>
                            $userData['username'],

                        'email' =>
                            $userData['email'],

                        'email_verified_at' =>
                            $userData['email_verified_at'] ?? null,

                        'password' =>
                            $userData['password'],

                        'remember_token' =>
                            $userData['remember_token'] ?? null,

                        'is_deletion_scheduled' =>
                            $userData['is_deletion_scheduled'] ?? false,

                        'deletion_scheduled_at' =>
                            $userData['deletion_scheduled_at'] ?? null,

                        'deletion_due_at' =>
                            $userData['deletion_due_at'] ?? null,

                        /*
                        |--------------------------------------------------------------------------
                        | FCM Token
                        |--------------------------------------------------------------------------
                        */

                        'fcm_token' =>
                            $userData['fcm_token'] ?? null,

                        'created_at' =>
                            $userData['created_at'] ?? now(),

                        'updated_at' =>
                            $userData['updated_at'] ?? now(),
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

            $activityGuestId =
                $activityData['guest_id'] ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | Restore Activity
        |--------------------------------------------------------------------------
        |
        | Original activity ID is preserved.
        |
        */

        DB::table('activities')->updateOrInsert(
            [
                'id' =>
                    $activityData['id'],
            ],
            [
                'user_id' =>
                    $activityUserId,

                'guest_id' =>
                    $activityGuestId,

                'title' =>
                    $activityData['title'],

                'description' =>
                    $activityData['description'] ?? null,

                'category' =>
                    $activityData['category'] ?? 'General',

                'duration_value' =>
                    $activityData['duration_value'] ?? null,

                'duration_unit' =>
                    $activityData['duration_unit'] ?? null,

                'due_date' =>
                    $activityData['due_date'] ?? null,

                'is_completed' =>
                    $activityData['is_completed'] ?? false,

                'completed_at' =>
                    $activityData['completed_at'] ?? null,

                'reminder_times' =>
                    $this->jsonValue(
                        $activityData['reminder_times'] ?? null
                    ),

                'frequency_unit' =>
                    $activityData['frequency_unit'] ?? 'days',

                'frequency_value' =>
                    $activityData['frequency_value'] ?? 1,

                'repeat_enabled' =>
                    $activityData['repeat_enabled'] ?? false,

                'reminder_sound' =>
                    $activityData['reminder_sound'] ?? 'small',

                'custom_sound_path' =>
                    $activityData['custom_sound_path'] ?? null,

                'reminder_vibration' =>
                    $activityData['reminder_vibration'] ?? true,

                'priority' =>
                    $activityData['priority'] ?? 'medium',

                'thumbnail' =>
                    $activityData['thumbnail'] ?? null,

                'show_in_drawer' =>
                    $activityData['show_in_drawer'] ?? true,

                'notification_sound' =>
                    $activityData['notification_sound'] ?? true,

                'notification_vibration' =>
                    $activityData['notification_vibration'] ?? true,

                'show_full_screen' =>
                    $activityData['show_full_screen'] ?? false,

                /*
                |--------------------------------------------------------------------------
                | New Activity Fields
                |--------------------------------------------------------------------------
                */

                'snoozed_until' =>
                    $activityData['snoozed_until'] ?? null,

                'is_mandatory_gap' =>
                    $activityData['is_mandatory_gap'] ?? false,

                'mandatory_gap_value' =>
                    $activityData['mandatory_gap_value'] ?? 0,

                'mandatory_gap_unit' =>
                    $activityData['mandatory_gap_unit'] ?? 'minutes',

                'urls' =>
                    $this->jsonValue(
                        $activityData['urls'] ?? null
                    ),

                'created_at' =>
                    $activityData['created_at'] ?? now(),

                'updated_at' =>
                    $activityData['updated_at'] ?? now(),

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
                    'id' =>
                        $attachmentData['id'],
                ],
                [
                    'user_id' =>
                        $guestId !== null
                            ? null
                            : $userId,

                    'guest_id' =>
                        $guestId !== null
                            ? $guestId
                            : (
                                $attachmentData['guest_id']
                                ?? null
                            ),

                    'activity_id' =>
                        $activityData['id'],

                    'file_name' =>
                        $attachmentData['file_name'],

                    'file_size' =>
                        $attachmentData['file_size'] ?? null,

                    'created_at' =>
                        $attachmentData['created_at'] ?? now(),

                    'updated_at' =>
                        $attachmentData['updated_at'] ?? now(),
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
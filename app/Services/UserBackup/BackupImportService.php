<?php

namespace App\Services\UserBackup;

use App\Models\User;
use App\Models\Activity;
use App\Models\Attachment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class BackupImportService
{
    public function import(string $folderPath)
    {
        $jsonPath = $folderPath . '/backup.json';

        if (!Storage::exists($jsonPath)) {
            return "Backup file not found.";
        }

        $backup = json_decode(Storage::get($jsonPath), true);

        if (!$backup) {
            return "Invalid backup file.";
        }

        /*
        |--------------------------------------------------------------------------
        | Restore Registered Users
        |--------------------------------------------------------------------------
        */

        foreach ($backup['users'] as $userData) {

            $user = User::updateOrCreate(
                [
                    'email' => $userData['user']['email']
                ],
                [
                    'username' => $userData['user']['username'],
                    'password' => Hash::make('12345678'),
                    'email_verified_at' => $userData['user']['email_verified_at'],
                ]
            );

            foreach ($userData['activities'] as $activityData) {

                $activity = Activity::updateOrCreate(

                    [
                        'user_id' => $user->id,
                        'title' => $activityData['title'],
                    ],

                    [
                        'guest_id'               => $activityData['guest_id'],
                        'description'            => $activityData['description'],
                        'category'               => $activityData['category'],
                        'due_date'               => $activityData['due_date'],
                        'is_completed'           => $activityData['is_completed'],
                        'completed_at'           => $activityData['completed_at'],
                        'reminder_times'         => $activityData['reminder_times'],
                        'frequency_unit'         => $activityData['frequency_unit'],
                        'frequency_value'        => $activityData['frequency_value'],
                        'reminder_sound'         => $activityData['reminder_sound'],
                        'custom_sound_path'      => $activityData['custom_sound_path'],
                        'reminder_vibration'     => $activityData['reminder_vibration'],
                        'priority'               => $activityData['priority'],
                        'thumbnail'              => $activityData['thumbnail'],
                        'show_in_drawer'         => $activityData['show_in_drawer'],
                        'notification_sound'     => $activityData['notification_sound'],
                        'notification_vibration' => $activityData['notification_vibration'],
                        'show_full_screen'       => $activityData['show_full_screen'],
                    ]
                );

                if (!empty($activityData['attachments'])) {

                    foreach ($activityData['attachments'] as $attachmentData) {

                        Attachment::updateOrCreate(

                            [
                                'activity_id' => $activity->id,
                                'file_name' => $attachmentData['file_name'],
                            ],

                            [
                                'user_id' => $user->id,
                                'guest_id' => $attachmentData['guest_id'],
                                'file_size' => $attachmentData['file_size'],
                            ]
                        );
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Restore Guest Activities
        |--------------------------------------------------------------------------
        */

        if (!empty($backup['guests'])) {

            foreach ($backup['guests'] as $guestData) {

                foreach ($guestData['activities'] as $activityData) {

                    $activity = Activity::updateOrCreate(

                        [
                            'guest_id' => $guestData['guest_id'],
                            'title' => $activityData['title'],
                        ],

                        [
                            'user_id'                => null,
                            'description'            => $activityData['description'],
                            'category'               => $activityData['category'],
                            'due_date'               => $activityData['due_date'],
                            'is_completed'           => $activityData['is_completed'],
                            'completed_at'           => $activityData['completed_at'],
                            'reminder_times'         => $activityData['reminder_times'],
                            'frequency_unit'         => $activityData['frequency_unit'],
                            'frequency_value'        => $activityData['frequency_value'],
                            'reminder_sound'         => $activityData['reminder_sound'],
                            'custom_sound_path'      => $activityData['custom_sound_path'],
                            'reminder_vibration'     => $activityData['reminder_vibration'],
                            'priority'               => $activityData['priority'],
                            'thumbnail'              => $activityData['thumbnail'],
                            'show_in_drawer'         => $activityData['show_in_drawer'],
                            'notification_sound'     => $activityData['notification_sound'],
                            'notification_vibration' => $activityData['notification_vibration'],
                            'show_full_screen'       => $activityData['show_full_screen'],
                        ]
                    );

                    if (!empty($activityData['attachments'])) {

                        foreach ($activityData['attachments'] as $attachmentData) {

                            Attachment::updateOrCreate(

                                [
                                    'activity_id' => $activity->id,
                                    'file_name' => $attachmentData['file_name'],
                                ],

                                [
                                    'user_id' => null,
                                    'guest_id' => $guestData['guest_id'],
                                    'file_size' => $attachmentData['file_size'],
                                ]
                            );
                        }
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Restore Files
        |--------------------------------------------------------------------------
        */

        $this->restoreFiles(basename($folderPath));

        return "Import completed.";
    }

    private function restoreFiles(string $folderName): void
    {
        Storage::disk('public')->makeDirectory('thumbnails');
        Storage::disk('public')->makeDirectory('attachments');

        $thumbnailFolder = 'backups/' . $folderName . '/thumbnails';
        $attachmentFolder = 'backups/' . $folderName . '/attachments';

        if (Storage::exists($thumbnailFolder)) {

            foreach (Storage::files($thumbnailFolder) as $file) {

                Storage::disk('public')->put(
                    'thumbnails/' . basename($file),
                    Storage::get($file)
                );
            }
        }

        if (Storage::exists($attachmentFolder)) {

            foreach (Storage::files($attachmentFolder) as $file) {

                Storage::disk('public')->put(
                    'attachments/' . basename($file),
                    Storage::get($file)
                );
            }
        }
    }
}
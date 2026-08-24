<?php

namespace App\Services\UserBackup;

use App\Models\User;
use App\Models\Activity;
use Illuminate\Support\Facades\Storage;

class BackupExportService
{
    protected BackupFileService $fileService;
    protected BackupZipService $zipService;

    public function __construct(
        BackupFileService $fileService,
        BackupZipService $zipService
    ) {
        $this->fileService = $fileService;
        $this->zipService = $zipService;
    }

    public function export(
        string $users = '',
        ?string $guests = null
    ) {
        /*
        |--------------------------------------------------------------------------
        | Registered Users
        |--------------------------------------------------------------------------
        */

        if ($users === 'all') {

            $userCollection = User::with([
                'activities' => function ($query) {
                    $query->withTrashed()
                        ->with('attachments');
                }
            ])->get();

        } elseif (!empty($users)) {

            $userIds = array_map(
                'trim',
                explode(',', $users)
            );

            $userCollection = User::with([
                'activities' => function ($query) {
                    $query->withTrashed()
                        ->with('attachments');
                }
            ])
                ->whereIn('id', $userIds)
                ->get();

        } else {

            $userCollection = collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Backup Structure
        |--------------------------------------------------------------------------
        */

        $backup = [
            'version' => 2,
            'created_at' => now()->toDateTimeString(),
            'users' => [],
            'guests' => [],
        ];

        /*
        |--------------------------------------------------------------------------
        | Registered Users
        |--------------------------------------------------------------------------
        */

        foreach ($userCollection as $user) {

            $backup['users'][] = [

                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                    'password' => $user->getRawOriginal('password'),
                    'remember_token' => $user->getRawOriginal('remember_token'),

                    'is_deletion_scheduled' =>
                        (bool) $user->is_deletion_scheduled,

                    'deletion_scheduled_at' =>
                        $user->deletion_scheduled_at?->toDateTimeString(),

                    'deletion_due_at' =>
                        $user->deletion_due_at?->toDateTimeString(),

                    'created_at' =>
                        $user->created_at?->toDateTimeString(),

                    'updated_at' =>
                        $user->updated_at?->toDateTimeString(),
                ],

                'activities' => $this->formatActivities(
                    $user->activities
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Guest Activities
        |--------------------------------------------------------------------------
        */

        if ($users === 'all' || $guests === 'all') {

            $guestActivities = Activity::withTrashed()
                ->with('attachments')
                ->whereNotNull('guest_id')
                ->get()
                ->groupBy('guest_id');

        } elseif (!empty($guests)) {

            $guestIds = array_map(
                'trim',
                explode(',', $guests)
            );

            $guestActivities = Activity::withTrashed()
                ->with('attachments')
                ->whereIn('guest_id', $guestIds)
                ->get()
                ->groupBy('guest_id');

        } else {

            $guestActivities = collect();
        }

        foreach ($guestActivities as $guestId => $activities) {

            $backup['guests'][] = [
                'guest_id' => $guestId,
                'activities' => $this->formatActivities($activities),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Nothing Found
        |--------------------------------------------------------------------------
        */

        if (
            empty($backup['users']) &&
            empty($backup['guests'])
        ) {
            return 'No users or guests found.';
        }

        /*
        |--------------------------------------------------------------------------
        | Backup Folder
        |--------------------------------------------------------------------------
        */

        if (!Storage::exists('backups')) {
            Storage::makeDirectory('backups');
        }

        if (!empty($users)) {

            if ($users === 'all') {
                $folderName = 'users_all';
            } else {
                $folderName = 'users_' .
                    str_replace(',', '_', $users);
            }

        } else {

            if ($guests === 'all') {
                $folderName = 'guests_all';
            } else {
                $folderName = 'guests_' .
                    str_replace(',', '_', $guests);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fresh Backup Folder
        |--------------------------------------------------------------------------
        */

        Storage::deleteDirectory(
            'backups/' . $folderName
        );

        Storage::makeDirectory(
            'backups/' . $folderName
        );

        Storage::makeDirectory(
            'backups/' . $folderName . '/thumbnails'
        );

        Storage::makeDirectory(
            'backups/' . $folderName . '/attachments'
        );

        /*
        |--------------------------------------------------------------------------
        | Save JSON
        |--------------------------------------------------------------------------
        */

        Storage::put(
            'backups/' . $folderName . '/backup.json',
            json_encode(
                $backup,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Copy Physical Files
        |--------------------------------------------------------------------------
        */

        $this->fileService->copyFiles(
            $backup,
            $folderName
        );

        /*
        |--------------------------------------------------------------------------
        | Create ZIP
        |--------------------------------------------------------------------------
        */

        return $this->zipService->createZip(
            $folderName
        );
    }

    /**
     * Convert activities to a complete backup structure.
     */
    private function formatActivities($activities): array
    {
        $result = [];

        foreach ($activities as $activity) {

            $result[] = [

                'id' => $activity->id,

                'user_id' => $activity->user_id,

                'guest_id' => $activity->guest_id,

                'title' => $activity->title,

                'description' => $activity->description,

                'category' => $activity->category,

                'duration_value' => $activity->duration_value,

                'duration_unit' => $activity->duration_unit,

                'due_date' => $activity->due_date?->toDateTimeString(),

                'is_completed' => (bool) $activity->is_completed,

                'completed_at' =>
                    $activity->completed_at?->toDateTimeString(),

                'reminder_times' => $activity->reminder_times,

                'frequency_unit' => $activity->frequency_unit,

                'frequency_value' => $activity->frequency_value,

                'repeat_enabled' =>
                    (bool) $activity->repeat_enabled,

                'reminder_sound' => $activity->reminder_sound,

                'custom_sound_path' =>
                    $activity->custom_sound_path,

                'reminder_vibration' =>
                    (bool) $activity->reminder_vibration,

                'priority' => $activity->priority,

                'thumbnail' => $activity->thumbnail,

                'show_in_drawer' =>
                    (bool) $activity->show_in_drawer,

                'notification_sound' =>
                    (bool) $activity->notification_sound,

                'notification_vibration' =>
                    (bool) $activity->notification_vibration,

                'show_full_screen' =>
                    (bool) $activity->show_full_screen,

                'urls' => $activity->urls,

                'created_at' =>
                    $activity->created_at?->toDateTimeString(),

                'updated_at' =>
                    $activity->updated_at?->toDateTimeString(),

                'deleted_at' =>
                    $activity->deleted_at?->toDateTimeString(),

                'attachments' =>
                    $this->formatAttachments(
                        $activity->attachments
                    ),
            ];
        }

        return $result;
    }

    /**
     * Convert attachments to complete backup structure.
     */
    private function formatAttachments($attachments): array
    {
        $result = [];

        foreach ($attachments as $attachment) {

            $result[] = [

                'id' => $attachment->id,

                'user_id' => $attachment->user_id,

                'guest_id' => $attachment->guest_id,

                'activity_id' => $attachment->activity_id,

                'file_name' => $attachment->file_name,

                'file_size' => $attachment->file_size,

                'created_at' =>
                    $attachment->created_at?->toDateTimeString(),

                'updated_at' =>
                    $attachment->updated_at?->toDateTimeString(),
            ];
        }

        return $result;
    }
}

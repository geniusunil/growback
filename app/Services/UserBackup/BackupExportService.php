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

            $userCollection = User::with('activities.attachments')->get();

        } elseif (!empty($users)) {

            $userIds = array_map('trim', explode(',', $users));

            $userCollection = User::with('activities.attachments')
                ->whereIn('id', $userIds)
                ->get();

        } else {

            $userCollection = collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Backup Array
        |--------------------------------------------------------------------------
        */

        $backup = [
            'version'    => 1,
            'created_at' => now()->toDateTimeString(),
            'users'      => [],
            'guests'     => [],
        ];

        /*
        |--------------------------------------------------------------------------
        | Registered Users Data
        |--------------------------------------------------------------------------
        */

        foreach ($userCollection as $user) {

            $backup['users'][] = [

                'user' => $user->only([
                    'id',
                    'username',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ]),

                'activities' => $user->activities->toArray(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Guest Activities
        |--------------------------------------------------------------------------
        */
if ($users === 'all' || $guests === 'all') {

    $guestActivities = Activity::with('attachments')
        ->whereNotNull('guest_id')
        ->get()
        ->groupBy('guest_id');

} elseif (!empty($guests)) {

    $guestIds = array_map('trim', explode(',', $guests));

    $guestActivities = Activity::with('attachments')
        ->whereIn('guest_id', $guestIds)
        ->get()
        ->groupBy('guest_id');

} else {

    $guestActivities = collect();
}
        foreach ($guestActivities as $guestId => $activities) {

            $backup['guests'][] = [
                'guest_id'   => $guestId,
                'activities' => $activities->toArray(),
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
                $folderName = 'users_' . str_replace(',', '_', $users);
            }

        } else {

            if ($guests === 'all') {
                $folderName = 'guests_all';
            } else {
                $folderName = 'guests_' . str_replace(',', '_', $guests);
            }
        }

        // Fresh Folder
        Storage::deleteDirectory('backups/' . $folderName);

        Storage::makeDirectory('backups/' . $folderName);
        Storage::makeDirectory('backups/' . $folderName . '/thumbnails');
        Storage::makeDirectory('backups/' . $folderName . '/attachments');

        /*
        |--------------------------------------------------------------------------
        | Save backup.json
        |--------------------------------------------------------------------------
        */

        Storage::put(
            'backups/' . $folderName . '/backup.json',
            json_encode(
                $backup,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Copy Files
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

        $zipFile = $this->zipService->createZip(
            $folderName
        );

        return $zipFile;
    }
}
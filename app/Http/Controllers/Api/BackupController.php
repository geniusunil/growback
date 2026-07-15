<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserBackup\BackupExportService;
use App\Services\UserBackup\BackupRestoreService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    protected BackupExportService $exportService;
    protected BackupRestoreService $restoreService;

    public function __construct(
        BackupExportService $exportService,
        BackupRestoreService $restoreService
    ) {
        $this->exportService = $exportService;
        $this->restoreService = $restoreService;
    }

    /**
     * Export backup and download ZIP
     */
public function export(Request $request)
{

   

    $users = (string) $request->input('users', '');
    $guests = $request->input('guests');

    $zipFile = $this->exportService->export(
        $users,
        $guests
    );

    // Error message handle
    if (!$zipFile || $zipFile === 'No users or guests found.') {
        return response()->json([
            'success' => false,
            'message' => $zipFile ?: 'No users or guests found.'
        ], 404);
    }

    $path = storage_path('app/private/backups/' . $zipFile);

    if (!file_exists($path)) {
        return response()->json([
            'success' => false,
            'message' => 'Backup file not found.'
        ], 404);
    }

    while (ob_get_level()) {
        ob_end_clean();}

    return response()->download(
        $path,
        $zipFile,
        [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$zipFile.'"',
        ]
    );
}
    /**
     * Import backup ZIP
     */
    public function import(Request $request)
    {
        $request->validate([
            'backup' => 'required|file|mimes:zip'
        ]);

        $file = $request->file('backup');

        $fileName = pathinfo(
            $file->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        $file->move(
            storage_path('app/private/backups'),
            $fileName . '.zip'
        );

        $message = $this->restoreService->restore($fileName);

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
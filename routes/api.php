 <?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\GoogleLoginController;
    use App\Http\Controllers\ActivityController;
    use App\Http\Controllers\Api\BackupController;
    use App\Models\GuestSession;
    use Illuminate\Support\Str;
    use App\Models\Activity;

    Route::get('check-connection', function () {
        return response()->json(['message' => 'API is working']);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');


    Route::post('/guest-session', function (Request $request) {

        $request->validate([
            'guest_id' => 'required|string',
        ]);

        $guestId = $request->guest_id;

        // Check that this guest ID exists in activities.
        $activityExists = Activity::where('guest_id', $guestId)
            ->whereNull('user_id')
            ->exists();

        if (!$activityExists) {
            return response()->json([
                'success' => false,
                'message' => 'Guest activity not found.',
            ], 404);
        }

        // Reuse existing active session.
        $session = GuestSession::where('guest_id', $guestId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($session) {
            return response()->json([
                'success' => true,
                'guest_id' => $session->guest_id,
                'message' => 'Guest session already exists.',
            ]);
        }

        // Generate token for the existing guest ID.
        $plainToken = Str::random(64);

        $session = GuestSession::create([
            'guest_id' => $guestId,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'success' => true,
            'guest_id' => $session->guest_id,
            'token' => $plainToken,
            'expires_at' => $session->expires_at,
        ], 201);
    })->middleware('throttle:10,1');



    Route::middleware('user-or-guest')->group(function () {

        // Route::post('/activities', [
        //     ActivityController::class,
        //     'store',
        // ]);

        Route::get('/activities', [
            ActivityController::class,
            'index',
        ]);

        // IMPORTANT: trash route must come before {id}
        Route::get('/activities/trash', [
            ActivityController::class,
            'trash',
        ]);

        Route::get('/activities/{id}', [
            ActivityController::class,
            'show',
        ]);

        Route::post('/activities/{id}/update', [
            ActivityController::class,
            'update',
        ]);

        Route::post('/activities/{id}/force-delete', [
            ActivityController::class,
            'forceDelete',
        ]);

        Route::post('/activities/force-all', [
            ActivityController::class,
            'forceDeleteAll',
        ]);

        Route::delete('/activities/{id}', [
            ActivityController::class,
            'destroy',
        ]);

        Route::post('/activities/{id}/delete', [
            ActivityController::class,
            'destroy',
        ]);
    });


    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/google-login', [GoogleLoginController::class, 'googleLogin']);
    Route::post('/activities/{id}/uncomplete', [ActivityController::class, 'unmarkComplete']);

    Route::post('/activities', [
        ActivityController::class,
        'store',
    ]);


    // Route::get('/activities/trash', [ActivityController::class, 'trash']);
    Route::post('/activities/restore-all', [ActivityController::class, 'restoreAll']);




    Route::post('/activities/{id}/restore', [ActivityController::class, 'restore']);
    Route::post('/activities/{id}/complete', [ActivityController::class, 'markComplete']);

    Route::delete('/attachments/{id}', [ActivityController::class, 'deleteAttachment']);
    Route::post('/attachments/{id}/delete', [ActivityController::class, 'deleteAttachment']);


    Route::post('/delete-account', [AuthController::class, 'deleteAccount']);
    Route::post('/cancel-deletion', [AuthController::class, 'cancelDeletion']);
    Route::post('/reactivate-activity', [AuthController::class, 'reactivateActivity']);
    Route::post('/snooze-activity', [AuthController::class, 'snoozeActivity']);

    Route::post('/save-fcm-token', [AuthController::class, 'saveFcmToken']);
    Route::post('/send-fcm-push', [AuthController::class, 'triggerFcmPush']);

    Route::post('/backup/export', [BackupController::class, 'export']);
    Route::post('/backup/import', [BackupController::class, 'import']);

    Route::post('/save-holiday-settings', [AuthController::class, 'saveHolidaySettings']);
    Route::get('/get-holiday-settings', [AuthController::class, 'getHolidaySettings']);

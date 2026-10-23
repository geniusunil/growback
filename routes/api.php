 <?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\GoogleLoginController;
    use App\Http\Controllers\ActivityController;
    use App\Http\Controllers\Api\BackupController;
    use App\Models\GuestSession;
    use Illuminate\Support\Str;

    Route::get('check-connection', function () {
        return response()->json(['message' => 'API is working']);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');
    
    

  Route::post('/guest-session', [
    ActivityController::class,
    'createGuestSession',
])->middleware('throttle:10,1');



  
Route::middleware('user-or-guest')->group(function () {

    Route::post('/activities', [
        ActivityController::class,
        'store',
    ]);

    Route::get('/activities', [
        ActivityController::class,
        'index',
    ]);

    
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

    Route::post('/activities/{id}/delete', [ActivityController::class,'destroy',]);
});


    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/google-login', [GoogleLoginController::class, 'googleLogin']);
    Route::post('/activities/{id}/uncomplete', [ActivityController::class, 'unmarkComplete']);
    // Route::post('/activities', [ActivityController::class,'store',]);
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
    Route::get('/user-location', [AuthController::class, 'getUserCountry']);
    Route::get('/holiday-countries', [AuthController::class, 'getCountries']);

Route::get('/activities/{id}/subtasks', [
    ActivityController::class,
    'getSubtasks'
]);
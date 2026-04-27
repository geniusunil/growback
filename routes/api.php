 <?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\ActivityController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/google-login', [GoogleLoginController::class, 'googleLogin']);


Route::post('/activities', [ActivityController::class, 'store']);
Route::get('/activities', [ActivityController::class, 'index']);
Route::get('/activities/trash', [ActivityController::class, 'trash']);
Route::post('/activities/restore-all', [ActivityController::class, 'restoreAll']);
Route::post('/activities/force-all', [ActivityController::class, 'forceDeleteAll']);


Route::post('/activities/{id}/update', [ActivityController::class, 'update']);
Route::delete('/activities/{id}', [ActivityController::class, 'destroy']);        // Flutter uses DELETE /activities/$id
Route::post('/activities/{id}/delete', [ActivityController::class, 'destroy']);   // legacy fallback
Route::post('/activities/{id}/restore', [ActivityController::class, 'restore']);
Route::post('/activities/{id}/force-delete', [ActivityController::class, 'forceDelete']);


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * STEP 1: Send OTP
     */
    public function signup(Request $request)
    {
    
        $request->validate([
            'email' => [
                'required', 
                'min:6', 
                'regex:/^[A-Za-z0-9]+(.[A-Za-z0-9]+)?@[A-Za-z0-9-]+.[A-Za-z]{2,}$/'
            ],
        ]);

       $otp = random_int(100000, 999999);

        OtpVerification::updateOrCreate(
            ['email' => $request->email],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]
        );

        try {
            Mail::to($request->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Email sending failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'OTP sent to email',
        ], 200);
    }

    /**
     * STEP 2: Verify OTP & Login
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required',
        ]);

        $record = OtpVerification::where('email', $request->email)->first();

        if (!$record || (string)$record->otp !== (string)$request->otp) {
            return response()->json([
                'message' => 'Invalid OTP',
            ], 400);
        }

        if (Carbon::now()->gt($record->expires_at)) {
            return response()->json([
                'message' => 'OTP expired',
            ], 400);
        }

        // User create or fetch
        $user = User::firstOrCreate(
            ['email' => $request->email],
            [
               'username' => explode('@', $request->email)[0],
                'password' => Hash::make(Str::random(20)),
            ]
        );

          if ($request->filled('fcm_token')) {
            User::appendFcmToken($user, $request->fcm_token);
        }
        // OTP one-time use
        $record->delete();

        // Token generate
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
             'user'    => $user->fresh(),
        ], 200);
    }
    
  
  public function deleteAccount(Request $request)
    {
        $user = null;
        if ($request->user()) {
            $user = $request->user();
        } elseif ($request->filled('user_id')) {
            $user = User::find($request->user_id);
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        try {
            date_default_timezone_set('Asia/Kolkata');
            config(['app.timezone' => 'Asia/Kolkata']);
            try { \Illuminate\Support\Facades\DB::statement("SET time_zone = '+05:30'"); } catch (\Exception $e) {}

            $cooldownDays = 14;
            $now = \Carbon\Carbon::now('Asia/Kolkata');
            $deletionTime = $now->copy()->addDays($cooldownDays);
            $formattedDate = $deletionTime->format('d M Y \a\t h:i A');

            $scheduledAtStr = $now->format('Y-m-d H:i:s');
            $dueAtStr       = $deletionTime->format('Y-m-d H:i:s');

            // Direct DB update to bypass Eloquent Model UTC timezone mutation
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'is_deletion_scheduled' => 1,
                    'deletion_scheduled_at' => $scheduledAtStr,
                    'deletion_due_at'       => $dueAtStr,
                    'updated_at'            => $scheduledAtStr,
                ]);

            return response()->json([
                'success' => true,
                'message' => "Your account has been scheduled for deletion.\nIt will be permanently deleted on {$formattedDate}.",
                'deletion_date' => $formattedDate,
                'deletion_timestamp' => $deletionTime->toIso8601String(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error scheduling account deletion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel Account Deletion & restore normal user status
     */
    public function cancelDeletion(Request $request)
    {
        $user = null;
        if ($request->user()) {
            $user = $request->user();
        } elseif ($request->filled('user_id')) {
            $user = User::find($request->user_id);
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        try {
            date_default_timezone_set('Asia/Kolkata');
            config(['app.timezone' => 'Asia/Kolkata']);
            try { \Illuminate\Support\Facades\DB::statement("SET time_zone = '+05:30'"); } catch (\Exception $e) {}

            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'is_deletion_scheduled' => 0,
                    'deletion_scheduled_at' => null,
                    'deletion_due_at'       => null,
                    'updated_at'            => \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Account deletion cancelled successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling account deletion: ' . $e->getMessage()
            ], 500);
        }
    }
    
     public function reactivateActivity(Request $request)
    {
        $id = $request->input('activity_id') ?? $request->input('id');
        $title = $request->input('title');

        try {
            $query = \Illuminate\Support\Facades\DB::table('activities');
            if (!empty($id)) {
                $query->where('id', $id);
            } elseif (!empty($title)) {
                $query->where('title', $title);
            } else {
                return response()->json(['success' => false, 'message' => 'Activity ID or Title is required'], 400);
            }

            $query->update([
                'is_completed' => 0,
                'completed_at' => null,
                'updated_at'   => \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity reactivated in DB: is_completed = 0, completed_at = null.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reactivating activity: ' . $e->getMessage()
            ], 500);
        }
    }
    
     public function snoozeActivity(Request $request)
    {
        $id = $request->input('activity_id') ?? $request->input('id');
        $title = $request->input('title');
        $rawSnoozedUntil = $request->input('snoozed_until');

        try {
            date_default_timezone_set('Asia/Kolkata');
            config(['app.timezone' => 'Asia/Kolkata']);
            try { \Illuminate\Support\Facades\DB::statement("SET time_zone = '+05:30'"); } catch (\Exception $e) {}

            $snoozedUntil = null;
            if (!empty($rawSnoozedUntil)) {
                $snoozedUntil = \Carbon\Carbon::parse($rawSnoozedUntil)->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:00');
            }

            $query = \Illuminate\Support\Facades\DB::table('activities');
            if (!empty($id)) {
                $query->where('id', $id);
            } elseif (!empty($title)) {
                $query->where('title', $title);
            } else {
                return response()->json(['success' => false, 'message' => 'Activity ID or Title is required'], 400);
            }

            $query->update([
                'snoozed_until' => $snoozedUntil,
                'updated_at'    => \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Snooze timestamp saved in DB successfully.',
                'snoozed_until' => $snoozedUntil
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving snooze timestamp: ' . $e->getMessage()
            ], 500);
        }
    }
    public function saveFcmToken(Request $request)
    {
        $token = $request->input('fcm_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token required'], 400);
        }

        try {
            $user = $request->user();
            if ($user) {
                $user->update(['fcm_token' => $token]);
            }
            return response()->json(['success' => true, 'message' => 'FCM Token saved successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to send FCM Push Notification to all active tokens of a User
     */
    public static function sendFcmPushToUser($userId, $dataPayload = ['action' => 'sync_activities'])
    {
        try {
            if (!$userId) return false;
            $user = User::find($userId);
            if (!$user || empty($user->fcm_token)) return false;

            $serverKey = env('FCM_SERVER_KEY', '');
            if (empty($serverKey)) return false;

            $payload = [
                'to' => $user->fcm_token,
                'priority' => 'high',
                'data' => $dataPayload,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $result = curl_exec($ch);
            curl_close($ch);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * API Endpoint to manually trigger FCM Push to a user
     */
    public function triggerFcmPush(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId && $request->user()) {
            $userId = $request->user()->id;
        }

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID is required'], 400);
        }

        $sent = self::sendFcmPushToUser($userId, ['action' => 'sync_activities']);
        if ($sent) {
            return response()->json(['success' => true, 'message' => 'FCM Push notification sent successfully']);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to send FCM Push (check FCM_SERVER_KEY or fcm_token)'], 500);
        }
    }
    
 
}

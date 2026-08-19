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

        // OTP one-time use
        $record->delete();

        // Token generate
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
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
 
}

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

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

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
                'username' => explode('@', $request->email)[0] . '_' . Str::random(4),
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
}

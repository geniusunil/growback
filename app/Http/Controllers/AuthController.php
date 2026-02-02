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
            'email' => 'required|email',
        ]);

      $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpVerification::updateOrCreate(
            ['email' => $request->email],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]
        );

        Mail::to($request->email)->send(new OtpMail($otp));

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

       $record = OtpVerification::where('email', $request->email)
    ->first();

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

        // User create ya fetch
        $user = User::firstOrCreate(
            ['email' => $request->email],
            [
                'name' => 'OTP User',
                'password' => Hash::make(Str::random(20)), // dummy password
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

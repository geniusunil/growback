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
use Illuminate\Support\Facades\Validator;



class AuthController extends Controller
{
    /**
     * STEP 1: Send OTP
     */
    public function signup(Request $request)
    {                                                                            
$validator = Validator::make($request->all(), [
    'email' => [
        'required',
        'regex:/^[A-Za-z0-9]+([.-][A-Za-z0-9]+)?@[A-Za-z0-9-]+\.[A-Za-z]{2,}$/'
    ],
], [
    'email.required' => 'Email is required',
    'email.regex' => 'Invalid email format'
]);

if ($validator->fails()) {
    return response()->json([
        'status' => false,
        'message' => $validator->errors()->first()
    ], 422);
    
}
   $otp = random_int(100000, 999999);

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
        
            ]
        );

        $user->update([
    'email_verified_at' => now()
]);


       
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

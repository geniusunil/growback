<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Google_Client;

class GoogleLoginController extends Controller
{
    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required',
        ]);

        $client = new Google_Client([
            'client_id' => env('GOOGLE_CLIENT_ID'),
        ]);

        
        $payload = $client->verifyIdToken($request->id_token);

        if (!$payload) {
            return response()->json([
                'message' => 'Invalid Google token',
            ], 401);
        }

      $user = User::updateOrCreate(
    ['email' => $payload['email']],
    [
        'name' => $payload['name'] ?? 'Google User',
        'provider' => 'google',      
    
    ]
);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Google login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }
}

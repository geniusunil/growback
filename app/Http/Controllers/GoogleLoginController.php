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
        // Accept either id_token or access_token
        $request->validate([
            'id_token' => 'nullable|string',
            'access_token' => 'nullable|string',
        ]);

        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $email = null;
        $name = 'Google User';

        if ($request->id_token) {
            $payload = $client->verifyIdToken($request->id_token);
            if ($payload) {
                $email = $payload['email'];
                $name = $payload['name'] ?? 'Google User';
            }
        } elseif ($request->access_token) {
            // If Web sends access_token instead of id_token
            $client->setAccessToken($request->access_token);
            $oauth2 = new \Google\Service\Oauth2($client);
            try {
                $userInfo = $oauth2->userinfo->get();
                $email = $userInfo->email;
                $name = $userInfo->name;
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid Access Token'], 401);
            }
        }

        if (!$email) {
            return response()->json([
                'message' => 'Invalid Google token',
            ], 401);
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'username' => $name,
                'password' => Hash::make(Str::random(20)),
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

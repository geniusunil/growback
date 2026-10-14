<?php

namespace App\Http\Middleware;

use App\Models\GuestSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateUserOrGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        // First: Sanctum authenticated user
        $user = Auth::guard('sanctum')->user();

        if ($user) {
            $request->attributes->set('actor_type', 'user');
            $request->attributes->set('actor_id', $user->id);

            return $next($request);
        }

        // Otherwise, validate guest bearer token
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication token required.',
            ], 401);
        }

        $session = GuestSession::where(
            'token_hash',
            hash('sha256', $bearer)
        )->first();

        if (!$session ||
            ($session->expires_at &&
             $session->expires_at->isPast())) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.',
            ], 401);
        }

        $request->attributes->set('actor_type', 'guest');
        $request->attributes->set('actor_id', $session->guest_id);

        return $next($request);
    }
}

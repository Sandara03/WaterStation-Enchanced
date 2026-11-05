<?php
namespace App\Http\Middleware;

use App\Models\AccessToken;
use Closure;
use Illuminate\Http\Request;

class TokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $plain = $request->bearerToken();
        $token = $plain ? AccessToken::with('user')->where('token_hash', hash('sha256', $plain))
            ->where('expires_at', '>', now())->first() : null;
        if (!$token || !$token->user || !$token->user->is_active) {
            return response()->json(['message' => 'Your session has expired. Please sign in.'], 401);
        }
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('access_token_id', $token->id);
        return $next($request);
    }
}

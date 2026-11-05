<?php
namespace App\Http\Controllers;

use App\Models\{AccessToken, User};
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, RateLimiter};
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:255']);
        $email = strtolower(trim($data['email']));
        $key = 'login:'.$request->ip().':'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'], 429);
        }
        $user = User::where('email', $email)->first();
        // A dummy hash keeps missing-account password checks from returning immediately.
        $valid = Hash::check($data['password'], $user?->password ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (!$user || !$valid || !$user->is_active) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }
        RateLimiter::clear($key);
        $plain = bin2hex(random_bytes(32));
        DB::transaction(function () use ($user, $plain, $request) {
            AccessToken::where('expires_at', '<=', now())->delete();
            AccessToken::create(['user_id' => $user->id, 'token_hash' => hash('sha256', $plain), 'expires_at' => now()->addHours(8)]);
            $request->setUserResolver(fn () => $user);
            Audit::record('auth.login', 'User', $user->id, 'Signed in');
        });
        return response()->json(['token' => $plain, 'user' => $user, 'expires_in' => 28800])->header('Cache-Control', 'no-store');
    }

    public function me(Request $request) { return $request->user(); }

    public function logout(Request $request)
    {
        AccessToken::whereKey($request->attributes->get('access_token_id'))->delete();
        Audit::record('auth.logout', 'User', $request->user()->id, 'Signed out');
        return response()->json(['message' => 'Signed out.']);
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string|max:255',
            'password' => 'required|string|min:12|max:128|confirmed',
        ]);
        if (!Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        }
        DB::transaction(function () use ($request, $data) {
            $request->user()->update(['password' => $data['password']]);
            AccessToken::where('user_id', $request->user()->id)->delete();
            Audit::record('auth.password_changed', 'User', $request->user()->id, 'Password changed; sessions revoked');
        });
        return response()->json(['message' => 'Password changed. Sign in again.']);
    }
}

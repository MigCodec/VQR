<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        abort_unless($googleUser->getEmail(), 422, 'Google no entregó un correo para esta cuenta.');

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere(function ($query) use ($googleUser) {
                $query->where('email', $googleUser->getEmail())
                    ->whereNotNull('email_verified_at');
            })
            ->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'name' => $user->name ?: $googleUser->getName(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Usuario VQR',
                'email' => $googleUser->getEmail(),
                'email_verified_at' => now(),
                'password' => Str::password(32),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended($user->hasActiveSubscription()
            ? route('account.show')
            : route('billing.show'));
    }
}

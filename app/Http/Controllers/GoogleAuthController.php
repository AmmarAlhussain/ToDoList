<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Throwable;
use Illuminate\Support\Facades\Auth;


class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver("google")
            ->scopes(['openid','profile','email','https://www.googleapis.com/auth/calendar'])
            ->with(['access_type' => 'offline','prompt' => 'consent'])
            ->redirect();
    }

    public function callbackGoogle()
    {

        try {

            $google_user = Socialite::driver("google")->user();

            $token = $google_user->token;
            $refreshToken = $google_user->refreshToken;
            $expiresIn = $google_user->expiresIn;

            $user = User::where("google_id", $google_user->getId())->first();

            if (!$user) {
                $user = User::create([
                    "name" => $google_user->getName(),
                    "email" => $google_user->getEmail(),
                    "google_id" => $google_user->getId(),
                    "google_token" => $token,
                    "google_refresh_token" => $refreshToken,
                    "google_token_expires_in" => $expiresIn,
                    "google_token_created_at" => now(),
                ]);

            } else {
                $user->update([
                    "google_token" => $token,
                    "google_refresh_token" => $refreshToken ?: $user->google_refresh_token,
                    "google_token_expires_in" => $expiresIn,
                    "google_token_created_at" => now(),
                ]);
            }
            Auth::login($user);
            return redirect()->route("home");
        } catch (\Throwable $e) {
            dd("WRONGGGG". $e->getMessage());
        }
    }
}

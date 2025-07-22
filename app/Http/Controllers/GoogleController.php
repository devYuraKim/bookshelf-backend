<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Client;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class GoogleController extends Controller
{

    public function redirect()
    {
        $client = new Client();
        $client->setAuthConfig(base_path('client_secret.json'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->addScope([
            'openid',
            'email',
            'profile'
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent select_account');

        $auth_url = $client->createAuthUrl();
        return redirect()->away($auth_url);
    }

    //ID Token: authentication. proves user's identity.
    //Access Token: authorization. grants permission to call Google APIs for a limited time. Refresh Tokens needed.
    public function callback(Request $request)
    {
        $client = new Client();
        $client->setAuthConfig(base_path('client_secret.json'));
        $redirectUri = config('services.google.redirect');
        $client->setRedirectUri($redirectUri);

        if ($request->has('code')) {
            $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

            if (isset($token['error'])) {
                return response()->json(['error' => $token['error']], 400);
            }

            if (!isset($token['id_token'])) {
                return response()->json(['error' => 'ID token not found'], 400);
            }

            $payload = $client->verifyIdToken($token['id_token']);
            if (!$payload) {
                return response()->json(['error' => 'Invalid ID token'], 400);
            }

            $googleId = $payload['sub'];
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? null;

            $userById = User::where('google_id', $googleId)->first();
            if ($userById) {
                if ($userById->email !== $email) {
                    throw new \Exception("google_id record exists but email mismatch");
                }
                Auth::login($userById);
                $user = $userById;
            } else {
                $userByEmail = User::where('email', $email)->first();

                if ($userByEmail) {
                    $userByEmail->update([
                        'google_id' => $googleId,
                    ]);
                    Auth::login($userByEmail);
                    $user = $userByEmail;
                } else {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'google_id' => $googleId,
                    ]);
                    Auth::login($user);
                }
            }
            $googleRefreshToken = $token['refresh_token'] ?? null;
            if ($googleRefreshToken) {
                // encryption set in Models\User.php
                $user->update(['google_refresh_token' => $googleRefreshToken]);
            }

            $tokenResult = $user->createToken('User Access Token');
            $userAccessToken = $tokenResult->accessToken;



            $frontendUrl = config('app.frontend_url');
            return redirect("{$frontendUrl}/login-success")
                ->cookie('access_token', $userAccessToken, 5, '/', null, true, true);
        } else {
            return response()->json(['error' => 'Authorization code not provided.'], 400);
        }
    }
}

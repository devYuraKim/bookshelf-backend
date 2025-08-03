<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use App\Models\User;

class AuthController extends Controller
{

    public function login(Request $request)
    {

        $data = [
            'grant_type' => 'password',
            'client_id' => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET'),
            'username' => $request->email,
            'password' => $request->password,
        ];

        $internalRequest = Request::create('/oauth/token', 'POST', $data);
        $response = app()->handle($internalRequest);
        $content = $response->getContent();
        $tokenData = json_decode($content, true);


        $credentials = $request->only('email', 'password');
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $user = Auth::user();

        if ($response->getStatusCode() !== 200 || !isset($tokenData['access_token'])) {
            Log::warning('Token request failed', [
                'status' => $response->getStatusCode(),
                'tokenData' => $tokenData,
            ]);

            return response()->json([
                'error' => 'Token request failed',
                'message' => $tokenData['message'] ?? 'Unexpected error during authentication',
            ], 401);
        }

        $refresh_token_time = config('auth.refresh_token_lifetime_days') * 24 * 60;

        return response()->json([
            'token_type' => $tokenData['token_type'],
            'access_token' => $tokenData['access_token'],
            'expires_in' => $tokenData['expires_in'],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ])->cookie(
            'refresh_token',
            $tokenData['refresh_token'],
            $refresh_token_time,
            '/',
            null,
            false,
            true
        );
    }

    public function logout(Request $request)
    {

        $userId = $request->user()->id;
        User::find($userId)->tokens()->each(function (Token $accessToken) {
            $accessToken->revoke();
            $accessToken->refreshToken?->revoke();
        });

        // access token id - refresh token id 확인용
        // $tokens = User::find($userId)->tokens()->get();
        // Log::info('tokens', ['data' => $tokens->map(function ($token) {
        // return [
        //     'access' => $token->id,
        //     'refresh' => $token->refreshToken?->id,
        // ];
        // }),]);

    }

    public function authRestore(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return response()->json(['error' => 'No refresh token'], 401);
        }

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET'),
        ];

        $internalTokenRequest = Request::create('/oauth/token', 'POST', $data);
        $internalTokenResponse = app()->handle($internalTokenRequest);
        $tokenContent = $internalTokenResponse->getContent();
        $tokenData = json_decode($tokenContent, true);

        Log::info('token_data: ', [$tokenData]);
        

        $internalUserRequest = Request::create('/api/user', 'GET');
        $internalUserRequest->headers->set('Authorization', 'Bearer ' . $tokenData['access_token']);
        $internalUserResponse = app()->handle($internalUserRequest);
        $userContent = $internalUserResponse->getContent();
        $userData = json_decode($userContent, true);

        if ($internalTokenResponse->getStatusCode() !== 200 || !isset($tokenData['access_token'])) {
            Log::warning('Token request failed', [
                'status' => $internalTokenResponse->getStatusCode(),
                'tokenData' => $tokenData,
            ]);

            return response()->json([
                'error' => 'Token request failed',
                'message' => $tokenData['message'] ?? 'Unexpected error during authentication',
            ], 401);
        }
        
        if ($internalUserResponse->getStatusCode() !== 200 || !isset($userData)) {
            Log::warning('User request failed', [
                'status' => $internalUserResponse->getStatusCode(),
                'userData' => $userData,
            ]);

            return response()->json([
                'error' => 'User request failed',
                'message' => $userData['message'] ?? 'Unexpected error during authentication',
            ], 401);
        }


        $refresh_token_minutes = config('auth.refresh_token_lifetime_days') * 24 * 60;

        return response()->json([
            'token_type' => $tokenData['token_type'],
            'access_token' => $tokenData['access_token'],
            'expires_in' => $tokenData['expires_in'],
            'user' => [
                'id' => $userData['user']['id'],
                'name' => $userData['user']['name'],
                'email' => $userData['user']['email'],
            ],
        ])->cookie(
            'refresh_token',
            $tokenData['refresh_token'],
            $refresh_token_minutes,
            '/',
            null,
            false,
            true
        );
    }

    public function user(Request $request) {
        return response()->json(['user' => $request->user()]);
    }
}

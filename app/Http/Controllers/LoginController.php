<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class LoginController extends Controller
{

    public function login(Request $request)
    {

        $data = [
            'grant_type' => 'password',
            'client_id' => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET'),
            'username' => $request->username,
            'password' => $request->password,
            'scope' => '*',
        ];

        $request = Request::create('/oauth/token', 'POST', $data);
        $response = app()->handle($request);
        $content = $response->getContent();
        $tokenData = json_decode($content, true);


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

    public function user(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
        ]);
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        if(!$refreshToken){
            return response()->json(['error'=>'No refresh token'], 401);
        }

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET'),
            'scope' => '*',
        ];

        $request = Request::create('/oauth/token', 'POST', $data);
        $response = app()->handle($request);
        $content = $response->getContent();
        $tokenData = json_decode($content, true);

        Log::info($tokenData);
        
    }
}

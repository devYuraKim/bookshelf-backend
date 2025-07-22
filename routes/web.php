<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Http;


Route::get('/oauth/authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');


Route::get('/', function () {
    return view('welcome');
});


Route::get('/redirect', function (Request $request) {
    $request->session()->put('state', $state = Str::random(40));

    $query = http_build_query([
        'client_id' => '0197cabc-4900-70ab-b7eb-f32b568df481',
        'redirect_uri' => '안가르쳐줘요',
        'response_type' => 'code',
        'scope' => '',
        'state' => $state,
        'prompt' => 'consent',
    ]);

    return redirect('http://localhost:8000/oauth/authorize?' . $query);
});

Route::get('/callback', function (Request $request) {
    
    $state = $request->session()->pull('state');

    throw_unless(
        strlen($state) > 0 && $state === $request->state,
        InvalidArgumentException::class,
        'Invalid state value.'
    );
    
    $tokenRequest = Request::create('/oauth/token', 'POST', [
        'grant_type' => 'authorization_code',
        'client_id' => '0197cabc-4900-70ab-b7eb-f32b568df481',
        'client_secret' => 'HPpTH3aXSnTnY0zyuctSPzxryenaJyPudDko3KsV',
        'redirect_uri' => 'http://localhost:8000/callback',
        'code' => $request->code,
    ]);
    
    // dd(vars: ['token_request'=>$tokenRequest]);

    $response = app()->handle($tokenRequest);
    dd($response);
    
    $data = json_decode($response->getContent(), true);

    return response()->json($data);
});

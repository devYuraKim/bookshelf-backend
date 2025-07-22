<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
public function authorize(Request $request)
    {
        // Step 1: Verify the 'state' parameter matches what's in the session
        $sessionState = $request->session()->get('state');
        $requestState = $request->input('state');

        if ($sessionState !== $requestState) {
            return redirect()->route('home')->withErrors('Invalid state parameter');
        }

        // Step 2: Handle the user's decision
        $user = auth()->user(); // Assuming user is logged in

        // Step 3: Generate the authorization code (this is the part that your app will use)
        $authorizationCode = Str::random(40); // In reality, store this in a database and associate with user

        // Step 4: Redirect to the client application with the authorization code
        $redirectUri = $request->input('redirect_uri');
        $redirectUri = rtrim($redirectUri, '?') . '?' . http_build_query([
            'code' => $authorizationCode,
            'state' => $sessionState,
        ]);

        return redirect($redirectUri);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PromptController extends Controller
{
        public function submitPrompt(Request $request)
    {
        $prompt = $request->prompt;

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . env('OPENAI_API_KEY')])->post('https://api.openai.com/v1/chat/completions', ['model' => 'gpt-4.1-nano', 'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]]);
        
        return response()->json([
            'prompt' => $prompt,
            'response'=> $response->json()
        ]);
    }
}

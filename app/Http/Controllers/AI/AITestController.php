<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\OpenAIService;
use Illuminate\Http\Request;

class AITestController extends Controller
{
    //
    public function test(OpenAIService $openAI)
    {
        $response = $openAI->chat([
            [
                'role' => 'system',
                'content' => 'You are a helpful financial assistant.'
            ],
            [
                'role' => 'user',
                'content' => 'Say hello to me.'
            ],
        ]);

        return response()->json([
            'message' => $response->choices[0]->message->content,
        ]);
    }
}

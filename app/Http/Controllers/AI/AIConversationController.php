<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AI\AIConversation;
use App\Models\AI\AIMessage;
use App\Services\AI\OpenAIService;
use Illuminate\Http\Request;

class AIConversationController extends Controller
{
    //
    public function sendMessage(Request $request, OpenAIService $openAI, $conversationId)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = $request->user();

        $conversation = AIConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'message' => 'Conversation not found'
            ], 404);
        }

        $userMessage = AIMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'message' => $request->message,
        ]);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();

        $openAIMessages = [
            [
                'role' => 'system',
                'content' => 'You are SanSom AI, a helpful personal finance assistant.'
            ]
        ];

        foreach ($messages as $message) {

            // OpenAI only accepts these roles for normal chat
            if (in_array($message->role, ['user', 'assistant', 'system'])) {

                $openAIMessages[] = [
                    'role' => $message->role,
                    'content' => $message->message,
                ];
            }
        }

        $response = $openAI->chat($openAIMessages);

        $aiMessage = $response->choices[0]->message->content;

        $assistantMessage = AIMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'message' => $aiMessage,
        ]);

        $conversation->update([
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'AI response generated successfully',

            'conversation_id' => $conversation->id,

            'user_message' => $userMessage,

            'assistant_message' => $assistantMessage,
        ]);
    }

    public function createConversation(Request $request)
    {
        $user = $request->user();

        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'title' => $request->title ?? 'New Conversation',
        ]);

        return response()->json([
            'message' => 'Conversation created successfully',
            'conversation' => $conversation,
        ], 201);
    }
}

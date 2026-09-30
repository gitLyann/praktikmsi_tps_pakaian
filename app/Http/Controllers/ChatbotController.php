<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chatbot;

class ChatbotController extends Controller
{
    public function message(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $userMessage = $request->input('message');

        $chatbot = Chatbot::where('queries', 'LIKE', "%{$userMessage}%")->first();

        if ($chatbot) {
            return response()->json([
                'reply' => $chatbot->replies,
                'matched' => true,
            ]);
        }

        return response()->json([
            'reply' => 'Maaf, sistem tidak mengerti jawaban yang harus diberikan. Silakan hubungi Customer Service kami.',
            'matched' => false,
        ]);
    }
}
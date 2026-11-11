<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use App\Models\ChatbotLog;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function message(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $userMessage = $request->input('message');

        $chatbot = Chatbot::where('queries', 'LIKE', "%{$userMessage}%")->first();

        $reply = $chatbot
            ? $chatbot->replies
            : 'Maaf, sistem tidak mengerti jawaban yang harus diberikan. Silakan hubungi Customer Service kami.';
        $matched = $chatbot !== null;

        // KMS: catat percakapan nyata sebagai bahan metrik Dashboard Eksekutif (ES).
        ChatbotLog::create([
            'user_id' => $request->user()?->id,
            'message' => $userMessage,
            'reply' => $reply,
            'matched' => $matched,
        ]);

        return response()->json([
            'reply' => $reply,
            'matched' => $matched,
        ]);
    }
}

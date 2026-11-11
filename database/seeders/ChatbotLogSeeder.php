<?php

namespace Database\Seeders;

use App\Models\Chatbot;
use App\Models\ChatbotLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Log percakapan chatbot demo (KMS) untuk metrik Dashboard Eksekutif (ES).
 * Idempoten, tidak menghapus data lama.
 */
class ChatbotLogSeeder extends Seeder
{
    public function run(): void
    {
        if (ChatbotLog::query()->count() >= 50) {
            $this->command?->info('Log percakapan chatbot sudah cukup, seeder dilewati.');

            return;
        }

        $faqs = Chatbot::query()->get();
        if ($faqs->isEmpty()) {
            return;
        }

        $users = User::query()->whereIn('role', ['pelanggan', 'staff', 'admin'])->get();
        $fallbackReply = 'Maaf, sistem tidak mengerti jawaban yang harus diberikan. Silakan hubungi Customer Service kami.';

        $total = 120;
        for ($i = 0; $i < $total; $i++) {
            $matched = ($i % 10) < 8; // sekitar 80% terjawab otomatis
            $faq = $faqs[$i % $faqs->count()];
            $variants = array_map('trim', array_filter(explode(',', (string) $faq->queries)));
            $question = $variants[$i % count($variants)] ?? '';
            $message = $matched && $question !== ''
                ? $question
                : 'pertanyaan demo yang tidak ada di basis FAQ #'.($i + 1);
            $at = Carbon::now()->subDays((int) floor($i * 90 / $total))->setTime(9 + ($i % 12), $i % 60, 0);

            ChatbotLog::query()->create([
                'user_id' => $users->isEmpty() ? null : $users[$i % $users->count()]->id,
                'message' => mb_substr($message, 0, 500),
                'reply' => $matched ? (string) $faq->replies : $fallbackReply,
                'matched' => $matched,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }

        $this->command?->info('ChatbotLogSeeder: '.$total.' percakapan demo (idempoten).');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Chatbot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_matched_message_is_logged_with_user(): void
    {
        $user = User::factory()->create(['role' => 'pelanggan']);
        Chatbot::create([
            'queries' => 'jam buka,jam operasional',
            'replies' => 'Toko buka pukul 09:00 - 21:00 WIB.',
        ]);

        $this->actingAs($user)
            ->postJson(route('chatbot.message'), ['message' => 'jam buka'])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('reply', 'Toko buka pukul 09:00 - 21:00 WIB.');

        $this->assertDatabaseHas('chatbot_logs', [
            'user_id' => $user->id,
            'message' => 'jam buka',
            'reply' => 'Toko buka pukul 09:00 - 21:00 WIB.',
            'matched' => true,
        ]);
    }

    public function test_unmatched_message_is_still_logged_with_fallback(): void
    {
        $user = User::factory()->create(['role' => 'pelanggan']);

        $this->actingAs($user)
            ->postJson(route('chatbot.message'), ['message' => 'zzz pertanyaan unik zzz'])
            ->assertOk()
            ->assertJsonPath('matched', false);

        $this->assertDatabaseHas('chatbot_logs', [
            'user_id' => $user->id,
            'message' => 'zzz pertanyaan unik zzz',
            'matched' => false,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('chatbot.message'), ['message' => 'jam buka'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('chatbot_logs', 0);
    }
}

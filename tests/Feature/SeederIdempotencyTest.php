<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EsDemoRestockSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_dapat_dijalankan_ulang(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'test@example.com')->count());
    }

    public function test_es_demo_restock_seeder_dapat_dijalankan_ulang(): void
    {
        Product::factory()->count(4)->create();

        $staff = User::factory()->create(['role' => 'staff']);
        $staff->employee()->create([
            'name' => $staff->name,
            'position' => 'Kasir',
        ]);

        $this->seed(EsDemoRestockSeeder::class);
        $first = RestockRequest::query()->where('catatan', 'Demo ES/OAS')->count();

        $this->seed(EsDemoRestockSeeder::class);
        $second = RestockRequest::query()->where('catatan', 'Demo ES/OAS')->count();

        $this->assertSame(6, $first);
        $this->assertSame($first, $second);
    }
}

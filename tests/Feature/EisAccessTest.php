<?php

namespace Tests\Feature;

use App\Models\EisCriterion;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use App\Services\EisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EisAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (config('eis.criteria') as $definition) {
            EisCriterion::query()->create([
                'code' => $definition['code'],
                'name' => $definition['name'],
                'type' => $definition['type'],
                'weight' => $definition['weight'],
            ]);
        }
    }

    private function customerIdFor(User $user): int
    {
        return (int) (DB::table('customers')->where('user_id', $user->id)->value('id')
            ?? DB::table('customers')->insertGetId([
                'user_id' => $user->id,
                'name' => $user->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
    }

    private function makeStaff(string $name): array
    {
        $user = User::factory()->create(['role' => 'staff', 'name' => $name]);

        return [$user, Employee::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'position' => 'Kasir',
        ])];
    }

    public function test_admin_can_access_eis_pages_and_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.eis'))
            ->assertOk()
            ->assertSee('EIS Kinerja Staf')
            ->assertSee('Profil');
        $this->actingAs($admin)->getJson(route('admin.eis.data', ['name' => 'summary']))
            ->assertOk()
            ->assertJsonStructure(['period', 'active_staff', 'avg_target_achievement', 'open_exceptions']);
        $this->actingAs($admin)->getJson(route('admin.eis.data', ['name' => 'ranking']))
            ->assertOk()
            ->assertJsonStructure(['rows']);
    }

    public function test_staff_only_receives_own_performance(): void
    {
        [$staffA, $employeeA] = $this->makeStaff('Staf Alpha');
        [$staffB, $employeeB] = $this->makeStaff('Staf Beta');
        $customer = User::factory()->create(['role' => 'pelanggan']);
        $period = now()->format('Y-m');

        foreach ([[$employeeA, 250000], [$employeeB, 900000]] as [$employee, $total]) {
            Transaction::unguarded(fn () => Transaction::create([
                'customer_id' => $this->customerIdFor($customer),
                'employee_id' => $employee->id,
                'date' => now()->subDays(1),
                'total' => $total,
                'payment_method' => 'EIS Access Test',
                'status' => 'completed',
            ]));
        }

        app(EisService::class)->buildSnapshots($period);

        $this->actingAs($staffA)->get(route('staff.eis.index'))
            ->assertOk()
            ->assertSee('Kinerja Saya')
            ->assertDontSee('Staf Beta');

        $response = $this->actingAs($staffA)->getJson(route('staff.eis.data', [
            'name' => 'trend',
            'period' => $period,
        ]))->assertOk();

        $this->assertCount(1, $response->json('datasets'));
        $this->assertSame('Staf Alpha', $response->json('datasets.0.name'));
        $this->assertStringNotContainsString('Staf Beta', json_encode($response->json()));
    }

    public function test_unauthorized_roles_and_guests_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $this->actingAs($staff)->get(route('admin.eis'))->assertStatus(403);
        $this->actingAs($pelanggan)->getJson(route('admin.eis.data', ['name' => 'summary']))->assertStatus(403);
        $this->actingAs($pelanggan)->get(route('staff.eis.index'))->assertStatus(403);
        $this->actingAs($admin)->get(route('staff.eis.index'))->assertStatus(403);

        auth()->logout();
        $this->get(route('admin.eis'))->assertRedirect(route('login'));
        $this->get(route('staff.eis.index'))->assertRedirect(route('login'));
        $this->getJson(route('admin.eis.data', ['name' => 'summary']))->assertStatus(401);
        $this->getJson(route('staff.eis.data', ['name' => 'trend']))->assertStatus(401);
    }
}

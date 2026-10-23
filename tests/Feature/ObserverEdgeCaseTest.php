<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ObserverEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $username, string $role): User
    {
        return User::forceCreate([
            'username' => $username,
            'password' => 'rahasia123',
            'nama_lengkap' => ucfirst($username),
            'role' => $role,
        ]);
    }

    public function test_bulk_update_tidak_memicu_observer(): void
    {
        Log::spy();
        $customer = $this->user('cust', 'customer');

        // Query builder: tidak memicu event model per baris.
        User::whereKey($customer->id)->update(['role' => 'staff']);

        $this->assertSame('staff', $customer->fresh()->role, 'DB tetap berubah.');
        Log::shouldNotHaveReceived('info', ['user.role.changed', Mockery::any()]);
    }

    public function test_save_melalui_instance_memicu_observer(): void
    {
        Log::spy();
        $customer = $this->user('cust', 'customer');

        $customer->role = 'staff';
        $customer->save();

        Log::shouldHaveReceived('info')
            ->once()
            ->with('user.role.changed', Mockery::on(
                fn (array $ctx): bool => $ctx['target_user_id'] === $customer->id
                    && $ctx['old_role'] === 'customer'
                    && $ctx['new_role'] === 'staff'
                    && $ctx['actor_id'] === null
            ));
    }

    public function test_log_tidak_memuat_data_sensitif(): void
    {
        Log::spy();
        $customer = $this->user('cust', 'customer');

        $customer->role = 'staff';
        $customer->save();

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            $haystack = strtolower(json_encode($context));

            return $message === 'user.role.changed'
                && array_keys($context) === ['target_user_id', 'old_role', 'new_role', 'actor_id']
                && ! str_contains($haystack, 'password')
                && ! str_contains($haystack, '$2y$')
                && ! str_contains($haystack, 'token')
                && ! str_contains($haystack, 'cart');
        });
    }

    public function test_pendaftaran_observer_tidak_ganda(): void
    {
        Log::spy();
        $customer = $this->user('cust', 'customer');

        $customer->role = 'staff';
        $customer->save();

        // Persis satu log, bukan dua.
        Log::shouldHaveReceived('info')->once()->with('user.role.changed', Mockery::any());
    }

    public function test_observer_tidak_menggantikan_gate_request_ditolak_tidak_menyentuh_db(): void
    {
        Log::spy();
        $customer = $this->user('cust', 'customer');
        $target = $this->user('target', 'customer');
        $before = DB::table('users')->where('id', $target->id)->first();

        $this->actingAs($customer)
            ->patch(route('admin.users.role.update', $target), ['role' => 'staff'])
            ->assertForbidden();

        $after = DB::table('users')->where('id', $target->id)->first();

        $this->assertEquals($before, $after);
        Log::shouldNotHaveReceived('info', ['user.role.changed', Mockery::any()]);
    }
}

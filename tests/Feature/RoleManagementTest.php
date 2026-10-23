<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleManagementTest extends TestCase
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

    public function test_br01_user_baru_otomatis_customer(): void
    {
        $user = User::create([
            'username' => 'baru',
            'password' => 'rahasia123',
            'nama_lengkap' => 'User Baru',
        ]);

        $this->assertSame('customer', $user->fresh()->role);
    }

    public function test_role_tidak_bisa_diisi_mass_assignment(): void
    {
        $user = User::create([
            'username' => 'nakal',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Nakal',
            'role' => 'admin',
        ]);

        $this->assertSame('customer', $user->fresh()->role);
    }

    public function test_gate_ability_matrix(): void
    {
        $customer = $this->user('cust', 'customer');
        $staff = $this->user('staf', 'staff');
        $admin = $this->user('adm', 'admin');

        // Gate tidak bergantung user login: evaluasi langsung per user.
        foreach ([$customer, $staff, $admin] as $u) {
            $this->assertSame($u->role === 'admin', Gate::forUser($u)->allows('manage-roles'));
            $this->assertSame($u->role === 'admin', Gate::forUser($u)->allows('manage-products'));
            $this->assertSame(in_array($u->role, ['staff', 'admin'], true), Gate::forUser($u)->allows('adjust-stock'));
            $this->assertSame($u->role === 'customer', Gate::forUser($u)->allows('use-cart'));
        }
    }

    public function test_ac01_admin_mengubah_customer_menjadi_staff(): void
    {
        $admin = $this->user('adm', 'admin');
        $customer = $this->user('cust', 'customer');

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $customer), ['role' => 'staff'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('staff', $customer->fresh()->role);
    }

    public function test_ac02_customer_403(): void
    {
        $customer = $this->user('cust', 'customer');
        $target = $this->user('target', 'customer');

        $this->actingAs($customer)
            ->patch(route('admin.users.role.update', $target), ['role' => 'staff'])
            ->assertForbidden();

        $this->assertSame('customer', $target->fresh()->role);
    }

    public function test_ac02_guest_dialihkan_ke_login(): void
    {
        $target = $this->user('target', 'customer');

        $this->patch(route('admin.users.role.update', $target), ['role' => 'staff'])
            ->assertRedirect(route('login'));

        $this->assertSame('customer', $target->fresh()->role);
    }

    public function test_ac03_admin_tidak_bisa_ubah_dirinya(): void
    {
        $admin = $this->user('adm', 'admin');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.role.update', $admin), ['role' => 'customer'])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_tidak_bisa_ubah_admin_lain(): void
    {
        $admin = $this->user('adm', 'admin');
        $adminLain = $this->user('adm2', 'admin');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.role.update', $adminLain), ['role' => 'customer'])
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $adminLain->fresh()->role);
    }

    public function test_ac04_role_tidak_valid_gagal_validasi(): void
    {
        $admin = $this->user('adm', 'admin');
        $customer = $this->user('cust', 'customer');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.role.update', $customer), ['role' => 'admin'])
            ->assertSessionHasErrors('role');

        $this->assertSame('customer', $customer->fresh()->role);
    }

    public function test_ac05_role_sama_tidak_menyimpan_ulang(): void
    {
        $admin = $this->user('adm', 'admin');
        $staff = $this->user('staf', 'staff');
        $staff->updated_at = now()->subDay();
        $staff->timestamps = false;
        $staff->save();
        $staff->timestamps = true;
        $before = $staff->fresh()->updated_at;

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $staff), ['role' => 'staff'])
            ->assertSessionHas('status');

        $this->assertSame($before->toDateTimeString(), $staff->fresh()->updated_at->toDateTimeString());
    }

    public function test_ac06_staff_dan_admin_tidak_bisa_cart_add(): void
    {
        foreach (['staff', 'admin'] as $role) {
            $this->actingAs($this->user($role.'x', $role))
                ->postJson(route('cart.add'), ['cart' => [], 'product_id' => 1])
                ->assertForbidden();
        }
    }

    public function test_customer_dan_guest_pada_cart_add(): void
    {
        $customer = $this->user('cust', 'customer');

        $this->actingAs($customer)
            ->postJson(route('cart.add'), ['cart' => ['2' => 1], 'product_id' => 1])
            ->assertOk()
            ->assertJsonPath('total', 8000)
            ->assertJsonCount(2, 'items');

        // Guest tanpa login -> 401 (bukan redirect) karena request JSON.
        $this->app['auth']->forgetGuards();

        $this->postJson(route('cart.add'), ['cart' => [], 'product_id' => 1])
            ->assertUnauthorized();
    }
}

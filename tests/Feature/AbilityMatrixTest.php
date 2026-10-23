<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbilityMatrixTest extends TestCase
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

    public function test_lihat_katalog_boleh_semua_role(): void
    {
        $this->get(route('shop'))->assertOk();
        $this->get(route('api.products'))->assertOk();

        foreach (['customer', 'staff', 'admin'] as $role) {
            $this->actingAs($this->user($role, $role))
                ->get(route('shop'))
                ->assertOk();
        }
    }

    public function test_lihat_stok_hanya_staff_dan_admin(): void
    {
        $this->get(route('staff.stock'))->assertRedirect(route('login'));

        $this->actingAs($this->user('cust', 'customer'))
            ->get(route('staff.stock'))
            ->assertForbidden();

        foreach (['staff', 'admin'] as $role) {
            $this->actingAs($this->user($role, $role))
                ->get(route('staff.stock'))
                ->assertOk()
                ->assertJsonPath('viewer_role', $role);
        }
    }

    public function test_kelola_katalog_hanya_admin(): void
    {
        $this->actingAs($this->user('cust', 'customer'))
            ->get(route('admin.products'))
            ->assertForbidden();

        $this->actingAs($this->user('staf', 'staff'))
            ->get(route('admin.products'))
            ->assertForbidden();

        $this->actingAs($this->user('adm', 'admin'))
            ->get(route('admin.products'))
            ->assertOk()
            ->assertJsonPath('abilities.manage-products', true);
    }

    public function test_ubah_role_hanya_admin(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));

        $this->actingAs($this->user('cust', 'customer'))
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($this->user('adm', 'admin'))
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_tambah_keranjang_hanya_customer(): void
    {
        $payload = ['cart' => [], 'product_id' => 1];

        $this->postJson(route('cart.add'), $payload)->assertUnauthorized();

        $this->actingAs($this->user('staf', 'staff'))
            ->postJson(route('cart.add'), $payload)
            ->assertForbidden();

        $this->actingAs($this->user('adm', 'admin'))
            ->postJson(route('cart.add'), $payload)
            ->assertForbidden();

        $this->actingAs($this->user('cust', 'customer'))
            ->postJson(route('cart.add'), $payload)
            ->assertOk()
            ->assertJsonPath('total', 5000);
    }

    public function test_ac06_customer_menjadi_staff_payload_cart_tetap_ditolak(): void
    {
        $customer = $this->user('cust', 'customer');
        $payload = ['cart' => ['1' => 1], 'product_id' => 1];

        // Sebelum perubahan role: payload sama, diizinkan.
        $this->actingAs($customer)
            ->postJson(route('cart.add'), $payload)
            ->assertOk()
            ->assertJsonPath('total', 10000);

        // Admin mengubah role lewat service (jalur resmi).
        $admin = $this->user('adm', 'admin');
        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $customer), ['role' => 'staff'])
            ->assertRedirect();

        // Session customer yang sama, payload JSON yang sama: kini ditolak Gate.
        $this->actingAs($customer->fresh())
            ->postJson(route('cart.add'), $payload)
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_gate_tidak_membaca_isi_cart_dari_payload(): void
    {
        // Payload "nakal" tetap tidak memberi akses; keputusan hanya dari role di DB.
        $this->actingAs($this->user('staff', 'staff'))
            ->postJson(route('cart.add'), ['cart' => ['1' => 99], 'product_id' => 1, 'role' => 'customer'])
            ->assertForbidden();
    }
}

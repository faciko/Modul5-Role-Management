<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaselineW05Test extends TestCase
{
    use RefreshDatabase;

    public function test_login_berhasil_ke_dashboard(): void
    {
        User::forceCreate([
            'username' => 'Salman',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Salman Mahesa Darmawan',
            'role' => 'customer',
        ]);

        $this->post('/login', ['username' => 'Salman', 'password' => 'rahasia123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->get('/dashboard')->assertOk()->assertSee('Salman Mahesa Darmawan');
    }

    public function test_login_gagal_tidak_membocorkan_mana_yang_salah(): void
    {
        $this->post('/login', ['username' => 'tidakada', 'password' => 'salah'])
            ->assertSessionHasErrors('login_error');

        $this->assertGuest();
    }

    public function test_logout_mengakhiri_session(): void
    {
        $user = User::forceCreate([
            'username' => 'Kevin',
            'password' => 'password123',
            'nama_lengkap' => 'Kevin',
            'role' => 'customer',
        ]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_dashboard_tidak_bisa_dibuka_tanpa_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_api_login_json(): void
    {
        User::forceCreate([
            'username' => 'admin',
            'password' => 'admin12345',
            'nama_lengkap' => 'Administrator',
            'role' => 'admin',
        ]);

        $this->postJson(route('api.login'), ['username' => 'admin', 'password' => 'salah'])
            ->assertStatus(422);

        $this->postJson(route('api.login'), ['username' => 'admin', 'password' => 'admin12345'])
            ->assertOk()
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('abilities.manage-roles', true)
            ->assertJsonPath('abilities.use-cart', false);
    }

    public function test_api_me_menolak_guest(): void
    {
        $this->getJson(route('api.me'))->assertUnauthorized();
    }
}

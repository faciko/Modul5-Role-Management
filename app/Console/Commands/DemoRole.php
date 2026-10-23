<?php

namespace App\Console\Commands;

use App\Http\Requests\ChangeRoleRequest;
use App\Models\User;
use App\Services\RoleManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DemoRole extends Command
{
    protected $signature = 'demo:role';

    protected $description = 'Jalankan skenario bukti Modul 5 (AC-01..AC-06) beserta perubahan DB dan log Observer';

    public function handle(RoleManagementService $service): int
    {
        $admin = User::where('role', 'admin')->first();
        $customer = User::where('role', 'customer')->orderBy('id')->first();
        $staff = User::where('role', 'staff')->first();

        if (! $admin || ! $customer || ! $staff) {
            $this->error('Akun demo belum lengkap. Jalankan: php artisan db:seed');

            return self::FAILURE;
        }

        $originalRole = $customer->role;
        $this->line('Akun: admin='.$admin->username.', customer='.$customer->username.', staff='.$staff->username);
        $this->newLine();

        $this->scenario('AC-01 admin mengubah customer menjadi staff', function () use ($service, $admin, $customer) {
            $before = $this->snapshot($customer);
            $this->loginAs($admin);
            $service->changeRole($admin, $customer, 'staff');
            $this->report($before, $customer, ['respons' => 'sukses, redirect']);
        });

        $this->scenario('AC-05 admin mengirim role yang sama lagi', function () use ($service, $admin, $customer) {
            $before = $this->snapshot($customer);
            $service->changeRole($admin, $customer, 'staff');
            $this->report($before, $customer, ['respons' => 'sukses tanpa update']);
        });

        $this->scenario('AC-04 admin mengirim role tidak valid (admin) — ditolak Form Request', function () use ($customer) {
            $before = $this->snapshot($customer);
            $request = new ChangeRoleRequest;
            $validator = validator(['role' => 'admin'], $request->rules());

            $this->report($before, $customer, [
                'respons' => $validator->fails()
                    ? 'gagal validasi: '.$validator->errors()->first('role')
                    : 'LOLOS (seharusnya gagal)',
            ]);
        });

        $this->scenario('AC-03 admin mengubah role dirinya sendiri — ditolak service', function () use ($service, $admin) {
            $before = $this->snapshot($admin);
            $this->loginAs($admin);

            try {
                $service->changeRole($admin, $admin, 'customer');
                $respons = 'LOLOS (seharusnya ditolak)';
            } catch (ValidationException $e) {
                $respons = 'error aturan bisnis: '.$e->validator->errors()->first('role');
            }

            $this->report($before, $admin, ['respons' => $respons]);
        });

        $this->scenario('AC-02 customer mengakses endpoint admin — ditolak Gate', function () use ($customer) {
            $before = $this->snapshot($customer);
            $this->loginAs($customer);
            $this->report($before, $customer, [
                'respons' => $this->gate($customer, 'manage-roles')
                    ? 'LOLOS (seharusnya 403)'
                    : '403 Forbidden',
            ]);
        });

        $this->scenario('AC-06 staff memanggil POST /cart/add — ditolak Gate use-cart', function () use ($staff) {
            $this->loginAs($staff);
            $this->report(null, null, [
                'respons' => $this->gate($staff, 'use-cart')
                    ? 'LOLOS (seharusnya 403)'
                    : '403 Forbidden (role di DB dibaca, payload cart tidak dipercaya)',
            ]);
        });

        $this->scenario('AC-06 customer memanggil POST /cart/add — diizinkan', function () {
            $customer = User::where('role', 'customer')->orderBy('id')->first();
            $this->loginAs($customer);
            $this->report(null, null, ['respons' => $this->gate($customer, 'use-cart') ? '200 OK' : '403 (role bukan customer)']);
        });

        $this->scenario('Independent challenge: save() vs bulk update', function () use ($staff) {
            $before = $this->snapshot($staff);

            $staff->role = 'customer';
            $staff->save();
            $this->report($before, $staff, ['respons' => 'save(): log WAJIB muncul']);

            $before = $this->snapshot($staff);
            User::where('id', $staff->id)->update(['role' => 'staff']);
            $this->report($before, $staff, ['respons' => 'bulk update: log TIDAK muncul, DB tetap berubah']);
            // Pulihkan role staff setelah eksperimen bulk update.
            User::where('id', $staff->id)->update(['role' => 'staff']);
        });

        // Pulihkan data.
        Auth::logout();
        DB::table('users')->where('id', $customer->id)->update(['role' => $originalRole]);
        $this->newLine();
        $this->info('Data dipulihkan: '.$customer->username.' -> '.$originalRole);

        return self::SUCCESS;
    }

    private function scenario(string $title, callable $run): void
    {
        $this->line('<options=bold>'.$title.'</>');
        $logsBefore = $this->logCount();
        $run();
        $this->line(sprintf('    log user.role.changed: %d -> %d',
            $logsBefore, $this->logCount()));
        $this->newLine();
    }

    private function report(?array $before, ?User $user, array $extra): void
    {
        if ($user !== null) {
            $after = $this->snapshot($user);
            $this->line('    DB role: '.$before['role'].' -> '.$after['role']
                .'  |  updated_at berubah: '.($before['updated_at'] !== $after['updated_at'] ? 'ya' : 'tidak'));
        }

        foreach ($extra as $key => $value) {
            $this->line('    '.$key.': '.$value);
        }
    }

    private function snapshot(User $user): array
    {
        $row = DB::table('users')->where('id', $user->id)->first();

        return ['role' => $row->role, 'updated_at' => $row->updated_at];
    }

    private function gate(User $user, string $ability): bool
    {
        return Gate::forUser($user)->allows($ability);
    }

    private function loginAs(?User $user): void
    {
        if ($user === null) {
            return;
        }

        Auth::login($user);
    }

    private function logCount(): int
    {
        $count = 0;
        $files = glob(storage_path('logs/*.log')) ?: [];

        foreach ($files as $file) {
            $count += substr_count((string) file_get_contents($file), 'user.role.changed');
        }

        return $count;
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Role admin awal ditetapkan di sini (jalur tepercaya), bukan lewat form.
     */
    public function run(): void
    {
        // Idempoten: akun W05 yang sudah ada tidak diduplikasi, role lama tetap.
        $accounts = [
            // User dari W05 (role default: customer — BR-01).
            ['username' => 'Salman', 'password' => 'rahasia123', 'nama_lengkap' => 'Salman Mahesa Darmawan', 'role' => 'customer'],
            ['username' => 'Kevin', 'password' => 'password123', 'nama_lengkap' => 'Kevin Alfarisi Haikalt', 'role' => 'customer'],

            // Akun admin awal.
            ['username' => 'admin', 'password' => 'admin12345', 'nama_lengkap' => 'Administrator Toko', 'role' => 'admin'],

            // Akun staff contoh.
            ['username' => 'staff', 'password' => 'staff12345', 'nama_lengkap' => 'Staff Gudang', 'role' => 'staff'],
        ];

        foreach ($accounts as $account) {
            if (User::where('username', $account['username'])->exists()) {
                continue;
            }

            User::create([
                'username' => $account['username'],
                'password' => Hash::make($account['password']),
                'nama_lengkap' => $account['nama_lengkap'],
                'role' => $account['role'],
            ]);
        }
    }
}

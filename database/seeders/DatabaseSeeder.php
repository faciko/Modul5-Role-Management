<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        // User dari W05 (role default: customer — BR-01).
        User::create([
            'username' => 'Salman',
            'password' => Hash::make('rahasia123'),
            'nama_lengkap' => 'Salman Mahesa Darmawan',
            'role' => 'customer',
        ]);

        User::create([
            'username' => 'Kevin',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kevin Alfarisi Haikalt',
            'role' => 'customer',
        ]);

        // Akun admin awal.
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin12345'),
            'nama_lengkap' => 'Administrator Toko',
            'role' => 'admin',
        ]);

        // Akun staff contoh.
        User::create([
            'username' => 'staff',
            'password' => Hash::make('staff12345'),
            'nama_lengkap' => 'Staff Gudang',
            'role' => 'staff',
        ]);
    }
}

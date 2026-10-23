<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['customer', 'staff', 'admin'];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Role sengaja TIDAK ada di $fillable: role tidak boleh berasal dari
     * request publik (registrasi/form). Ubah lewat property assignment + save().
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class RoleManagementService
{
    /**
     * Dipanggil hanya dari route yang sudah diotorisasi Gate dan Form Request
     * yang sudah memvalidasi nilai role.
     */
    public function changeRole(
        User $actor, User $target, string $newRole
    ): void {
        // BR-04: admin tidak boleh mengubah dirinya sendiri / akun admin lain.
        if ($actor->is($target) || $target->role === 'admin') {
            throw ValidationException::withMessages([
                'role' => 'Akun admin tidak dapat diubah di fitur ini.',
            ]);
        }

        // BR-05: role sama berarti tidak ada yang perlu disimpan.
        if ($target->role === $newRole) {
            return;
        }

        $target->role = $newRole;
        $target->save();
    }
}

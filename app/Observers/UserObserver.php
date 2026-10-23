<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Observer hanya MENCATAT perubahan yang sudah terjadi.
     * Observer bukan penjaga izin — aturan bisnis ada di service/Gate.
     */
    public function updated(User $user): void
    {
        if (! $user->wasChanged('role')) return;

        Log::info('user.role.changed', [
            'target_user_id' => $user->id,
            'old_role' => $user->getRawOriginal('role'),
            'new_role' => $user->role,
            'actor_id' => Auth::id(), // null bila dari seeder/CLI
        ]);
    }
}

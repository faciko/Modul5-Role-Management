<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthMeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'role' => $user->role,
            ],
            'abilities' => [
                'manage-roles' => $user->can('manage-roles'),
                'manage-products' => $user->can('manage-products'),
                'adjust-stock' => $user->can('adjust-stock'),
                'use-cart' => $user->can('use-cart'),
            ],
        ]);
    }
}

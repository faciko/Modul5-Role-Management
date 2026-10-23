<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DemoController extends Controller
{
    private const PRODUCTS = [
        ['id' => 1, 'name' => 'Buku Tulis', 'price' => 5000],
        ['id' => 2, 'name' => 'Pulpen', 'price' => 3000],
        ['id' => 3, 'name' => 'Penggaris', 'price' => 4000],
    ];

    /**
     * Katalog publik (semua role boleh melihat — BR matrix baris pertama).
     */
    public function shop()
    {
        return view('shop');
    }

    public function products(): JsonResponse
    {
        return response()->json([
            'stok_dilihat_dari' => auth()->check()
                ? (auth()->user()->can('adjust-stock') ? 'stok terlihat' : 'stok tidak terlihat')
                : 'guest',
            'products' => self::PRODUCTS,
        ]);
    }

    /**
     * Halaman stok: staff + admin (Gate adjust-stock) — menghasilkan 403 untuk customer.
     */
    public function stock(): JsonResponse
    {
        return response()->json([
            'viewer_role' => auth()->user()->role,
            'products' => array_map(
                fn (array $p): array => $p + ['stok' => 25],
                self::PRODUCTS
            ),
        ]);
    }

    /**
     * Halaman pengelolaan katalog: admin saja (Gate manage-products).
     */
    public function manageProducts(Request $request): JsonResponse
    {
        abort_unless(Gate::allows('manage-products'), 403);

        return response()->json([
            'message' => 'Halaman kelola katalog (admin).',
            'products' => self::PRODUCTS,
            'abilities' => [
                'manage-products' => $request->user()->can('manage-products'),
                'manage-roles' => $request->user()->can('manage-roles'),
            ],
        ]);
    }
}

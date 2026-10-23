<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Route simulasi POST /cart/add dari W06 (Stateless Cart).
 *
 * Isi cart tetap berasal dari payload request (server tidak menyimpan cart),
 * sedangkan status login berasal dari session. Implementasi asli W06 berada
 * di repository terpisah; lihat INTEGRASI-W06.md untuk rencana integrasi.
 */
class CartSimulationController extends Controller
{
    private array $products = [
        1 => ['name' => 'Buku Tulis', 'price' => 5000],
        2 => ['name' => 'Pulpen',     'price' => 3000],
        3 => ['name' => 'Penggaris',  'price' => 4000],
    ];

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cart' => ['present', 'array'],
            'cart.*' => ['integer', 'min:1', 'max:99'],
            'product_id' => ['required', 'integer'],
        ]);

        $cart = $data['cart'];
        $id = (int) $data['product_id'];

        if (! isset($this->products[$id])) {
            return response()->json(['message' => 'Produk tidak ditemukan.'], 404);
        }

        $cart[$id] = min(99, ($cart[$id] ?? 0) + 1);

        return response()->json($this->calculate($cart));
    }

    private function calculate(array $cart): array
    {
        $items = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            if (! isset($this->products[$id])) {
                continue;
            }

            $product = $this->products[$id];
            $subtotal = $product['price'] * $quantity;

            $items[] = [
                'id' => (int) $id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }

        return ['items' => $items, 'total' => $total];
    }
}

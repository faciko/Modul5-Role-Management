<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membungkus respons request AJAX/JSON (fetch dari halaman /shop):
 * - Accept: application/json, supaya pelanggaran Gate/auth menjadi 403/401,
 *   bukan redirect HTML;
 * - header X-CSRF-Token berisi token session terkini pada SETIAP respons;
 * - token itu juga diterima sebagai pengganti X-CSRF-TOKEN pada request
 *   berikutnya, sehingga halaman tidak pernah memakai token basi setelah
 *   session diregenerasi (login/logout).
 */
class ForceJsonForAjax
{
    public function handle(Request $request, Closure $next): Response
    {
        $wantsJson = $request->isJson() || $request->ajax();

        if ($wantsJson) {
            $request->headers->set('Accept', 'application/json');

            if (! $request->headers->has('X-CSRF-TOKEN') && $request->headers->has('X-CSRF-Token')) {
                $request->headers->set('X-CSRF-TOKEN', $request->headers->get('X-CSRF-Token'));
            }
        }

        $response = $next($request);
        $token = (string) ($request->hasSession() ? $request->session()->token() : '');

        // Normalisasi respons error non-JSON menjadi JSON.
        if ($wantsJson && in_array($response->getStatusCode(), [401, 403, 419], true)
            && ! $response instanceof JsonResponse) {
            $status = $response->getStatusCode();
            $message = match ($status) {
                403 => 'This action is unauthorized.',
                419 => 'CSRF token mismatch.',
                default => 'Unauthenticated.',
            };

            $response = response()->json(['message' => $message], $status);
        }

        if ($wantsJson && $token !== '') {
            $response->headers->set('X-CSRF-Token', $token);
        }

        return $response;
    }
}

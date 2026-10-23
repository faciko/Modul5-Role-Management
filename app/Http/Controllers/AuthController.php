<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    // GET /login
    public function showLogin(): View
    {
        return view('login');
    }

    // POST /login
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            // Mencegah session fixation
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        // Pesan error umum tanpa membocorkan username/password mana yang salah
        return back()->withErrors([
            'login_error' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    // POST /login untuk fetch W06 (menerima JSON, mengembalikan JSON)
    public function apiLogin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Username atau password salah.'], 422);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => [
                'username' => $request->user()->username,
                'nama_lengkap' => $request->user()->nama_lengkap,
                'role' => $request->user()->role,
            ],
            'abilities' => [
                'manage-roles' => $request->user()->can('manage-roles'),
                'manage-products' => $request->user()->can('manage-products'),
                'adjust-stock' => $request->user()->can('adjust-stock'),
                'use-cart' => $request->user()->can('use-cart'),
            ],
        ]);
    }

    // GET /dashboard
    public function dashboard(): View|RedirectResponse
    {
        if (request()->expectsJson() || request()->ajax()) {
            return redirect('/dashboard');
        }

        return view('dashboard', [
            'user' => Auth::user(),
        ]);
    }

    // POST /logout
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

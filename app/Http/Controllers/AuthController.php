<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // GET /login
    public function showLogin()
    {
        return view('login');
    }
    // POST /login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
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
    // GET /dashboard
    public function dashboard()
    {
        return view('dashboard', [
            'user' => Auth::user(),
        ]);
    }
    // POST /logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}

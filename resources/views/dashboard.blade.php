<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f9f9f9; }
        .box { border: 1px solid #333; padding: 25px; width: 420px; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 15px; }
        .header h2 { margin: 0; }
        button.logout { background: none; border: none; color: #0066cc; text-decoration: underline; cursor: pointer; padding: 0; font-size: 14px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; background: #eef; }
        .muted { color: #666; font-size: 13px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="header">
            <h2>Dashboard</h2>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="logout">[ Logout ]</button>
            </form>
        </div>
        <p><strong>Selamat datang, {{ $user->nama_lengkap }}!</strong></p>
        <p>Halaman ini hanya bisa dibuka setelah login.</p>
        <p class="muted">Username: {{ $user->username }}</p>
        <p class="muted">Role: <span class="badge">{{ $user->role }}</span></p>

        @can('manage-roles')
            <p><a href="{{ route('admin.users.index') }}">Kelola Role Pengguna</a></p>
        @endcan

        @can('manage-products')
            <p><a href="{{ route('admin.products') }}">Kelola Katalog</a></p>
        @endcan

        @can('adjust-stock')
            <p><a href="{{ route('staff.stock') }}">Lihat Stok</a></p>
        @endcan

        @can('use-cart')
            <p><a href="{{ route('shop') }}">Buka Toko (Keranjang)</a></p>
        @endcan
    </div>
</body>
</html>

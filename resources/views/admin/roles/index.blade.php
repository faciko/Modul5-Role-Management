<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Role</title>
    <style>
        body { font-family: sans-serif; margin: 0; background: #f9f9f9; }
        .wrap { max-width: 900px; margin: 30px auto; background: #fff; border: 1px solid #333; padding: 25px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 15px; }
        .header h2 { margin: 0; }
        button.logout { background: none; border: none; color: #0066cc; text-decoration: underline; cursor: pointer; padding: 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: middle; }
        th { background: #f2f2f2; }
        select { padding: 4px; }
        button.save { padding: 4px 12px; cursor: pointer; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; background: #eef; }
        .ok { color: #0a7a2f; background: #e8f8ee; border: 1px solid #b7e4c7; padding: 8px; margin-bottom: 12px; font-size: 14px; }
        .error { color: #d00; background: #ffebeb; border: 1px solid #f5c6cb; padding: 8px; margin-bottom: 12px; font-size: 14px; }
        .muted { color: #777; font-size: 12px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <h2>Kelola Role Pengguna</h2>
            <div>
                <span class="muted">{{ auth()->user()->username }} ({{ auth()->user()->role }})</span>
                &nbsp;|&nbsp;
                <a href="{{ route('dashboard') }}">Dashboard</a>
                &nbsp;|&nbsp;
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="logout">[ Logout ]</button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="ok">[ {{ session('status') }} ]</div>
        @endif

        @if ($errors->any())
            <div class="error">[ {{ $errors->first() }} ]</div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role Saat Ini</th>
                    <th>Ubah Role</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->nama_lengkap }}</td>
                        <td><span class="badge">{{ $user->role }}</span></td>
                        <td>
                            @if ($user->role === 'admin' || $user->is(auth()->user()))
                                <span class="muted">
                                    {{ $user->is(auth()->user()) ? 'Akun sendiri — tidak dapat diubah' : 'Akun admin — tidak dapat diubah' }}
                                </span>
                            @else
                                <form method="POST" action="{{ route('admin.users.role.update', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role">
                                        <option value="customer" @selected($user->role === 'customer')>customer</option>
                                        <option value="staff" @selected($user->role === 'staff')>staff</option>
                                    </select>
                                    <button type="submit" class="save">Simpan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted">Role admin sengaja tidak tersedia sebagai pilihan pada form (BR-03).</p>
    </div>
</body>
</html>

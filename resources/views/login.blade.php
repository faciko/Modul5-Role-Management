<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f9f9f9; }
        .box { border: 1px solid #333; padding: 25px; width: 320px; background: #fff; }
        .error { color: #d00; background: #ffebeb; border: 1px solid #f5c6cb; padding: 8px; margin-bottom: 12px; font-size: 14px; }
        .form-group { margin-bottom: 12px; }
        label { display: block; margin-bottom: 4px; font-size: 14px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 6px; box-sizing: border-box; }
        button { padding: 6px 16px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="margin-top:0">Login</h2>
        <p style="color:#666; font-size:14px;">Masuk untuk membuka dashboard</p>

        @if ($errors->has('login_error'))
            <div class="error">
                [ {{ $errors->first('login_error') }} ]
            </div>
        @endif

        <form method="POST" action="/login">
            @csrf
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Masuk</button>
        </form>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Stateless Cart (Simulasi W06)</title>
    <style>
        body { font-family: sans-serif; max-width: 760px; margin: 30px auto; }
        h1 { margin-bottom: 4px; }
        .bar { display: flex; justify-content: space-between; align-items: center; border: 1px solid #ddd; padding: 10px; margin-bottom: 14px; background: #fafafa; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; background: #eef; }
        .muted { color: #666; font-size: 13px; }
        button { margin-right: 6px; padding: 6px 10px; cursor: pointer; }
        .panel { border: 1px solid #ddd; padding: 12px; margin-bottom: 14px; }
        .error { color: #c00; }
        .ok { color: #0a7a2f; }
        pre { background: #f6f6f6; padding: 10px; overflow: auto; font-size: 12px; }
        .login-row input { padding: 5px; margin-right: 6px; }
        .hide { display: none; }
    </style>
</head>
<body>
    <h1>Stateless Cart (Simulasi W06)</h1>
    <p class="muted">
        Isi cart dikirim dari browser pada setiap request. Status login dibaca server dari session.
        Refresh halaman mengosongkan cart — ini perilaku normal, bukan efek Observer.
    </p>

    <div class="bar">
        <div id="session-info">Memuat status login…</div>
        <div>
            <a href="/admin/users" class="muted">Kelola role (admin)</a>
            &nbsp;|&nbsp;
            <a href="/dashboard" class="muted">Dashboard</a>
        </div>
    </div>

    <div class="panel" id="login-panel">
        <strong>Login (JSON)</strong>
        <div class="login-row" style="margin-top:8px">
            <input id="username" placeholder="username" autocomplete="username">
            <input id="password" type="password" placeholder="password" autocomplete="current-password">
            <button onclick="login()">Masuk</button>
            <button onclick="logout()">Logout</button>
        </div>
        <div id="login-msg" class="muted" style="margin-top:8px"></div>
        <div class="muted">Contoh akun: customer=Kevin, staff=staff, admin=admin</div>
    </div>

    <div class="panel">
        <strong>Katalog</strong>
        <div id="katalog" class="muted">Memuat…</div>
    </div>

    <div class="panel">
        <strong>Keranjang</strong>
        <div id="items">Keranjang kosong.</div>
        <h3 id="total">Total: Rp0</h3>
        <button onclick="clearCart()">Kosongkan Keranjang</button>
    </div>

    <div class="panel">
        <strong>Respons terakhir server</strong>
        <pre id="response">Belum ada request.</pre>
    </div>

    <script>
        let cart = {};

        async function call(url, payload = null, method = 'POST') {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            };
            if (payload) options.body = JSON.stringify(payload);

            const response = await fetch(url, options);
            let data;
            try {
                data = await response.json();
            } catch (e) {
                data = { message: 'Server mengembalikan respons non-JSON (' + response.status + ').' };
            }

            // Sinkronkan token terbaru dari server (session diregenerasi saat login/logout).
            const fresh = response.headers.get('X-CSRF-Token');
            if (fresh) {
                document.querySelector('meta[name="csrf-token"]').setAttribute('content', fresh);
            }

            return { status: response.status, ok: response.ok, data };
        }

        function show(result) {
            document.getElementById('response').textContent =
                'HTTP ' + result.status + '\n' + JSON.stringify(result.data, null, 2);
        }

        // Token selalu dibaca ulang dari DOM: logout/login meregenerasi token session.
        function csrf() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }

        async function login() {
            const result = await call('/api/login', {
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
            });
            show(result);
            document.getElementById('login-msg').textContent = result.ok
                ? 'Login berhasil sebagai ' + result.data.user.username + ' (' + result.data.user.role + ')'
                : (result.data.message || 'Login gagal.');
            await loadMe();
        }

        async function logout() {
            const result = await call('/logout', null);
            show(result);
            cart = {};
            render({ items: [], total: 0 });
            document.getElementById('login-msg').textContent = 'Logout.';
            await loadMe();
        }

        async function loadMe() {
            const result = await call('/api/me', null, 'GET');
            const info = document.getElementById('session-info');

            if (!result.ok) {
                info.innerHTML = '<span class="error">Guest (belum login)</span> — cart/add akan ditolak.';
                return;
            }

            const u = result.data.user;
            const a = result.data.abilities;
            const allowed = Object.entries(a).filter(([, v]) => v).map(([k]) => k).join(', ') || '-';
            info.innerHTML = 'Login sebagai <strong>' + u.nama_lengkap + '</strong> (' + u.username + ') ' +
                '<span class="badge">' + u.role + '</span><br>' +
                '<span class="muted">ability: ' + allowed + ' | use-cart: ' +
                (a['use-cart'] ? '<span class="ok">boleh</span>' : '<span class="error">tidak</span>') + '</span>';
        }

        async function add(id) {
            const result = await call('/cart/add', { cart: cart, product_id: id });
            show(result);

            if (!result.ok) {
                document.getElementById('items').innerHTML =
                    '<span class="error">Ditolak server: HTTP ' + result.status + ' — ' +
                    (result.data.message || '-') + '</span>';
                return;
            }

            cart = Object.fromEntries(result.data.items.map(i => [i.id, i.quantity]));
            render(result.data);
        }

        function render(data) {
            const container = document.getElementById('items');
            container.replaceChildren();

            if (!data.items || data.items.length === 0) {
                container.textContent = 'Keranjang kosong.';
            }

            (data.items || []).forEach(item => {
                const row = document.createElement('p');
                row.textContent = item.name + ' x ' + item.quantity + ' = Rp' + item.subtotal.toLocaleString('id-ID');
                container.appendChild(row);
            });

            document.getElementById('total').textContent =
                'Total: Rp' + (data.total || 0).toLocaleString('id-ID');
        }

        function clearCart() {
            cart = {};
            render({ items: [], total: 0 });
            document.getElementById('response').textContent = 'Cart di browser dikosongkan (tidak ada request ke server).';
        }

        async function loadProducts() {
            const result = await call('/api/products', null, 'GET');
            const box = document.getElementById('katalog');
            box.replaceChildren();

            if (!result.ok) {
                box.textContent = 'Gagal memuat katalog.';
                return;
            }

            result.data.products.forEach(p => {
                const b = document.createElement('button');
                b.textContent = p.name + ' - Rp' + p.price.toLocaleString('id-ID');
                b.onclick = () => add(p.id);
                box.appendChild(b);
            });
        }

        loadMe();
        loadProducts();
    </script>
</body>
</html>

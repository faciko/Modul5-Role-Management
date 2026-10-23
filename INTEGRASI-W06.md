# Rencana Integrasi W06 Stateless Cart

Sesuai Modul 5 bagian 4.1: karena kode W06 (`stateless-shop`) berada pada
repository lain, praktikum inti menggunakan **route simulasi** dan integrasi
penuh tidak dipaksa dalam satu sesi.

## Yang sudah ada di repo ini

- `POST /cart/add` — `CartSimulationController` (payload `{cart, product_id}`,
  produk hardcode, harga dihitung server).
- Middleware: `auth` + `can:use-cart` (Gate `use-cart` = role `customer`).

## Rencana integrasi ke `stateless-shop` asli

1. Tambahkan kolom `role` pada tabel `users` lewat migration `add_role_to_users_table`.
2. Tambahkan login W05 (`AuthController`, view `login`, route `/login`, `/dashboard`, `/logout`).
3. Tambahkan Gate `use-cart` di `AppServiceProvider` dan middleware
   `->middleware(['auth', 'can:use-cart'])` pada `Route::post('/cart/add', ...)`
   yang sudah ada — cukup satu baris, jangan menulis ulang route.
4. Halaman `shop.blade.php` perlu menampilkan tombol login/logout dan
   menampilkan pesan 403 dari `fetch` (saat ini `alert()` sudah menangkap error).
5. Tambahkan `UserObserver` + registrasi di `AppServiceProvider`.

## Catatan

- Cart tetap stateless: isi cart tidak disimpan server, hanya role yang dibaca dari session.
- Refresh halaman mengosongkan `cart` di JavaScript — perilaku ini normal dan
  tidak berkaitan dengan Observer role.

# Rencana Integrasi W06 Stateless Cart

Sesuai Modul 5 §4.1: karena kode W06 (`stateless-shop`) berada pada repository
lain, praktikum inti memakai **route simulasi** dan integrasi penuh tidak
dipaksa dalam satu sesi.

## Yang sudah ada di repo ini (simulasi)

- `GET /shop` — halaman cart stateless (JS `fetch`, cart disimpan di memori browser).
- `POST /cart/add` — `CartSimulationController`, payload `{cart, product_id}`,
  produk hardcode, harga dihitung server.
- Middleware: `auth` + `can:use-cart` (Gate `use-cart` = role `customer`).
- `POST /api/login` + `GET /api/me` — login JSON dan ringkasan ability.

## Bukti AC-06 (sudah diuji)

1. Login `Kevin` (customer) di `/shop` → `POST /cart/add` → **200**.
2. Admin mengubah Kevin menjadi `staff` lewat `/admin/users`.
3. Kevin mengirim payload cart **yang sama** → **403** `This action is unauthorized.`

Cart tetap stateless (isi dari request), tetapi hak akses dibaca ulang dari
session + role di database pada setiap request.

## Rencana integrasi ke `stateless-shop` asli

1. Tambahkan kolom `role` lewat migration `add_role_to_users_table`.
2. Tambahkan login W05 (`AuthController`, view `login`, route `/login`, `/dashboard`, `/logout`).
3. Tambahkan Gate `use-cart` di `AppServiceProvider`, lalu ubah satu baris:
   `Route::post('/cart/add', ...)->middleware(['auth', 'can:use-cart']);`
   — jangan menulis ulang route yang sudah ada.
4. Tambahkan registrasi `UserObserver` di `AppServiceProvider`.
5. Di `shop.blade.php`, tambahkan tombol login/logout dan tampilkan status
   HTTP dari `fetch` (saat ini hanya `alert()`).

## Catatan

- Refresh halaman mengosongkan `cart` di JavaScript — perilaku normal, tidak
  berkaitan dengan Observer role.
- `POST /logout` dari fetch harus memakai token CSRF terbaru; halaman `/shop`
  membaca token dari header `X-CSRF-Token` pada setiap respons.

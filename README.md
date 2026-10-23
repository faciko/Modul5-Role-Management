# Tugas 1 — Login (baseline W05) + Modul 5 Role Management

Repo praktikum **Modul 5 Role Management** (Proyek 3 D3 Teknik Informatika).
Baseline: tugas login W05 (`/login`, `/dashboard`, `/logout`, field `username` + `nama_lengkap`).
Keranjang W06 disimulasikan lewat route `/cart/add` — lihat `INTEGRASI-W06.md`.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed      # RoleDemoSeeder: idempoten, akun W05 tidak diduplikasi
```

Butuh MySQL aktif (`.env` default: `db_tugas`) atau ubah `DB_CONNECTION` ke `sqlite`.

## Akun demo

| Username | Password    | Role     |
|----------|-------------|----------|
| Salman   | rahasia123  | customer |
| Kevin    | password123 | customer |
| admin    | admin12345  | admin    |
| staff    | staff12345  | staff    |

Admin awal dibuat oleh seeder (jalur tepercaya), **tidak** lewat form registrasi.
`role` sengaja tidak ada di `$fillable` sehingga tidak bisa diisi mass-assignment.

## Halaman

| URL | Akses | Keterangan |
|---|---|---|
| `/login`, `/logout` | publik / auth | Login W05 |
| `/dashboard` | auth | Menu sesuai ability |
| `/shop` | publik | Simulasi cart stateless W06 + login JSON |
| `/admin/users` | admin (`manage-roles`) | Form ubah role (customer ⇄ staff) |
| `PATCH /admin/users/{user}/role` | admin | Endpoint perubahan role |
| `/staff/stock` | staff + admin (`adjust-stock`) | Lihat stok |
| `/admin/products` | admin (`manage-products`) | Kelola katalog |
| `POST /cart/add` | customer (`use-cart`) | Cart dari payload request |
| `/api/login`, `/api/me`, `/api/products` | – | Pendukung halaman `/shop` |

## Peta komponen (Modul 5 §3.3)

| Komponen | File |
|---|---|
| Gate + registrasi Observer | `app/Providers/AppServiceProvider.php` |
| Route | `routes/web.php` |
| Form Request | `app/Http/Requests/ChangeRoleRequest.php` |
| Controller | `app/Http/Controllers/RoleController.php` |
| Service | `app/Services/RoleManagementService.php` |
| Model | `app/Models/User.php` |
| Observer | `app/Observers/UserObserver.php` |
| Migration role | `database/migrations/2026_10_19_035537_add_role_to_users_table.php` |
| Seeder akun | `database/seeders/RoleDemoSeeder.php` |

## Bukti untuk laporan / code defense

Jalankan:

```bash
php artisan test
```

| Test | Membuktikan |
|---|---|
| `RoleManagementTest` | AC-01 s/d AC-06, BR-01/03/04, log Observer tepat 1×, AC-05 tanpa `save` |
| `AbilityMatrixTest` | Access matrix 5 baris + AC-06 (customer → staff, payload sama → 403) |
| `ObserverEdgeCaseTest` | `save()` memicu log, bulk update tidak, log tanpa data sensitif, Observer ≠ penjaga izin |
| `BaselineW05Test` | Login/dashboard/logout W05 tetap berjalan |

Lihat log perubahan role:

```bash
tail -f storage/logs/laravel.log | grep user.role.changed
```

Contoh:
```
[2026-10-23 08:50:44] local.INFO: user.role.changed {"target_user_id":1,"old_role":"customer","new_role":"staff","actor_id":4}
```

## Troubleshooting cepat

| Gejala | Penyebab umum |
|---|---|
| 403 saat login admin | role di DB bukan `admin`, atau nama ability salah |
| 422 / redirect dengan error | Form Request menolak pilihan role (hanya `customer`, `staff`) |
| Data berubah tetapi log kosong | Observer belum terdaftar, atau update lewat query builder/bulk |
| Log muncul dua kali | dua `save()`, dua request, atau Observer terdaftar ganda |
| 419 | token CSRF basi setelah login/logout — muat ulang halaman `/shop` |
| Staff masih bisa `cart/add` | session lama; logout lalu login ulang |

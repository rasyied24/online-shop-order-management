# Online Shop: Order Management

Aplikasi fullstack untuk mengelola produk, pelanggan, dan transaksi penjualan. Satu order dapat berisi lebih dari satu produk, dengan validasi stok dan perhitungan harga sepenuhnya di server. Backend Laravel 13 (REST API) dan frontend React + TypeScript berjalan sebagai dua service terpisah.

> Proyek ini berawal dari sebuah studi kasus online shop, lalu saya kembangkan lebih lanjut sebagai proyek portofolio.

![CI](https://github.com/rasyied24/online-shop-order-management/actions/workflows/ci.yml/badge.svg)

## Fitur utama

- Login dengan token, rute terproteksi, dan penanganan token kedaluwarsa
- CRUD produk dan pelanggan: pagination, pencarian, pengurutan (produk), dan soft delete
- Pembuatan order multi-produk dengan validasi stok, harga dan total dihitung di server, dalam satu transaksi database
- Riwayat order dengan filter tanggal dan pelanggan, serta halaman detail
- Dashboard: total transaksi, total penjualan, produk terlaris, dan peringatan stok menipis
- Tampilan responsif dengan state loading, empty, dan error; format Rupiah dan tanggal Indonesia

## Daftar isi

1. [Tech stack dan alasannya](#tech-stack-dan-alasannya)
2. [Struktur repo](#struktur-repo)
3. [Menjalankan aplikasi](#menjalankan-aplikasi)
4. [Menjalankan test](#menjalankan-test)
5. [Dokumentasi API](#dokumentasi-api)
6. [Logika aplikasi](#logika-aplikasi)
7. [Asumsi dan keterbatasan](#asumsi-dan-keterbatasan)
8. [Rencana pengembangan](#rencana-pengembangan)
9. [AI Usage](#ai-usage)

## Tech stack dan alasannya

**Backend** (`backend/`)

| Komponen | Pilihan | Alasan |
|---|---|---|
| Framework | Laravel 13 (PHP 8.3+) | Framework yang paling saya kuasai, sehingga keputusan teknisnya bisa saya pertanggungjawabkan |
| Database | PostgreSQL | Mendukung row locking (`SELECT ... FOR UPDATE`) yang dipakai untuk mengamankan stok |
| Autentikasi | Laravel Sanctum (API token) | Sederhana dan cukup untuk SPA yang terpisah; token kedaluwarsa setelah 24 jam |
| Validasi | Form Request | Validasi terpisah dari controller |
| Test | PHPUnit (feature test) | Bawaan Laravel |

**Frontend** (`frontend/`)

| Komponen | Pilihan | Alasan |
|---|---|---|
| Framework | React 19 + TypeScript + Vite (SPA) | Backend sudah terpisah, aplikasi berada di balik login, dan tidak butuh SSR/SEO. Vite memberi setup yang lebih ringan daripada Next.js |
| Routing | React Router | Rute terproteksi dan code splitting per halaman |
| Data fetching | TanStack Query | Cache, state loading/error, dan invalidasi setelah mutasi |
| Form dan validasi | React Hook Form + Zod | Nama field array sama dengan key error Laravel (`items.1.quantity`), jadi error server dipetakan ke baris yang tepat |
| UI | Tailwind CSS v4 + shadcn/ui | Komponen yang dapat diakses (aksesibel) dan mudah disesuaikan |
| HTTP | Axios + interceptor | Token otomatis ditambahkan, 401 ditangani di satu tempat, error dinormalisasi |

## Struktur repo

```
online-shop-order-manajemen/
├── backend/        Laravel 13 (REST API)
├── frontend/       React + Vite (SPA)
├── docs/postman/   Postman collection
└── README.md
```

Backend dan frontend adalah dua service terpisah dan dijalankan sendiri-sendiri.

## Menjalankan aplikasi

**Prasyarat:** PHP 8.3+ dengan ekstensi `pdo_pgsql`, Composer, PostgreSQL, Node.js (dikembangkan dengan Node 24) dan npm.

Buat dahulu database PostgreSQL kosong bernama `azatech_shop`. Database sebaiknya dimiliki oleh user yang akan kamu isi di `backend/.env` (pada PostgreSQL 15 ke atas, user biasa tidak otomatis boleh membuat tabel di schema `public`). Lalu jalankan:

```bash
# 1. Ambil kode
git clone https://github.com/rasyied24/online-shop-order-management.git && cd online-shop-order-management

# 2. Backend: instal dependensi dan siapkan .env
cd backend && composer install && cp .env.example .env && php artisan key:generate

# 3. Sesuaikan DB_USERNAME dan DB_PASSWORD di backend/.env, lalu migrasi + seed
php artisan migrate --seed

# 4. Jalankan backend (tetap di terminal ini)
php artisan serve

# 5. Terminal baru (buka di folder root repo, NAMA-REPO): instal dan jalankan frontend
cd frontend && cp .env.example .env && npm install && npm run dev
```

- Backend: <http://localhost:8000/api>
- Frontend: <http://localhost:5173>

Frontend memakai `VITE_API_URL` (default di `.env.example`: `http://localhost:8000/api`). Backend hanya mengizinkan origin dari `FRONTEND_URL` (default `http://localhost:5173`), jadi buka frontend lewat `localhost`, bukan `127.0.0.1`.

**Akun login (dari seeder):**

| Email | Password |
|---|---|
| `admin@azatech.id` | `password` |

**Data seeder:** 1 user admin, 20 produk (`P0001` sampai `P0020`), 15 pelanggan, dan 30 order yang tersebar dalam 30 hari terakhir. Migrasi dan seeder dapat dijalankan dari database kosong.

## Menjalankan test

**Backend** membutuhkan database PostgreSQL **terpisah** bernama `azatech_shop` (agar data development tidak terhapus). Buat database kosong itu dengan pemilik yang sama (user yang sama dengan di `backend/.env` juga dipakai oleh test), lalu:

```bash
cd backend
php artisan test
```

Ada 16 test, mencakup skenario utama: buat order sukses, stok tidak cukup, validasi input gagal, dan akses tanpa token.

**Frontend** belum memiliki test otomatis (lihat [keterbatasan](#asumsi-dan-keterbatasan)). Pemeriksaan yang tersedia:

```bash
cd frontend
npm run lint
npm run build   # termasuk type-check TypeScript
```

## Dokumentasi API

Postman collection ada di [`docs/postman/azatech-shop.postman_collection.json`](docs/postman/azatech-shop.postman_collection.json).

1. Impor file tersebut di Postman.
2. Jalankan request **Auth, Login** terlebih dahulu. Tes pada request itu menyimpan token ke variabel koleksi, dan semua request lain otomatis memakainya.
3. Variabel `base_url` bisa diubah (default `http://localhost:8000/api`).
4. Request **Create** pada Produk, Pelanggan, dan Order menyimpan id-nya ke variabel (`product_id`, `customer_id`, `order_id`), sehingga request Detail, Update, dan Delete bisa langsung dijalankan sesudahnya.

Semua endpoint selain login membutuhkan header `Authorization: Bearer <token>`.

**Format response** (semua endpoint):

```json
{ "success": true, "message": "OK", "data": {}, "meta": { "page": 1, "per_page": 10, "total": 57, "total_pages": 6 } }
{ "success": false, "message": "Validasi gagal", "errors": { "items.0.quantity": ["Jumlah minimal 1"] } }
```

`meta` hanya ada pada response list; `errors` bersifat opsional. Status code yang dipakai: 200, 201, 204 (DELETE, tanpa body), 401, 404, 409, 422, 500.

## Logika aplikasi

### Alur pembuatan order (`POST /api/orders`)

1. Klien mengirim `customer_id` dan `items` (`product_id`, `quantity`). **Harga dan total tidak pernah diterima dari klien**; nilai `price` atau `total` yang dikirim diabaikan.
2. Form Request memvalidasi input. Satu produk tidak boleh muncul dua kali dalam `items`. Jika gagal: **422**.
3. Seluruh proses berikut berjalan dalam **satu database transaction** (`DB::transaction` di `CreateOrderAction`):
   - Baris produk yang terlibat dikunci dengan `lockForUpdate`, **diurutkan berdasarkan id**. Penguncian berurutan mencegah deadlock ketika dua order mengunci produk yang sama dalam urutan berbeda, dan mencegah dua order memakai stok yang sama secara bersamaan (oversell).
   - Stok setiap item diperiksa. Jika ada yang kurang, error **semua item bermasalah** dikumpulkan lalu dikembalikan sekaligus dengan **409** (`InsufficientStockException`); transaksi dibatalkan sehingga **tidak ada stok yang berkurang**.
   - Server menghitung `unit_price` (snapshot harga saat transaksi), `subtotal` tiap item, dan `total_amount`.
   - Nomor invoice `INV-YYYYMMDD-XXXX` diambil dari tabel `order_sequences` dengan satu perintah SQL atomik, sehingga unik dan aman dari balapan (race) antar-request.
   - `orders` dan `order_items` disimpan, lalu stok dikurangi.
4. Response **201** berisi order lengkap beserta item-nya.

**Rollback.** `DB::transaction` menangkap exception apa pun yang keluar dari closure, membatalkan seluruh perubahan, lalu melemparnya kembali. Karena kenaikan nomor invoice berada di dalam transaksi yang sama, nomornya ikut dibatalkan dan **tidak loncat**. Inilah alasan nomor invoice tidak memakai sequence bawaan database, karena sequence tidak ikut rollback. Konsekuensinya, order pada hari yang sama antre di baris penghitung itu sampai transaksi selesai.

**Kenapa 409 untuk stok dan 422 untuk validasi?** 422 berarti input itu sendiri tidak valid. 409 berarti input valid tetapi bertentangan dengan keadaan data saat ini (stok tidak cukup), dan bisa berubah jika dicoba lagi nanti.

`unit_price` disimpan di `order_items` agar laporan historis tetap akurat walaupun harga produk berubah. Produk atau pelanggan yang sudah dihapus (soft delete) tetap tampil pada riwayat order.

### Struktur kode backend

| Lokasi | Peran |
|---|---|
| `app/Http/Controllers/Api` | Menerima request dan mengembalikan response; dibuat tipis |
| `app/Http/Requests` | Validasi input (Form Request) |
| `app/Http/Resources` | Membentuk JSON keluaran |
| `app/Actions` | Logika bisnis satu use case per kelas (`CreateOrderAction`, `GetDashboardSummaryAction`) |
| `app/Exceptions` | Exception khusus kejadian bisnis (`InsufficientStockException`) |
| `app/Support` | Helper format response standar (`ApiResponse`) |
| `bootstrap/app.php` | Semua error API diubah ke format response yang sama |

### Tambahan pada skema database

| Tambahan | Alasan |
|---|---|
| Tabel `order_sequences` (`order_date` sebagai primary key, `last_number`) | Penghitung nomor invoice per hari yang dinaikkan secara atomik di dalam transaksi order, sehingga `order_number` unik tanpa bergantung pada `MAX()` atau `COUNT()` yang rawan balapan |
| `UNIQUE (order_id, product_id)` pada `order_items` | Satu produk hanya satu baris per order; sekaligus menjadi index untuk `order_id` |
| Check constraint: `products.stock >= 0`, `products.price > 0`, `order_items.quantity > 0` | Pengaman terakhir di level database, di luar validasi aplikasi |
| Index `products.product_name` dan `customers.customer_name` | Mempercepat pengurutan. Pencarian `ILIKE '%kata%'` tidak memakainya (perbaikan: index trigram `pg_trgm`) |
| Index `orders.order_date`, `orders.customer_id`, `order_items.product_id` | Filter riwayat order dan agregasi dashboard. PostgreSQL tidak membuat index otomatis untuk foreign key |
| Foreign key `RESTRICT` (order ke pelanggan dan user, item ke produk) | Data yang sudah dipakai transaksi tidak bisa terhapus permanen; penghapusan dilakukan lewat soft delete |
| Tabel bawaan framework (`personal_access_tokens`, `cache`, `jobs`, `sessions`) | Dibuat oleh migrasi bawaan Laravel dan Sanctum |

### Frontend

- **Autentikasi:** token disimpan di `localStorage`. Saat aplikasi dimuat, token divalidasi lewat `/auth/me`. Satu interceptor menangani 401 di mana pun terjadi (token kedaluwarsa atau dicabut): sesi dibersihkan dan user diarahkan ke login, lalu kembali ke halaman tujuan semula setelah login ulang.
- **Buat Order:** baris produk dinamis (`useFieldArray`). Produk yang sudah dipilih di satu baris dinonaktifkan di baris lain. Subtotal dan total di layar hanya tampilan (dihitung dalam sen agar bebas galat floating-point); total yang sah dihitung server. Error 409/422 dipetakan ke baris dan field yang bermasalah.
- **Stok pada form order hanya petunjuk.** Jumlah melebihi stok menampilkan peringatan tetapi tidak memblokir, karena stok di browser bisa basi; server yang menentukan.
- **State** loading, empty, dan error tersedia pada setiap halaman data. Halaman dimuat per rute (code splitting), dengan halaman 404 dan penangkap error untuk kegagalan tak terduga.

## Asumsi dan keterbatasan

**Backend**
- Kode produk (`product_code`) tetap unik walaupun produk sudah di-soft-delete, sehingga kode produk yang dihapus tidak bisa dipakai ulang.
- Validasi nomor HP pelanggan: opsional, angka 8 sampai 19 digit, boleh diawali `+`.
- Dashboard bersifat **semua waktu** (tidak ada filter periode).
- Produk yang sudah dihapus tetap dihitung dan tampil pada daftar produk terlaris.
- Belum ada test untuk skenario race condition paralel (dua order bersamaan pada produk yang sama). Mekanisme pengamannya (transaksi dan `lockForUpdate` berurutan) sudah ada, tetapi belum dibuktikan dengan test.
- Belum ada idempotency key pada `POST /orders`. Klik ganda dicegah di sisi klien (tombol dinonaktifkan saat proses), tetapi request ganda langsung ke API akan membuat dua order.

**Frontend**
- Token di `localStorage` rentan dicuri jika ada celah XSS. Alternatif yang lebih aman adalah cookie `HttpOnly`, yang butuh perubahan di sisi backend.
- State daftar (halaman, pencarian, filter) disimpan di memori komponen, bukan di URL, sehingga hilang saat reload dan tidak bisa dibagikan lewat tautan.
- Stok produk pada baris order adalah snapshot saat produk dipilih; setelah error 409 pengguna perlu memilih ulang produk untuk melihat stok terbaru.
- Tabel pada layar kecil digeser secara horizontal, bukan diubah menjadi tampilan kartu.
- Ambang stok menipis (5) ditulis di frontend untuk badge tabel produk, padahal sumber kebenarannya di backend. Dashboard sudah memakai nilai dari server.
- Logout di satu tab tidak otomatis mengeluarkan tab lain.
- TypeScript dipasang di seri 6.0 karena `typescript-eslint` belum mendukung TypeScript 7.
- Belum ada test otomatis di frontend.

**Belum dikerjakan:** `docker compose`, GitHub Actions, grafik penjualan harian, export CSV, dan test frontend (lihat [rencana pengembangan](#rencana-pengembangan)).

## Rencana pengembangan

- [x] GitHub Actions untuk lint, build, dan test pada setiap push
- [ ] Test race condition paralel untuk pembuatan order, test rollback nomor invoice, dan idempotency key pada `POST /orders`
- [ ] `docker compose` untuk menjalankan seluruh aplikasi dengan satu perintah
- [ ] Test frontend: Vitest untuk helper murni (format Rupiah/tanggal, hitung order, pemetaan error server) dan Playwright untuk alur login sampai buat order
- [ ] Endpoint agregasi penjualan harian di backend, lalu grafiknya di dashboard
- [ ] Filter pelanggan pada riwayat order di UI (backend sudah mendukung `customer_id`) dan export CSV
- [ ] Menyimpan state daftar di URL, tampilan kartu untuk tabel di layar kecil, dan komponen CRUD generik untuk halaman Produk dan Pelanggan
- [ ] Token pada cookie `HttpOnly` (Sanctum SPA mode)
- [ ] Index trigram `pg_trgm` untuk pencarian dengan wildcard di depan

## AI Usage

Saya memakai **Claude (Anthropic)** lewat antarmuka chat sebagai asisten pengembangan.

| Bagian | Peran AI | Peran saya |
|---|---|---|
| Backend (Laravel) | Membantu diskusi desain, penulisan kode, dan test | Menentukan keputusan, menjalankan dan memverifikasi semua test, dan menyesuaikan kode |
| Frontend (React) | Menyusun arsitektur dan menulis kode bertahap per fase (setup, login, produk dan pelanggan, buat order, riwayat, dashboard, poles) | Menjalankan setiap fase, menguji manual terhadap daftar verifikasi, melaporkan dan memperbaiki masalah |
| Dokumentasi | Menyusun draf README dan Postman collection | Memeriksa dan menyesuaikan isinya dengan kondisi aplikasi |

Saya bertanggung jawab atas seluruh kode di repositori ini dan dapat menjelaskannya.
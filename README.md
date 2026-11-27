# KasirKita POS — Sistem Kasir Modern (Point of Sale)

Aplikasi kasir (Point of Sale / POS) berbasis web untuk operasional penjualan ritel secara digital, mencakup manajemen produk, stok inventori, transaksi tunai & QRIS dinamis, struk thermal, riwayat transaksi, laporan penjualan, serta kontrol akses berbasis peran (RBAC).

---

## Akun Uji Coba (Testing)

Akun berikut dibuat otomatis oleh `DatabaseSeeder` saat menjalankan `php artisan db:seed` atau `php artisan migrate:fresh --seed`:

| Role | Nama | Email | Kata Sandi | Hak Akses |
| :--- | :--- | :--- | :--- | :--- |
| **Pemilik / Admin (`owner`)** | Budi Santoso (Pemilik) | `owner@kasirkita.id` | `password123` | Akses penuh: Dashboard, Kasir, Riwayat Transaksi, Manajemen Produk, Manajemen Stok, Laporan Penjualan, Pengguna & Log Audit, Pengaturan Toko, serta Refund. |
| **Kasir (`kasir`)** | Siti Rahma (Kasir) | `kasir@kasirkita.id` | `password123` | Dashboard, Kasir / POS, Riwayat Transaksi (miliknya), Cetak Struk, dan pembatalan transaksi `pending` miliknya. |

> **Penting:** Segera ubah kata sandi kedua akun di atas sebelum digunakan di lingkungan production.

---

## Teknologi yang Digunakan

- **Backend:** Laravel `13.44.1`
- **PHP:** `8.4.27`
- **Database:** MySQL `8.4.3`
- **Frontend:** Blade + Livewire `4.4.10` + Tailwind CSS (`@tailwindcss/vite ^4.0.0`) + Vite `7.3.8`
- **QR Code Generator:** `chillerlan/php-qrcode 6.0.1`
- **Dependency Manager:** Composer `2.10.3` & npm

---

## Fitur Utama

1. **Dashboard Operasional**
   - Total penjualan & jumlah transaksi lunas hari ini, pendapatan bulan ini, grafik penjualan 7 hari terakhir, ringkasan metode pembayaran, produk terlaris 30 hari, peringatan stok menipis/habis, dan transaksi terbaru.
2. **Kasir / POS (`/kasir`)**
   - Pencarian produk berdasarkan nama, SKU, atau scan barcode (`Enter`).
   - Filter kategori, keranjang interaktif, diskon (Rp), dan validasi agar penjualan tidak melebihi stok tersedia.
   - Pembayaran **Tunai** (nominal cepat & perhitungan kembalian otomatis) dan **QRIS dinamis** melalui payment gateway.
3. **Manajemen Produk (`/produk`) — Khusus Owner**
   - Tambah, edit, aktif/nonaktifkan produk, upload foto produk, pengelolaan kategori, validasi SKU & barcode unik, serta import/export CSV.
   - Perubahan harga otomatis tercatat ke `audit_logs`.
4. **Manajemen Stok (`/stok`) — Khusus Owner**
   - Stok otomatis berkurang saat transaksi berhasil dibayar (menggunakan transaksi database & `lockForUpdate()` untuk mencegah stok negatif).
   - Penyesuaian stok manual dengan alasan wajib, peringatan stok menipis & habis, serta riwayat pergerakan stok (`inventory_movements`).
5. **Riwayat Transaksi (`/transaksi`) & Struk Thermal**
   - Pencarian nomor transaksi/kasir, filter tanggal, metode pembayaran, dan status pembayaran.
   - Cetak ulang struk thermal ukuran **58mm** dan **80mm** (`/transaksi/{id}/struk`).
   - Pembatalan dan refund (khusus Owner) dengan pencatatan audit dan opsi pengembalian stok.
6. **Laporan Penjualan (`/laporan`) — Khusus Owner**
   - Filter harian, mingguan, bulanan, atau rentang tanggal kustom, estimasi laba (berdasarkan snapshot harga modal vs harga jual), rincian per metode pembayaran & kategori, produk terlaris, serta export CSV.
   - Hanya menghitung transaksi dengan status `paid` dan `completed`.
7. **Pengguna, Log Audit (`/pengguna`), & Pengaturan Toko (`/pengaturan`) — Khusus Owner**
   - Pengelolaan akun pengguna, pemantauan aktivitas penting di `audit_logs`, pengaturan identitas toko pada struk, serta status integrasi QRIS.

---

## Instalasi & Menjalankan Secara Lokal

1. **Clone / masuk ke direktori proyek**
   ```bash
   cd "d:\Projek pribadi\kasir"
   ```

2. **Install dependensi PHP & Frontend**
   ```bash
   composer install
   npm install
   npm run build
   ```

3. **Konfigurasi Environment**
   - Salin `.env.example` ke `.env` (jika belum ada) dan generate application key:
     ```bash
     php artisan key:generate
     ```
   - Sesuaikan koneksi database MySQL pada `.env`:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=kasir
     DB_USERNAME=root
     DB_PASSWORD=
     ```

4. **Jalankan Migrasi, Seeder, dan Storage Link**
   ```bash
   php artisan migrate:fresh --seed
   php artisan storage:link
   ```

5. **Jalankan Server Lokal**
   ```bash
   php artisan serve
   ```
   Buka `http://127.0.0.1:8000` di browser dan masuk menggunakan akun uji coba di atas.

---

## Konfigurasi Pembayaran QRIS (Midtrans / Xendit)

Pembayaran QRIS tidak menggunakan simulasi palsu. Status transaksi hanya berubah menjadi `paid` setelah backend menerima konfirmasi valid dari payment gateway (melalui webhook yang terverifikasi signature/token atau pengecekan status resmi).

Atur variabel berikut di `.env`:

```env
PAYMENT_GATEWAY_PROVIDER=midtrans # midtrans atau xendit
PAYMENT_GATEWAY_MODE=sandbox      # sandbox atau production

# Jika menggunakan Midtrans
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=

# Jika menggunakan Xendit
XENDIT_SECRET_KEY=
XENDIT_CALLBACK_TOKEN=
```

Daftarkan endpoint webhook berikut pada dashboard payment gateway:
- Midtrans: `https://domain-anda/webhooks/payment/midtrans`
- Xendit: `https://domain-anda/webhooks/payment/xendit`

Jika credential belum diisi, halaman Kasir akan menampilkan status bahwa pembayaran QRIS belum aktif.

---

## Pengujian & Audit Keamanan

Jalankan seluruh automated tests dan audit dependensi dengan perintah:

```bash
php artisan test
composer audit
```

Cakupan pengujian utama:
- Transaksi tunai, perhitungan kembalian, penolakan uang kurang, dan pencegahan stok negatif.
- Pembuatan tagihan QRIS, penolakan saat credential belum diatur, verifikasi signature webhook, idempotency webhook duplikat, dan penanganan QRIS kedaluwarsa.
- Manajemen produk, validasi SKU unik, audit log perubahan harga, pembatasan akses RBAC Kasir vs Owner, pembatalan/refund dengan pengembalian stok, dan akurasi laporan penjualan.

<p align="center">
  <img src="public/img/Logo.png" alt="Logo Nusantara Distribusi Tracking" width="120">
</p>

<h1 align="center">Nusantara Distribusi Tracking (NDT)</h1>

<p align="center">
  Aplikasi Mini ERP untuk pembelian, penjualan, dan persediaan barang PT Nusantara Distribusi.
</p>

---

## Daftar Isi

1. [Tentang Aplikasi](#tentang-aplikasi)
2. [Fitur](#fitur)
3. [Teknologi](#teknologi)
4. [Kebutuhan Sistem](#kebutuhan-sistem)
5. [Instalasi](#instalasi)
6. [Akun Awal](#akun-awal)
7. [Hak Akses per Peran](#hak-akses-per-peran)
8. [Aturan Bisnis Penting](#aturan-bisnis-penting)
9. [Struktur Proyek](#struktur-proyek)
10. [Backup dan Restore Database](#backup-dan-restore-database)
11. [Pemecahan Masalah](#pemecahan-masalah)

---

## Tentang Aplikasi

NDT adalah aplikasi web internal untuk mengelola alur barang dari pembelian ke supplier sampai
pengiriman ke customer, dengan stok yang selalu dapat ditelusuri. Pengembangannya mengacu pada
dokumen SRS Mini ERP (Inventory & Sales Management System).

Alur utama:

```
Purchase Order (Draft -> Approved) -> Goods Receipt -> stok bertambah
Sales Order (Open)                 -> Delivery      -> stok berkurang
```

## Fitur

| Modul | Isi |
|---|---|
| Autentikasi | Login, logout, penguncian akun setelah 5 kali gagal (15 menit), sesi berakhir setelah 30 menit tidak aktif |
| Master Data | Kategori, Produk, Supplier, Customer |
| Purchasing | Purchase Order (Draft/Approved), Goods Receipt (penerimaan bertahap), pembatalan PO dan GR |
| Sales | Sales Order, Delivery (pengiriman bertahap), pembatalan SO dan Delivery |
| Inventory | Stok per produk, histori mutasi stok |
| Laporan | Pembelian, penjualan, stok, dan 10 produk terlaris |
| Administrasi | Kelola akun pengguna: tambah, ubah peran, aktif/nonaktif, reset password, buka kunci |

## Teknologi

- **Framework:** CodeIgniter 4 (pola MVC, logika bisnis di folder `Services`)
- **Database:** PostgreSQL
- **Tampilan:** Bootstrap 5.3 dan Bootstrap Icons (dimuat dari CDN, jadi perlu koneksi internet saat membuka halaman)
- **Bahasa:** PHP 8.1 atau lebih baru

## Kebutuhan Sistem

| Komponen | Versi / Keterangan |
|---|---|
| PHP | 8.1 atau lebih baru |
| Ekstensi PHP | `intl`, `mbstring`, `json`, `pgsql` (dan `pdo_pgsql` bila diminta), serta `zip` (dipakai Composer untuk mengunduh paket) |
| PostgreSQL | 12 atau lebih baru |
| Composer | 2.x |
| Browser | Edge, Chrome, atau Firefox versi terbaru |

> Pengguna XAMPP: buka `php.ini` (lewat tombol **Config** pada modul Apache di XAMPP Control Panel),
> hilangkan tanda `;` di depan `extension=intl`, `extension=pgsql`, dan `extension=zip`,
> lalu simpan dan mulai ulang Apache.
>
> Perhatikan: perintah `php` di terminal bisa memakai file `php.ini` yang berbeda dari Apache.
> Cek dengan `php --ini`, lalu ubah file yang ditunjukkan pada baris *Loaded Configuration File*.

## Instalasi

### 1. Ambil kode dan pasang dependensi

Sebelum `composer install`, pastikan ekstensi PHP yang dibutuhkan sudah aktif (lihat
[Kebutuhan Sistem](#kebutuhan-sistem)), terutama `zip`. Cek dengan:

```bash
php -m
```

Daftar yang muncul harus memuat `intl`, `mbstring`, `pgsql`, dan `zip`. Kalau ada yang belum
muncul, aktifkan di `php.ini` lalu buka terminal baru.

```bash
git clone <alamat-repository> ndt
cd ndt
composer install
```

### 2. Buat database PostgreSQL

Masuk ke `psql` sebagai superuser, lalu jalankan (ganti password sesuai keinginan):

```sql
CREATE USER mini_erp WITH PASSWORD 'ganti_dengan_password_anda';
CREATE DATABASE mini_erp OWNER mini_erp;
```

### 3. Buat file `.env`

Salin `env` menjadi `.env`, lalu isi seperti berikut:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'
app.forceGlobalSecureRequests = false

database.default.hostname = localhost
database.default.database = mini_erp
database.default.username = mini_erp
database.default.password = ganti_dengan_password_anda
database.default.DBDriver = Postgre
database.default.DBPrefix =
database.default.port = 5432
database.default.charset = utf8
database.default.DBCollat =

# Opsional: password awal akun seeder (bawaan: Password123!)
# seed.defaultPassword = 'isi-sendiri'
```

File `.env` berisi rahasia dan **tidak boleh** di-commit. Pastikan `.gitignore` memuat baris `.env`.

### 4. Atur zona waktu

Buka `app/Config/App.php` lalu ubah:

```php
public string $appTimezone = 'Asia/Jakarta';
```

Tanpa ini, jam dokumen tampil dalam UTC (selisih 7 jam) dan tanggal bawaan di form bisa mundur sehari
pada dini hari.

### 5. Jalankan migrasi (membuat 16 tabel)

```bash
php spark migrate
```

### 6. Isi data awal

```bash
php spark db:seed DatabaseSeeder
```

`DatabaseSeeder` mengisi:

- 5 peran dan 5 akun pengguna
- 10 kategori, 16 produk (beserta saldo stok awal 0), 5 supplier, 5 customer
- **Data transaksi contoh** (PO, Goods Receipt, Sales Order, Delivery) lengkap dengan stok dan histori
  mutasinya, termasuk contoh dokumen yang dibatalkan

Seeder bersifat idempotent, jadi aman dijalankan berulang kali. Untuk instalasi yang bersih tanpa data
transaksi contoh, jalankan seeder satu per satu:

```bash
php spark db:seed RoleSeeder
php spark db:seed UserSeeder
php spark db:seed CategorySeeder
php spark db:seed ProductSeeder
php spark db:seed StockBalanceSeeder
php spark db:seed SupplierSeeder
php spark db:seed CustomerSeeder
```

### 7. Jalankan aplikasi

```bash
php spark serve
```

Buka `http://localhost:8080` lalu login dengan salah satu akun di bawah.

## Akun Awal

Semua akun memakai password awal **`Password123!`** (atau nilai `seed.defaultPassword` di `.env`).

| Username | Peran |
|---|---|
| `admin` | Admin |
| `purchasing` | Staf Purchasing |
| `gudang` | Staf Gudang |
| `sales` | Staf Sales |
| `manajer` | Manajer |

> **Penting:** password awal hanya untuk pengembangan. Di lingkungan lain, ganti semua password lewat
> menu **Pengguna > Reset Password**, atau isi `seed.defaultPassword` sebelum menjalankan seeder.

## Hak Akses per Peran

| Fitur | Admin | Purchasing | Gudang | Sales | Manajer |
|---|:---:|:---:|:---:|:---:|:---:|
| Master data | Ubah | Lihat | - | Lihat | - |
| Kelola pengguna | Ubah | - | - | - | - |
| Purchase Order | - | Ubah | Lihat | - | - |
| Goods Receipt | - | - | Ubah | - | - |
| Sales Order | - | - | Lihat | Ubah | - |
| Delivery | - | - | Ubah | - | - |
| Stok produk | Lihat | - | Lihat | Lihat | Lihat |
| Histori mutasi | Lihat | - | Lihat | - | Lihat |
| Laporan | Sesuai peran | Pembelian | Stok | Penjualan, stok | Semua |

Hak akses diperiksa di sisi server pada setiap permintaan, bukan hanya dengan menyembunyikan menu.
Aturan lengkapnya ada di `app/Config/Auth.php`.

## Aturan Bisnis Penting

- **Dokumen tidak pernah dihapus.** Kesalahan dikoreksi dengan pembatalan (disertai alasan wajib),
  lalu dokumen baru dibuat. Dokumen tidak dapat diedit setelah disimpan.
- **Stok hanya berubah lewat `StockService`.** Setiap perubahan selalu menulis catatan di
  `stock_movements` dan memperbarui saldo di `stock_balances` dalam satu database transaction.
- **Stok tidak boleh negatif.** Delivery dan pembatalan Goods Receipt ditolak bila stok tidak cukup.
- **PO Draft tidak mengubah stok.** Hanya PO berstatus Approved yang bisa diterima barangnya.
- **Penerimaan dan pengiriman bertahap.** Sisa kuantitas tidak disimpan, tetapi dihitung dari dokumen
  Goods Receipt atau Delivery yang berstatus Aktif.
- **PO dengan Goods Receipt aktif tidak bisa dibatalkan.** Batalkan Goods Receipt-nya lebih dulu.
- **Nomor dokumen otomatis:** `PO-YYYYMM-0001`, `GR-YYYYMM-0001`, `SO-YYYYMM-0001`, `DL-YYYYMM-0001`.
- **Total dihitung di server** dari kuantitas x harga satuan, bukan dari nilai kiriman form.

## Struktur Proyek

```
app/
  Config/        Auth (hak akses dan menu), Documents (status dan awalan nomor),
                 Master dan Purchasing (pesan), Routes, Filters
  Controllers/   Tipis: menerima request, memanggil Service, memilih View
  Services/      Logika bisnis dan validasi (StockService, DocumentNumberService, dst.)
  Models/        Akses data, query berparameter
  Filters/       AuthFilter dan GuestFilter
  Helpers/       auth_helper dan format_helper (Rupiah, tanggal, label status)
  Views/         Template Bootstrap (layouts/main sebagai kerangka bersama)
  Database/
    Migrations/  16 tabel (roles sampai stock_movements)
    Seeds/       Data awal dan data contoh
public/
  img/Logo.png   Logo aplikasi
```

## Backup dan Restore Database

Cadangan berkala dianjurkan karena data transaksi tidak pernah dihapus.

**Backup:**

```bash
pg_dump -U mini_erp -h localhost -F c -f ndt_backup.dump mini_erp
```

**Restore ke database kosong:**

```bash
createdb -U postgres -O mini_erp mini_erp_restore
pg_restore -U mini_erp -h localhost -d mini_erp_restore ndt_backup.dump
```

Setelah restore, arahkan `database.default.database` di `.env` ke database yang dipulihkan, lalu
bandingkan jumlah data dan saldo stok dengan database asal.

## Pemecahan Masalah

| Masalah | Penyebab dan solusi |
|---|---|
| `could not find driver` | Ekstensi `pgsql` belum aktif di `php.ini` |
| Composer: `zip extension and unzip/7z commands are both missing` | Aktifkan `extension=zip` di `php.ini`, buka terminal baru, lalu ulangi `composer install` |
| Error `intl` saat `composer install` atau `spark` | Aktifkan `extension=intl` di `php.ini` |
| `password authentication failed` | Periksa `username` dan `password` di `.env` |
| Tabel tidak ditemukan | Jalankan `php spark migrate` |
| Tidak bisa login | Jalankan `php spark db:seed UserSeeder`. Akun yang terkunci bisa dibuka Admin lewat menu Pengguna |
| Alamat mengandung `index.php` | Ubah `public string $indexPage = '';` di `app/Config/App.php` |
| Ingin login dua akun di dua tab | Cookie sesi dipakai bersama satu browser. Pakai jendela incognito, browser lain, atau host berbeda (`localhost` dan `127.0.0.1`, tambahkan keduanya di `$allowedHostnames` pada `App.php`) |
| `127.0.0.1` tidak bisa dibuka | Jalankan `php spark serve --host 0.0.0.0` |
| Logo tidak muncul | Pastikan file ada di `public/img/Logo.png` (huruf besar `L`) lalu tekan `Ctrl+F5` |

## Lisensi

Proyek internal PT Nusantara Distribusi. Hak penggunaan mengikuti kebijakan perusahaan.
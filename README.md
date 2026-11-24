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
10. [Dokumentasi](#dokumentasi)
11. [Pengujian](#pengujian)
12. [Backup dan Restore Database](#backup-dan-restore-database)
13. [Pemecahan Masalah](#pemecahan-masalah)
14. [Lisensi](#lisensi)

---

## Tentang Aplikasi

NDT adalah aplikasi web internal untuk mengelola alur barang dari pembelian ke supplier sampai
pengiriman ke customer, dengan stok yang selalu dapat ditelusuri. Pengembangannya mengacu pada
dokumen SRS Mini ERP (Inventory & Sales Management System), lihat bagian [Dokumentasi](#dokumentasi).

Alur utama:

```
Purchase Order (Draft -> Approved) -> Goods Receipt -> stok bertambah
Sales Order (Open)                 -> Delivery      -> stok berkurang
```

## Fitur

| Modul | Isi |
|---|---|
| Autentikasi | Login, logout, penguncian akun setelah 5 kali gagal (15 menit), sesi berakhir setelah 30 menit tidak aktif, proteksi CSRF |
| Master Data | Kategori, Produk (dengan `min_stock` dan `max_stock` opsional), Supplier, Customer |
| Purchasing | Purchase Order (Draft/Approved) dengan persetujuan Manajer, validasi kapasitas terhadap `max_stock`, Goods Receipt (penerimaan bertahap), pembatalan PO dan GR |
| Sales | Sales Order, Delivery (pengiriman bertahap), pembatalan SO dan Delivery |
| Inventory | Stok per produk, histori mutasi stok |
| Dashboard Purchasing | Widget produk yang perlu dipesan ulang (dengan tombol **Buat PO**) dan widget produk yang stoknya mendekati batas atas (`max_stock`) |
| Laporan | Pembelian, penjualan, stok, dan 10 produk terlaris |
| Administrasi | Kelola akun pengguna: tambah, ubah peran, aktif/nonaktif, reset password, buka kunci |

## Teknologi

- **Framework:** CodeIgniter 4 (pola MVC, logika bisnis di folder `Services`)
- **Database:** PostgreSQL
- **Tampilan:** Bootstrap 5.3 dan Bootstrap Icons (dimuat dari CDN, jadi perlu koneksi internet saat membuka halaman)
- **Bahasa:** PHP 8.5.12

## Kebutuhan Sistem

| Komponen | Versi / Keterangan |
|---|---|
| PHP | 8.5.12 |
| Ekstensi PHP | `intl`, `mbstring`, `json`, `pgsql` (`pdo_pgsql` opsional), serta `zip` (dipakai Composer untuk mengunduh paket) |
| PostgreSQL | 18.7 |
| Composer | 2.11.2, membutuhkan koneksi internet untuk mengunduh paket |
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
git clone <alamat-repository> mini-erp
cd mini-erp
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

### 5. Jalankan migrasi (membuat 17 tabel)

```bash
php spark migrate
```

### 6. Isi data awal

```bash
php spark db:seed DatabaseSeeder
```

`DatabaseSeeder` mengisi:

- 5 peran dan 5 akun pengguna
- 10 kategori, 16 produk, 5 supplier, 5 customer
- **Data transaksi contoh** (PO, Goods Receipt, Sales Order, Delivery) lengkap dengan stok dan histori
  mutasinya, termasuk contoh dokumen yang dibatalkan

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
| Purchase Order | - | Ubah (tanpa approve) | Lihat | - | Lihat, Approve |
| Goods Receipt | - | - | Ubah | - | - |
| Sales Order | - | - | Lihat | Ubah | - |
| Delivery | - | - | Ubah | - | - |
| Stok produk | Lihat | - | Lihat | Lihat | Lihat |
| Histori mutasi | Lihat | - | Lihat | - | Lihat |
| Laporan | Sesuai peran | Pembelian | Stok | Penjualan, stok | Semua |

> Catatan: peran **Manajer** pada Purchase Order mengikuti SRS (FR-09, BR-13). Sesuaikan tabel ini
> bila `app/Config/Auth.php` berbeda.

Hak akses diperiksa di sisi server pada setiap permintaan, bukan hanya dengan menyembunyikan menu.
Aturan lengkapnya ada di `app/Config/Auth.php`.

## Aturan Bisnis Penting

- **Dokumen transaksi tidak pernah dihapus.** PO, Goods Receipt, Sales Order, dan Delivery dikoreksi
  dengan pembatalan (disertai alasan wajib), lalu dokumen baru dibuat. Dokumen tidak dapat diedit setelah
  disimpan. Master data hanya bisa dihapus selama belum dipakai transaksi.
- **Stok hanya berubah lewat `StockService`.** Setiap perubahan selalu menulis catatan di
  `stock_movements` dan memperbarui saldo di `stock_balances` dalam satu database transaction.
  Bila ada langkah yang gagal, seluruh transaksi di-rollback.
- **Stok tidak boleh negatif.** Delivery dan pembatalan Goods Receipt ditolak bila stok tidak cukup.
  Pengiriman bersamaan untuk produk yang sama diamankan dengan row lock (`SELECT ... FOR UPDATE`).
- **PO Draft tidak mengubah stok.** Hanya PO berstatus Approved yang bisa diterima barangnya.
- **PO harus disetujui orang lain.** Pembuat PO tidak boleh menyetujui PO miliknya sendiri; hanya
  Manajer yang dapat menyetujui, dan PO yang sudah Approved tidak dapat disetujui ulang.
- **Kapasitas PO dibatasi `max_stock`.** Jumlah PO baru tidak boleh melebihi sisa kapasitas
  (`max_stock` - stok - PO Draft/Approved yang belum diterima). Produk tanpa `max_stock` tidak dibatasi.
- **Sales Order boleh melebihi stok, Delivery tidak.** SO tetap tersimpan dengan peringatan stok kurang;
  stok baru diperiksa dan dikurangi saat Delivery.
- **Penerimaan dan pengiriman bertahap.** Sisa kuantitas tidak disimpan, tetapi dihitung dari dokumen
  Goods Receipt atau Delivery yang berstatus Aktif.
- **Pembatalan berurutan.** PO dengan Goods Receipt aktif tidak bisa dibatalkan, begitu juga SO dengan
  Delivery aktif. Batalkan Goods Receipt atau Delivery-nya lebih dulu.
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
  Exceptions/    Exception khusus aplikasi
  Language/      File bahasa (pesan)
  Libraries/     Library pendukung
  ThirdParty/    Kode pihak ketiga
  Views/         Template Bootstrap (layouts/main sebagai kerangka bersama)
  Database/
    Migrations/  17 tabel (roles sampai stock_movements)
    Seeds/       Data awal dan data contoh
public/
  img/Logo.png   Logo aplikasi
dokumen_analisis/
  BRD/           Business Requirements Document
  SRS/           Software Requirements Specification
  Test Case/     Skenario pengujian
```

## Dokumentasi

Dokumen analisis ada di folder `dokumen_analisis/`:

| Dokumen | Lokasi | Isi |
|---|---|---|
| BRD | `dokumen_analisis/BRD/` | Kebutuhan bisnis |
| SRS | `dokumen_analisis/SRS/` | Kebutuhan fungsional dan non-fungsional (FR, BR, VR, NFR) |
| Test Case | `dokumen_analisis/Test Case/` | 35 skenario uji (TC-01 sampai TC-35) |

## Pengujian

Pengujian dilakukan secara manual mengikuti 35 skenario di folder `Test Case`. Tiap skenario memuat
langkah uji, hasil yang diharapkan, hasil aktual, bukti, dan status, serta merujuk ke kode kebutuhan
di SRS (misalnya FR-10, BR-03). Skenario mencakup stok, approval PO, pembatalan dokumen, concurrency,
laporan, hak akses, penguncian akun, session timeout, dan proteksi CSRF.

Jalankan `php spark db:seed DatabaseSeeder` terlebih dahulu agar data contoh tersedia.

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
| Request POST ditolak HTTP 403 | Token CSRF tidak terkirim atau kedaluwarsa. Muat ulang halaman lalu kirim ulang form |
| Alamat mengandung `index.php` | Ubah `public string $indexPage = '';` di `app/Config/App.php` |
| Ingin login dua akun di dua tab | Cookie sesi dipakai bersama satu browser. Pakai jendela incognito, browser lain, atau host berbeda (`localhost` dan `127.0.0.1`, tambahkan keduanya di `$allowedHostnames` pada `App.php`) |
| `127.0.0.1` tidak bisa dibuka | Jalankan `php spark serve --host 0.0.0.0` |
| Logo tidak muncul | Pastikan file ada di `public/img/Logo.png` (huruf besar `L`) lalu tekan `Ctrl+F5` |

## Lisensi

Proyek internal PT Nusantara Distribusi. Hak penggunaan mengikuti kebijakan perusahaan.
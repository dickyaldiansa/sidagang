# SIDAGANG

Sistem Informasi Harga, Stok, dan Distribusi Bahan Pokok untuk Dinas Perindustrian dan Perdagangan Kota Batam. Dibangun dengan Laravel 12, Blade, Bootstrap 5, dan ApexCharts.

## Fitur yang tersedia

- Login berbasis username dan role (admin, petugas pasar, petugas stok, validator, pimpinan).
- Dashboard data terverifikasi: KPI, kepatuhan pasar, harga antar pasar, pergerakan harga, early warning, dan ringkasan stok.
- Quick entry harga: seluruh komoditas sekaligus, navigasi Enter, pencarian, salin seluruh harga sebelumnya, status tidak tersedia, serta perubahan otomatis.
- Workflow laporan harga dan stok: draft, dikirim, terverifikasi, ditolak dengan catatan.
- Monitoring dan detail perbandingan periode sebelumnya.
- Master pasar, satuan, komoditas harga, komoditas stok, dan periode survey yang dinamis.
- Rekap bulanan matriks pasar × tanggal, export CSV (dapat dibuka Excel), serta print/PDF dari browser.
- Endpoint internal JSON untuk dashboard, harga terbaru/trend/perbandingan, dan stok terbaru/trend.
- Layout responsif untuk desktop, tablet, dan ponsel.

## Menjalankan aplikasi

Persyaratan minimum: PHP 8.2, Composer 2. Laravel 12 kompatibel dengan PHP 8.2;.

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
```

Pada Laragon/Apache, aplikasi dapat langsung dibuka melalui:

```text
http://localhost/sidagang
```

Pastikan Apache Laragon aktif dan modul `rewrite_module` tersedia. Tidak perlu menjalankan `php artisan serve`.

Konfigurasi bawaan menggunakan SQLite agar demo dapat langsung dijalankan. Untuk MySQL/MariaDB, ubah variabel `DB_*` di `.env`.

## Akun demo

Ganti seluruh password demo sebelum deployment.

## Verifikasi

```bash
php artisan test
php artisan view:cache
```

Test mencakup proteksi login, akses halaman utama, rendering dashboard/master/laporan, dan penyimpanan draft harga.

## Import workbook historis

Workbook dengan sheet `Harga Bapok` dan `Stok Bapok` dapat diimpor ulang secara idempotent:

```bash
php artisan sidagang:import-workbook "storage/app/imports/Harga Komoditas Bahan Pokok.xlsx"
```

Nilai `-` atau kosong disimpan sebagai `NULL/unavailable`. Laporan hasil import diberi status terverifikasi dan setiap proses dicatat dalam tabel `import_logs`.

## Catatan produksi

Implementasi ini adalah fondasi MVP yang dapat dijalankan. Integrasi template Excel asli, import historis dengan preview/mapping, generator PDF server-side berkop resmi, permission granular, notifikasi, dan backup terjadwal perlu diselesaikan serta diuji dengan workbook sumber sebelum go-live/UAT.

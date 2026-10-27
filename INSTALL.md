# Pemasangan di shared hosting / cPanel

## Persyaratan

- PHP minimal 8.1 (pengujian dilakukan dengan PHP 8.3).
- MySQL 5.7+/8.x atau MariaDB 10.4+ dengan InnoDB. Pengujian database: MariaDB 10.11.
- Ekstensi PHP `pdo_mysql`, sesi PHP aktif, serta fungsi `random_bytes` dan `password_hash`.
- Apache atau LiteSpeed yang membaca `.htaccess` dan mendukung `Require all denied`.
- HTTPS/SSL aktif untuk kamera HP/laptop.
- Tidak membutuhkan SSH, VPS, Composer, Node.js, cron, atau layanan QR berbayar.

## 1. Buat subdomain dan database

1. Buat subdomain melalui menu **Domains**, misalnya `register.domainanda.id`.
2. Catat document root subdomain tersebut. Lokasinya mengikuti pengaturan cPanel Anda, tidak selalu `public_html`.
3. Melalui **MySQL Database Wizard**, buat database, buat user database, lalu hubungkan user tersebut ke database dengan **ALL PRIVILEGES** untuk database aplikasi itu saja. Installer memerlukan izin membuat tabel dan indeks.
4. Catat nama database/user lengkap beserta prefiks cPanel.
5. Pilih PHP 8.3 atau versi yang memenuhi minimum, dan aktifkan `pdo_mysql` melalui menu PHP hosting.

## 2. Unggah berkas

1. Unggah ZIP melalui File Manager dan ekstrak.
2. Pindahkan **isi folder `hadir-php`** ke document root subdomain. `index.php`, `install.php`, `assets`, `app`, `config`, dan `.htaccess` harus berada di folder tujuan yang sama.
3. Aktifkan **Show Hidden Files** agar `.htaccess` ikut terlihat dan tidak tertinggal.
4. Gunakan permission berkas 644 dan folder 755. Folder `storage` harus bisa ditulis user PHP; gunakan 775 hanya jika konfigurasi kepemilikan hosting memerlukannya. Jangan gunakan 777.
5. Hapus ZIP dari folder publik setelah ekstraksi.

## 3. Konfigurasi database

Salin `config/config.example.php` menjadi `config/config.php` melalui File Manager, lalu edit:

```php
<?php
return [
    'app_name' => 'Hadir',
    'timezone' => 'Asia/Jakarta',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'prefikscp_database',
        'user' => 'prefikscp_user',
        'pass' => 'PASSWORD_DATABASE_ANDA',
        'charset' => 'utf8mb4',
    ],
    'install_key' => 'ISI_KUNCI_ACAK_RAHASIA_MINIMAL_24_KARAKTER',
    'force_secure_cookie' => false,
];
```

- Isi `install_key` dengan teks acak milik Anda minimal 24 karakter. Ini kunci akses installer, bukan password akun; jangan bagikan.
- Pertahankan tanda kutip PHP dengan benar. Jika password database mengandung `'` atau `\`, escape masing-masing menjadi `\'` atau `\\` dalam string PHP bertanda kutip tunggal.
- `host` mengikuti informasi penyedia hosting. Gunakan `127.0.0.1` jika localhost tidak sesuai dan penyedia mengizinkan koneksi TCP.
- Bila HTTPS diterminasi di reverse proxy tepercaya dan PHP tidak mendeteksi HTTPS, ubah `force_secure_cookie` ke `true` setelah memastikan situs hanya dibuka lewat HTTPS.

## 4. Jalankan installer

1. Buka `https://subdomain-anda/install.php`.
2. Pastikan semua persyaratan ditandai terpenuhi.
3. Masukkan kunci instalasi yang sama dengan konfigurasi.
4. Isi nama, username superadmin, dan kata sandi 10–72 karakter.
5. Klik **Instal & buat superadmin**. Tabel dibuat otomatis; tidak perlu impor SQL secara terpisah.
6. Masuk menggunakan akun yang baru dibuat.
7. **Hapus `install.php`** dari hosting. Installer juga terkunci oleh database setelah instalasi pertama.
8. Pastikan akses URL `.../storage/`, `.../database/schema.sql`, dan `.../config/config.php` ditolak (403). Jika tidak, minta hosting memperbaiki dukungan `.htaccess` sebelum memasukkan data tamu.

Jika database telah berisi tabel sistem lain, gunakan database baru khusus Hadir. Jangan mengulang installer pada database produksi atau menghapus tabel `installation` untuk mencoba memperbaiki error.

## 5. Siapkan acara

1. Superadmin membuka **Acara → Buat acara**.
2. **Tim & akses → Tambah akun**, pilih Admin dan acara penugasannya.
3. Admin login untuk mengisi tamu, mengatur detail acara, dan membuat petugas.
4. Buat satu tamu percobaan; unduh undangan; pindai dari HP lain dan coba scan ulang.
5. Jika uji berhasil, lanjutkan memasukkan data acara sesungguhnya.

## Pemasangan lokal Linux Mint (opsional)

Letakkan aplikasi di folder yang dilayani Apache, misalnya `/var/www/html/hadir`, dan buat database MySQL melalui phpMyAdmin. Isi konfigurasi lalu buka `http://localhost/hadir/install.php`. Akses kamera dari perangkat lain tetap memerlukan HTTPS; pengecualian localhost hanya berlaku di perangkat yang membuka browser itu sendiri.

Alternatif terminal setelah konfigurasi: `php -S localhost:8080` dari folder aplikasi. **Server bawaan PHP hanya untuk pengembangan**, karena tidak membaca `.htaccess`; jangan gunakan sebagai server publik.

## Pemeliharaan dan backup

- Gunakan backup cPanel: file aplikasi + database penuh, setidaknya sebelum acara dan sesudah rekap akhir. CSV hanya rekap, bukan backup database yang dapat memulihkan akun/QR.
- Simpan backup di luar folder publik. Saat memulihkan, kembalikan database beserta konfigurasi yang sesuai; ID dan token QR tetap terjaga jika database dipulihkan utuh.
- Jangan mengganti database setelah undangan dibagikan; QR bergantung pada token di database.
- Periksa `storage/error.log` melalui File Manager bila ada error. Jangan tampilkan log di halaman publik. Putar/hapus log lama setelah disalin aman agar kuota tidak habis.
- Perbarui PHP sesuai dukungan hosting dan uji scan sebelum acara besar. Kapasitas nyata mengikuti CPU, koneksi database, dan batas akun shared hosting; paket ini bukan hasil uji beban ribuan scanner bersamaan.

## Pemulihan akun

Superadmin dapat mengganti password akun lain dari **Tim & akses → Kelola**. Jika pemilik lupa password dan masih ada superadmin lain, minta superadmin tersebut meresetnya.

Jika seluruh superadmin terkunci dan hosting mempunyai Terminal/SSH, jalankan `php tools/reset-password.php USERNAME`. Script hanya bekerja melalui CLI, meminta password baru dua kali, dan memutus sesi lama. Tanpa Terminal, jalankan script lewat bantuan teknis hosting; jangan membuat halaman reset password publik.

## Pemecahan masalah

| Gejala | Langkah |
|---|---|
| Error 500 setelah upload | Periksa versi PHP, `.htaccess`, dan `storage/error.log`. Beberapa hosting melarang `Options -Indexes`; minta dukungan hosting atau hapus hanya baris tersebut, pertahankan aturan penolakan folder privat. |
| Koneksi database gagal | Periksa prefiks nama/user, password, host, port, dan hubungan user ke database. |
| could not find driver | Aktifkan `pdo_mysql` pada versi PHP subdomain yang digunakan. |
| Storage tidak dapat ditulis | Periksa ownership dan permission folder storage. Jangan mengubah seluruh aplikasi ke 777. |
| Kamera tidak muncul | Gunakan HTTPS, izinkan kamera, tutup aplikasi lain yang memakai kamera; gunakan browser modern. |
| QR tidak ditemukan | Pastikan acara yang dipilih benar, tamu belum dihapus, dan memakai QR dari instalasi ini. |
| Sesi berakhir / formulir ditolak | Muat ulang halaman dan login kembali. Nonaktifkan cache halaman aplikasi pada hosting/CDN. |
| Terlalu banyak percobaan login | Tunggu 15 menit. Batas diterapkan per username dan IP; jaringan bersama memiliki batas IP bersama. |
| Waktu tidak sesuai | Konfigurasi default Asia/Jakarta; data disimpan UTC, tampilan dan ekspor dikonversi sesuai timezone konfigurasi. |
| Impor CSV ditolak | Pakai UTF-8, koma, header nama,kategori, maksimum 1 MB/2.000 baris. Ekspor Excel ke CSV UTF-8 lalu periksa pemisah. |

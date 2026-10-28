# Hadir — Sistem Registrasi Acara Berbasis QR Code

**Hadir** adalah sistem registrasi dan pencatatan kehadiran tamu untuk berbagai acara. Setiap tamu memperoleh QR Code unik yang dapat dipindai oleh petugas menggunakan kamera laptop atau ponsel.

Aplikasi dibuat dengan **PHP Native dan MySQL/MariaDB**, memiliki tampilan responsif, dan dirancang agar dapat dijalankan pada shared hosting tanpa framework atau proses build di server.

## Fitur utama

- **Multiacara:** mengelola beberapa acara dengan daftar tamu dan akses terpisah.
- **Tiga peran pengguna:** superadmin, admin acara, dan petugas registrasi.
- **Undangan QR unik:** membuat QR untuk setiap tamu dan mengunduh undangan sebagai PNG.
- **Scan melalui browser:** menggunakan kamera laptop atau ponsel, tanpa memasang aplikasi tambahan.
- **Scan otomatis:** pilihan untuk melanjutkan pemindaian secara berkelanjutan atau menekan tombol lanjut setelah setiap tamu.
- **Sambutan suara:** browser dapat membacakan “Selamat datang, [nama tamu]” setelah check-in berhasil.
- **Check-in manual:** mencari tamu dan mencatat kehadirannya ketika kamera tidak tersedia.
- **Pencegahan kehadiran ganda:** QR yang sudah digunakan tidak dapat mencatat kehadiran lagi.
- **Impor dan ekspor CSV:** memasukkan daftar tamu serta mengunduh rekap kehadiran.
- **Ringkasan dan aktivitas:** melihat statistik kehadiran, kedatangan terakhir, serta riwayat aktivitas sesuai hak akses.

## Hak akses

| Fitur | Superadmin | Admin acara | Petugas |
|---|---|---|---|
| Melihat acara | Semua acara | Acara penugasan | Acara penugasan |
| Membuat acara | Ya | Tidak | Tidak |
| Mengubah detail acara | Semua acara | Acara sendiri yang aktif | Tidak |
| Mengarsipkan atau mengaktifkan acara | Ya | Tidak | Tidak |
| Mengelola akun | Semua akun lain | Petugas pada acara sendiri | Tidak |
| Mengelola tamu dan impor CSV | Semua acara aktif | Acara sendiri yang aktif | Tidak |
| Melihat undangan QR dan ekspor CSV | Semua acara | Acara sendiri | Tidak |
| Mencari tamu dan melakukan check-in | Acara yang dipilih | Acara sendiri | Acara sendiri |
| Melihat riwayat aktivitas | Semua atau berdasarkan acara | Acara sendiri | Tidak |
| Mengganti kata sandi sendiri | Ya | Ya | Ya |

Setiap admin dan petugas ditugaskan ke **satu acara**. Satu acara dapat memiliki beberapa admin dan petugas.

## Alur penggunaan

1. Superadmin membuat acara dan akun admin, lalu menugaskan admin ke acara tersebut.
2. Admin menambahkan petugas dan memasukkan daftar tamu secara manual atau melalui CSV.
3. Admin membuka **Lihat QR → Unduh PNG**, kemudian membagikan undangan kepada masing-masing tamu.
4. Petugas masuk ke sistem dan membuka halaman **Scan QR**.
5. Petugas mengaktifkan kamera serta memilih mode scan otomatis atau manual.
6. Saat QR valid dipindai, sistem mencatat waktu kehadiran dan petugas yang melakukan check-in.
7. Setelah acara, admin dapat mengunduh rekap kehadiran dalam format CSV.

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP Native |
| Database | MySQL / MariaDB dengan InnoDB |
| Frontend | HTML, CSS, dan JavaScript |
| Pembuatan QR | qrcode 1.5.4 |
| Pemindaian QR | jsQR 1.4.0 |
| Suara sambutan | Web Speech API, bergantung pada browser dan perangkat |

Pustaka QR disertakan dalam project. Instalasi di server tidak memerlukan Composer, Node.js, atau proses build.

## Kebutuhan sistem

- PHP **8.1 atau lebih baru**, dengan ekstensi `pdo_mysql` dan sesi PHP aktif.
- MySQL 5.7+/8.x atau MariaDB 10.4+ dengan dukungan InnoDB.
- Apache atau LiteSpeed yang mendukung aturan `.htaccess` pada paket.
- Browser modern dengan JavaScript aktif.
- **HTTPS** untuk mengakses kamera, kecuali saat membuka `localhost` pada perangkat yang sama.
- Folder `storage` dapat ditulis oleh proses PHP.

Source yang disiapkan telah diuji menggunakan PHP 8.3 dan MariaDB 10.11. Kamera dan suara perlu diuji pada perangkat yang akan digunakan saat acara.

## Instalasi singkat

1. Buat database dan pengguna MySQL, lalu berikan akses pengguna ke database tersebut.
2. Unggah dan ekstrak project ke document root domain atau subdomain.
3. Salin `config/config.example.php` menjadi `config/config.php`.
4. Isi konfigurasi database dan atur `install_key` dengan kunci acak rahasia minimal 24 karakter.
5. Buka `install.php` melalui browser dan buat akun superadmin.
6. Setelah instalasi berhasil, hapus `install.php` dari server.

Tabel dibuat otomatis oleh installer. Tidak ada username atau kata sandi bawaan.

Panduan lengkap pemasangan, pengecekan izin folder, backup, dan pemecahan masalah tersedia dalam **[INSTALL.md](INSTALL.md)**.

## Impor daftar tamu

Gunakan file CSV berformat **UTF-8** dengan pemisah koma dan header berikut:

```csv
nama,kategori
Ahmad Fauzi,Umum
Siti Aminah,VIP
```

Batas impor adalah **1 MB atau 2.000 baris** per proses. Impor bersifat penambahan: mengimpor file yang sama dua kali akan menambah data kembali. Jika ada baris yang tidak valid, seluruh proses impor dibatalkan.

## Keamanan dan backup

- Akses acara dan tindakan pengguna diperiksa berdasarkan peran serta penugasan acara.
- Setiap QR menggunakan token acak dan diperiksa terhadap acara yang dipilih.
- Proses check-in menggunakan transaksi database untuk mencegah pencatatan ganda.
- Formulir dilindungi CSRF; akses database menggunakan prepared statements; kata sandi disimpan dalam bentuk hash.
- Sesi menggunakan cookie HttpOnly dan SameSite. Perubahan akses akun memutus sesi lama.
- Terapkan HTTPS dan pastikan folder privat seperti `config`, `storage`, dan `database` terlindungi dari akses langsung melalui web.
- Cadangkan **database dan file aplikasi** secara berkala. Ekspor CSV adalah rekap, bukan backup database lengkap.
- Jangan mengunggah `config/config.php`, log, backup database, atau kredensial ke repository.

QR adalah bukti undangan yang dapat disalin. Petugas tetap perlu memeriksa kesesuaian nama tamu saat registrasi.

## Batasan

- Satu QR berlaku untuk satu tamu; belum ada pengaturan jumlah rombongan, kursi, atau sesi acara.
- Undangan dibagikan secara manual; sistem belum mengirim WhatsApp atau email otomatis.
- Check-in membutuhkan koneksi ke server dan belum mendukung mode offline.
- Dashboard diperbarui saat halaman dimuat ulang.
- Belum tersedia menu untuk membatalkan kehadiran atau memulihkan tamu yang dihapus.
- Suara sambutan bergantung pada browser, suara yang tersedia, dan pengaturan audio perangkat; beberapa browser dapat membutuhkan interaksi pengguna terlebih dahulu.

## Struktur project

```text
hadir-qr/
├── app/                 # Logika aplikasi dan tampilan
├── assets/              # CSS, JavaScript, ikon, dan pustaka QR
├── config/              # Contoh dan konfigurasi aplikasi
├── database/            # Skema database
├── storage/             # Log aplikasi
├── tests/               # Catatan validasi
├── tools/               # Pemeriksaan dan pemulihan melalui CLI
├── index.php            # Halaman utama dan routing
├── install.php          # Installer awal
├── INSTALL.md           # Panduan instalasi
└── README.md            # Dokumentasi project
```

## Pemeriksaan tambahan

Jika tersedia akses Terminal atau SSH, jalankan:

```bash
php tools/check.php
```

Perintah tersebut memeriksa konfigurasi dasar dan koneksi database. Rincian hasil pengujian serta daftar pemeriksaan sebelum acara tersedia di **[tests/VALIDATION.md](tests/VALIDATION.md)**.


## Lisensi

Project ini menggunakan [MIT License](LICENSE).

Hak cipta © 2026 Muhammad Ulinnuha.
Lisensi pustaka pihak ketiga tersedia di folder assets/vendor.

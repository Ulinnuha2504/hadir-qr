# Hadir — Registrasi Acara QR

Versi PHP Native + MySQL untuk shared hosting. Desain hijau lembut, responsif, tanpa framework, Composer, Node.js, atau build di server. Semua CSS, JavaScript, ikon, dan pustaka QR disertakan lokal.

## Mulai

Baca **INSTALL.md** untuk pemasangan melalui cPanel. Unggah isi paket ke document root subdomain, isi `config/config.php`, lalu buka `install.php`. Installer membuat tabel dan satu akun superadmin pilihan Anda. Tidak ada akun/password bawaan.

## Pembagian akses

| Fitur | Superadmin | Admin / ketua acara | Petugas |
|---|---|---|---|
| Melihat acara | Semua | Satu acara penugasan | Satu acara penugasan |
| Membuat acara | Ya | Tidak | Tidak |
| Mengubah detail acara | Semua | Acara sendiri yang aktif | Tidak |
| Mengarsipkan / mengaktifkan acara | Ya | Tidak | Tidak |
| Mengelola akun | Semua, kecuali mengubah akun sendiri melalui form tim | Hanya petugas acara sendiri | Tidak |
| Mengelola tamu, impor CSV | Semua acara aktif | Acara sendiri yang aktif | Tidak |
| Melihat / mengunduh QR, ekspor CSV | Semua acara | Acara sendiri | Tidak |
| Mencari tamu, scan, check-in manual | Acara dipilih | Acara sendiri | Acara sendiri |
| Melihat aktivitas | Semua / filter acara | Acara sendiri | Tidak |
| Mengganti kata sandi sendiri | Ya | Ya | Ya |

Satu akun admin/petugas ditugaskan ke tepat satu acara. Satu acara dapat memiliki beberapa admin/petugas. Superadmin dapat membuat akun superadmin tambahan. Penonaktifan akun, pergantian peran/penugasan, dan reset kata sandi memutus sesi lama. Akun sendiri tidak dapat dinonaktifkan melalui daftar tim.

## Alur penggunaan

1. Superadmin membuat acara.
2. Superadmin membuat admin dan menugaskannya ke acara tersebut.
3. Admin membuat petugas serta memasukkan daftar tamu, satu per satu atau CSV.
4. Buka **Lihat QR → Unduh PNG**, lalu bagikan gambar kepada tamu secara pribadi. Tamu tidak perlu login.
5. Petugas masuk melalui browser HP/laptop, membuka **Scan QR**, dan mengizinkan kamera.
6. Saat QR terbaca, hasil tampil. Tekan **Scan tamu berikutnya** untuk melanjutkan.
7. Jika kamera bermasalah, cari nama di Daftar tamu lalu klik **Check-in**.
8. Admin mengekspor rekap CSV setelah acara.

## Fitur

- Multiacara dengan data dan otorisasi terpisah.
- QR acak 192-bit untuk setiap tamu; tidak memakai ID berurutan sebagai kode undangan.
- Validasi acara dan penguncian transaksi database saat check-in. Dua petugas memindai QR sama hanya menghasilkan satu kehadiran.
- QR dari acara lain / tamu dihapus ditolak. QR lama tetap sama ketika nama/kategori tamu diedit.
- Pencatatan waktu dan petugas check-in.
- Daftar tamu dipaginasi 25 baris; pencarian dan filter status di server.
- CSV UTF-8, pemisah koma, header `nama,kategori`; maksimum 1 MB / 2.000 baris per impor.
- Impor bersifat penambahan; nama sama tidak dianggap duplikat karena dua orang dapat bernama sama. Jangan impor ulang file yang sama kecuali memang ingin menambah tamu.
- Kesalahan baris CSV membatalkan seluruh impor tersebut.
- Undangan PNG dan cetak / simpan PDF melalui browser.
- Acara diarsipkan tetap dapat dilihat dan diekspor; perubahan tamu dan check-in ditutup.
- Tamu belum hadir dapat dihapus dari daftar (soft delete); tamu sudah hadir tidak dapat dihapus melalui antarmuka.
- Riwayat aktivitas, CSRF, prepared statements PDO, password hashing, sesi HttpOnly/SameSite, dan batas percobaan login.

## Batasan yang disengaja

- Satu QR mewakili satu tamu, tanpa jumlah rombongan / kursi / sesi acara.
- QR merupakan bukti undangan yang dapat disalin. Petugas tetap perlu memastikan nama pemegang QR; sistem tidak memverifikasi identitas fisik.
- Distribusi undangan dilakukan sendiri; tidak mengirim WhatsApp/email otomatis.
- Tidak ada reset kehadiran atau pemulihan tamu dihapus melalui UI. Data historis dijaga; koreksi oleh pemilik database harus dilakukan hati-hati dan dibackup terlebih dahulu.
- Tidak ada mode offline. Check-in memerlukan koneksi ke server; jika koneksi putus, sistem tidak menyatakan berhasil sebelum server menyimpan data.
- Dashboard diperbarui saat halaman dimuat ulang; tidak memakai WebSocket atau polling terus-menerus.
- Daftar pemilih/pengelolaan acara dibatasi 500 acara terbaru untuk menjaga UI ringan.
- Gunakan browser modern dengan JavaScript aktif. Kamera fisik serta kompatibilitas hosting Anda tetap perlu dicoba setelah instalasi.

## Struktur

- `index.php`: pintu masuk dan rute query `?page=...`, tidak memerlukan rewrite.
- `install.php`: installer pertama; hapus setelah selesai.
- `app/bootstrap.php`: koneksi, sesi, validasi, otorisasi, check-in.
- `app/actions.php`: tindakan POST.
- `app/views.php`: halaman server-rendered.
- `assets/app.css`, `assets/app.js`: tampilan dan interaksi.
- `assets/vendor/`: jsQR 1.4.0 dan qrcode 1.5.4 serta lisensi.
- `database/schema.sql`: tabel InnoDB dan indeks.
- `config/config.example.php`: contoh konfigurasi tanpa rahasia nyata.
- `storage/`: log kesalahan privat.
- `tools/`: pemeriksaan CLI dan pemulihan kata sandi melalui terminal.
- `tests/VALIDATION.md`: hasil pengujian dan pemeriksaan penerimaan hosting.

Tidak menyertakan data percobaan, akun tes, konfigurasi database nyata, atau database dari versi situs sebelumnya. Ini instalasi baru, bukan migrasi otomatis dari versi Cloudflare.

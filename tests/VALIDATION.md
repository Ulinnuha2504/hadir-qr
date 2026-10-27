# Validasi Hadir PHP — 26 Oktober 2026

Pengujian dilakukan pada PHP 8.3.6 dan MariaDB 10.11 dengan database khusus percobaan. Pengujian HTTP memakai empat worker PHP dan sesi terpisah untuk superadmin, admin acara A/B, serta petugas acara A/B.

## Hasil

| Pemeriksaan | Hasil |
|---|---|
| Sintaks seluruh file PHP dan JavaScript aplikasi | Lulus |
| Installer membuat tabel dan akun; instalasi kedua ditolak | Lulus |
| Login dan pembuatan acara serta akun | Lulus |
| Tamu tersimpan dan teks berbahaya ditampilkan sebagai teks | Lulus |
| Petugas dilarang mengelola akun / ekspor data / mengubah tamu | Lulus |
| Admin acara B dilarang mengubah tamu/acara atau membuka undangan acara A | Lulus |
| Admin mengirim role superadmin dan acara B: server tetap membuat petugas acara A | Lulus |
| QR acara lain ditolak | Lulus |
| Check-in pertama tersimpan, check-in kedua ditolak | Lulus |
| Dua sesi mengirim check-in simultan: hanya satu berhasil dan satu audit tercatat | Lulus |
| CSRF salah ditolak | Lulus |
| Tamu sudah hadir tidak dapat dihapus | Lulus |
| Ekspor CSV dan halaman undangan | Lulus |
| Impor CSV valid dan rollback seluruh impor jika ada baris invalid | Lulus |
| Penonaktifan akun memutus sesi lama | Lulus |
| Arsip acara menutup perubahan tamu dan scanner | Lulus |
| Penggantian kata sandi sendiri | Lulus |
| Pembuatan QR dan decode kembali dengan jsQR | Lulus |
| Tampilan desktop dashboard dan undangan di browser | Diperiksa |
| Unduh PNG dari browser | Berhasil |

## Batas pemeriksaan

- Belum diinstal di akun hosting pengguna; konfigurasi Apache/LiteSpeed, SSL, permission, dan batas resource perlu diverifikasi di sana.
- Kamera fisik HP/laptop belum diuji. Pustaka encoder/decoder QR telah diuji dengan encode/decode.
- CSS responsif disertakan; belum dilakukan pengujian pada setiap perangkat/browser.
- Tidak ada klaim telah diuji beban besar atau audit keamanan independen.

## Pemeriksaan setelah instalasi

1. Masuk sebagai superadmin, buat dua acara percobaan.
2. Buat admin/petugas per acara; coba akses ID acara lain melalui URL dan formulir. Akses harus ditolak.
3. Tambahkan tamu, unduh PNG, pindai dengan kamera HP petugas pada acara yang benar.
4. Pindai ulang dan pindai pada acara lain: keduanya harus ditolak.
5. Uji check-in dua petugas pada QR sama; hanya satu kehadiran tercatat.
6. Coba CSV contoh, pencarian, filter, ekspor, serta arsip acara.
7. Pastikan URL folder app/config/storage/database/tools/tests ditolak server (403).
8. Hapus install.php dan ZIP dari document root.
9. Cadangkan database dan file melalui cPanel sebelum penggunaan acara sesungguhnya.

CLI opsional: `php tools/check.php`. Script memeriksa PHP, PDO MySQL, konfigurasi, storage, koneksi, dan status instalasi; tidak menampilkan password.

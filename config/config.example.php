<?php
// Salin menjadi config.php, lalu isi sesuai database cPanel Anda.
return [
    'app_name' => 'Hadir',
    'timezone' => 'Asia/Jakarta',
    'db' => ['host' => 'localhost', 'port' => 3306, 'name' => 'cpanel_hadir', 'user' => 'cpanel_user', 'pass' => '', 'charset' => 'utf8mb4'],
    // Ganti dengan teks acak rahasia minimal 24 karakter, bukan password akun.
    'install_key' => 'GANTI_DENGAN_KUNCI_ACAK_MINIMAL_24_KARAKTER',
    // Hanya true bila HTTPS diakhiri reverse proxy hosting yang tepercaya.
    'force_secure_cookie' => false,
];

<?php
require __DIR__.'/app/bootstrap.php';
$checks=['PHP 8.1 atau lebih baru'=>version_compare(PHP_VERSION,'8.1.0','>='),'Ekstensi PDO MySQL'=>extension_loaded('pdo_mysql'),'Konfigurasi config/config.php'=>is_array($config),'Folder storage dapat ditulis'=>is_writable(__DIR__.'/storage')];
$done=false;$error='';
if($config){try{$done=(bool)sql('SELECT id FROM installation WHERE id=1')->fetchColumn();}catch(Throwable $ex){/* Schema belum dibuat; diperiksa saat instalasi. */}}
if($done){http_response_code(403);exit('Instalasi sudah selesai. Hapus install.php dari hosting. Masuk melalui index.php.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        verify_csrf();foreach($checks as $ok)if(!$ok)throw new DomainException('Lengkapi persyaratan instalasi terlebih dahulu.');
        $key=(string)($config['install_key']??'');
        if(strlen($key)<24||preg_match('/GANTI_|ISI_KUNCI/i',$key)||!hash_equals($key,(string)($_POST['install_key']??'')))throw new DomainException('Kunci instalasi tidak sesuai. Atur kunci acak rahasia minimal 24 karakter di config/config.php.');
        $name=text_value('name',120);$username=strtolower(text_value('username',50));if(!valid_username($username))throw new DomainException('Username harus 3–50 karakter: a-z, angka, titik, garis bawah, minus.');$pass=password_value();
        $statements=explode(';',file_get_contents(__DIR__.'/database/schema.sql'));foreach($statements as $s)if(trim($s)!=='')db()->exec($s);
        db()->beginTransaction();
        sql('INSERT INTO installation (id) VALUES (1)');
        sql("INSERT INTO users (name,username,password_hash,role) VALUES (?,?,?,'superadmin')",[$name,$username,password_hash($pass,PASSWORD_DEFAULT)]);
        db()->commit();flash('Instalasi berhasil. Masuk dengan akun superadmin Anda dan hapus install.php.');go(url('login'));
    }catch(Throwable $ex){if(db_available_transaction())db()->rollBack();error_log((string)$ex);$error=$ex instanceof DomainException?$ex->getMessage():'Instalasi gagal. Periksa kredensial/izin database dan storage/error.log. Jika sudah terpasang, buka index.php.';}
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalasi Hadir</title><link rel="stylesheet" href="assets/app.css"></head><body><main class="auth-wrap"><section class="auth-card install"><a class="brand" href="index.php"><span class="brand-icon">▦</span>hadir<span>.</span></a><div class="eyebrow">SELAMAT DATANG</div><h1>Siap menyambut tamu?</h1><p>Hubungkan database, buat akun pemilik, lalu mulai acara pertama Anda.</p><div class="notice">Salin <b>config/config.example.php</b> menjadi <b>config/config.php</b>. Isi database dan ganti install_key dengan teks acak rahasia. Tidak ada akun atau kata sandi bawaan.</div><ul class="checks"><?php foreach($checks as $label=>$ok):?><li><?= $ok?'✓':'○' ?> <?=e($label)?></li><?php endforeach;?></ul><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?><form method="post"><?=csrf_field()?><label>Kunci instalasi<input name="install_key" type="password" required autocomplete="off"></label><label>Nama pemilik<input name="name" required maxlength="120" autocomplete="name"></label><label>Username superadmin<input name="username" required minlength="3" maxlength="50" autocomplete="username" pattern="[a-z0-9_.-]{3,50}"></label><label>Kata sandi<input name="password" type="password" minlength="10" maxlength="72" required autocomplete="new-password"></label><button class="btn primary full" <?=in_array(false,$checks,true)?'disabled':''?>>Instal & buat superadmin →</button></form><p class="fine">Setelah berhasil, hapus install.php melalui File Manager hosting.</p></section></main></body></html>

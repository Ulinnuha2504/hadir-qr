<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/../app/bootstrap.php';
$username=strtolower(trim($argv[1]??''));
if(!valid_username($username)){fwrite(STDERR,"Gunakan: php tools/reset-password.php USERNAME\n");exit(1);}
$u=sql('SELECT id,username FROM users WHERE username=?',[$username])->fetch();
if(!$u){fwrite(STDERR,"Username tidak ditemukan.\n");exit(1);}
fwrite(STDOUT,"Pemulihan akun @".$username.". Input password mungkin terlihat di terminal.\nPassword baru (10–72 karakter): ");$p=rtrim(fgets(STDIN)?:'',"\r\n");
fwrite(STDOUT,"Ulangi password: ");$again=rtrim(fgets(STDIN)?:'',"\r\n");
if($p!==$again||strlen($p)<10||strlen($p)>72){fwrite(STDERR,"Password tidak cocok / panjang tidak sesuai.\n");exit(1);}
db()->beginTransaction();
sql('UPDATE users SET password_hash=?,session_version=session_version+1,active=1 WHERE id=?',[password_hash($p,PASSWORD_DEFAULT),$u['id']]);
sql('INSERT INTO audit_logs (action,target_id) VALUES (?,?)',['auth.password',(int)$u['id']]);db()->commit();
fwrite(STDOUT,"Password diperbarui, akun aktif, sesi lama diputus.\n");

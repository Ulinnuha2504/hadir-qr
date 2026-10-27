<?php
declare(strict_types=1);
const ROOT = __DIR__ . '/..';
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/storage/error.log');
$configuration = ROOT . '/config/config.php';
$config = is_file($configuration) ? require $configuration : null;
date_default_timezone_set($config['timezone'] ?? 'Asia/Jakarta');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($config['force_secure_cookie'] ?? false);
session_name('hadir_'.substr(hash('sha256',realpath(ROOT)?:ROOT),0,12));
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');
header("Permissions-Policy: camera=(self), microphone=(), geolocation=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
header('Cache-Control: no-store');
function db(): PDO {
    global $config; static $pdo;
    if (!$config) throw new RuntimeException('Konfigurasi belum tersedia.');
    if (!$pdo) { $d=$config['db']; $pdo=new PDO('mysql:host='.$d['host'].';port='.(int)$d['port'].';dbname='.$d['name'].';charset=utf8mb4',$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); $pdo->exec("SET time_zone = '+00:00'"); }
    return $pdo;
}
function sql(string $s,array $args=[]): PDOStatement { $q=db()->prepare($s);$q->execute($args);return $q; }
function e($s): string {return htmlspecialchars((string)($s??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function url(string $page='dashboard', array $args=[]): string {return 'index.php?'.http_build_query(['page'=>$page]+$args);}
function go(string $url): never {header('Location: '.$url);exit;}
function csrf(): string {return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function csrf_field(): string {return '<input type="hidden" name="csrf" value="'.e(csrf()).'">';}
function verify_csrf(): void {if(!hash_equals(csrf(),(string)($_POST['csrf']??'')))throw new DomainException('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');}
function flash(string $message,string $kind='success'): void {$_SESSION['flash']=[$message,$kind];}
function user(): ?array {
    static $resolved=false,$u=null;
    if($resolved)return $u;$resolved=true;
    if(empty($_SESSION['uid']))return null;
    if(time()-($_SESSION['last_seen']??0)>7200){unset($_SESSION['uid']);return null;}
    $u=sql('SELECT id,name,username,role,event_id,active,session_version FROM users WHERE id=?',[$_SESSION['uid']])->fetch();
    if(!$u || !$u['active'] || (int)$u['session_version']!==(int)($_SESSION['version']??0)){unset($_SESSION['uid']);$u=null;return null;}
    $_SESSION['last_seen']=time();return $u;
}
function login_required(): array {$u=user();if(!$u)go(url('login'));return $u;}
function role_required(array $roles): array {$u=login_required();if(!in_array($u['role'],$roles,true))throw new DomainException('Anda tidak memiliki izin untuk tindakan ini.');return $u;}
function event_access(int $id,bool $manage=false,bool $active=false): array {
    $u=login_required(); if($manage&&$u['role']==='petugas')throw new DomainException('Fitur ini khusus pengelola acara.');
    if($u['role']!=='superadmin' && (int)$u['event_id']!==$id)throw new DomainException('Acara ini bukan penugasan Anda.');
    $ev=sql('SELECT * FROM events WHERE id=?'.(db()->inTransaction()?' FOR UPDATE':''),[$id])->fetch();if(!$ev)throw new DomainException('Acara tidak ditemukan.');
    if($active && $ev['status']!=='active')throw new DomainException('Acara diarsipkan. Check-in dan perubahan tamu ditutup.');return $ev;
}
function selected_event(): ?array {
    $u=login_required();$id=$u['role']==='superadmin'?(int)($_GET['event']??$_POST['event_id']??0):(int)$u['event_id'];
    if(!$id)return null;return event_access($id);
}
function audit(string $action,?int $event=null,?int $target=null): void {sql('INSERT INTO audit_logs (user_id,event_id,action,target_id) VALUES (?,?,?,?)',[user()['id']??null,$event,$action,$target]);}
function text_value(string $key,int $max=120,bool $required=true): string {
    $v=trim((string)($_POST[$key]??''));if(($required&&$v==='')||strlen($v)>$max||!preg_match('//u',$v))throw new DomainException('Isian '.str_replace('_',' ',$key).' tidak valid (maksimal '.$max.' byte).');return $v;
}
function valid_username(string $u): bool {return (bool)preg_match('/^[a-z0-9_.-]{3,50}$/',$u);}
function password_value(): string {$p=(string)($_POST['password']??'');if(strlen($p)<10||strlen($p)>72)throw new DomainException('Kata sandi harus 10–72 karakter.');return $p;}
function token(): string {return bin2hex(random_bytes(24));}
function date_time(?string $date,string $format='d M Y · H:i'): string {if(!$date)return '—';return (new DateTimeImmutable($date,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format($format);}
function event_time(): ?string {$v=trim((string)($_POST['starts_at']??''));if($v==='')return null;$d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$v);if(!$d||$d->format('Y-m-d\TH:i')!==$v)throw new DomainException('Tanggal acara tidak valid.');return $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
function json_reply(array $data,int $status=200): never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function checkin(int $event,string $code): array {
    if(str_starts_with($code,'HADIR:'))$code=substr($code,6);
    if(!preg_match('/^[a-f0-9]{48}$/',$code))throw new DomainException('Format QR tidak valid. Gunakan QR undangan dari sistem ini.');
    db()->beginTransaction();try {
        event_access($event,false,true);
        $g=sql('SELECT * FROM guests WHERE event_id=? AND token=? AND deleted_at IS NULL FOR UPDATE',[$event,$code])->fetch();
        if(!$g)throw new DomainException('QR tidak ditemukan pada acara ini.');
        if($g['checked_at'])throw new DomainException($g['name'].' sudah hadir pada '.date_time($g['checked_at']).'.');
        sql('UPDATE guests SET checked_at=UTC_TIMESTAMP(),checked_by=? WHERE id=? AND checked_at IS NULL',[user()['id'],$g['id']]);
        audit('guest.checkin',$event,(int)$g['id']);db()->commit();return ['name'=>$g['name'],'category'=>$g['category'],'time'=>date_time(gmdate('Y-m-d H:i:s'))];
    } catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}
}
set_exception_handler(function(Throwable $ex): void {
    if(db_available_transaction())db()->rollBack();
    $ref=bin2hex(random_bytes(5));error_log('['.$ref.'] '.$ex);
    http_response_code(500);echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/app.css"><main class="auth-wrap"><section class="auth-card"><h1>Halaman belum dapat dibuka</h1><p>Periksa konfigurasi database atau hubungi pengelola. Referensi: '.e($ref).'</p><a href="index.php">Kembali</a></section></main></html>';
});
function db_available_transaction(): bool {try{return db()->inTransaction();}catch(Throwable $ex){return false;}}

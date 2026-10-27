<?php
declare(strict_types=1);
function handle_post(): never {
    $a=(string)($_POST['action']??'');$ajax=$a==='scan';$back=url('dashboard');
    try {
        verify_csrf();
        if($a==='login') {
            $username=strtolower(trim((string)($_POST['username']??'')));$password=(string)($_POST['password']??'');
            if(strlen($username)>50||strlen($password)>200)throw new DomainException('Username atau kata sandi salah.');
            $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
            foreach([hash('sha256','ip:'.$ip)=>60,hash('sha256','user:'.$username)=>8] as $bucket=>$limit) {
                db()->beginTransaction();
                sql('INSERT IGNORE INTO login_attempts (bucket,attempts,first_at) VALUES (?,0,UTC_TIMESTAMP())',[$bucket]);
                $r=sql('SELECT attempts,first_at FROM login_attempts WHERE bucket=? FOR UPDATE',[$bucket])->fetch();
                if(strtotime($r['first_at'].' UTC')<time()-900){sql('UPDATE login_attempts SET attempts=0,first_at=UTC_TIMESTAMP() WHERE bucket=?',[$bucket]);$r['attempts']=0;}
                if((int)$r['attempts']>=$limit){db()->commit();throw new DomainException('Terlalu banyak percobaan masuk. Coba lagi setelah 15 menit.');}
                sql('UPDATE login_attempts SET attempts=attempts+1 WHERE bucket=?',[$bucket]);db()->commit();
            }
            $u=sql('SELECT * FROM users WHERE username=?',[$username])->fetch();
            $hash=$u['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            if(!password_verify($password,$hash)||!$u||!$u['active'])throw new DomainException('Username atau kata sandi salah.');
            session_regenerate_id(true);$_SESSION=['uid'=>(int)$u['id'],'version'=>(int)$u['session_version'],'last_seen'=>time(),'csrf'=>bin2hex(random_bytes(32))];
            sql('DELETE FROM login_attempts WHERE bucket=? OR first_at < UTC_TIMESTAMP() - INTERVAL 1 DAY',[hash('sha256','user:'.$username)]);
            audit('auth.login',$u['event_id']?(int)$u['event_id']:null,(int)$u['id']);go(url());
        }
        $u=login_required();
        if($a==='logout'){$_SESSION=[];session_destroy();go(url('login'));}
        if($a==='scan') { $result=checkin((int)($_POST['event_id']??0),trim((string)($_POST['code']??'')));json_reply(['ok'=>true,'guest'=>$result]); }
        if($a==='password') {
            if(!password_verify((string)($_POST['current_password']??''),sql('SELECT password_hash FROM users WHERE id=?',[$u['id']])->fetchColumn()))throw new DomainException('Kata sandi saat ini tidak sesuai.');
            $new=password_value();sql('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$u['id']]);$_SESSION['version']=(int)$u['session_version']+1;session_regenerate_id(true);audit('auth.password',null,(int)$u['id']);flash('Kata sandi diperbarui. Sesi lain otomatis keluar.');go(url('account'));
        }
        $eventId=(int)($_POST['event_id']??0);$back=url('dashboard',$eventId?['event'=>$eventId]:[]);
        db()->beginTransaction();
        switch($a) {
            case 'event_save':
                role_required(['superadmin','admin']);
                if($eventId){event_access($eventId,true);if($u['role']==='admin' && event_access($eventId)['status']!=='active')throw new DomainException('Acara telah diarsipkan. Hubungi superadmin.');}
                elseif($u['role']!=='superadmin')throw new DomainException('Hanya superadmin dapat membuat acara.');
                $name=text_value('name',160);$location=text_value('location',200,false);$desc=text_value('description',500,false);$starts=event_time();
                if($eventId)sql('UPDATE events SET name=?,starts_at=?,location=?,description=? WHERE id=?',[$name,$starts,$location,$desc,$eventId]);
                else{sql('INSERT INTO events (name,starts_at,location,description) VALUES (?,?,?,?)',[$name,$starts,$location,$desc]);$eventId=(int)db()->lastInsertId();}
                audit('event.save',$eventId,$eventId);$back=url('events');flash('Detail acara tersimpan.');break;
            case 'event_status':
                role_required(['superadmin']);$ev=event_access($eventId);
                sql('UPDATE events SET status=? WHERE id=?',[$ev['status']==='active'?'archived':'active',$eventId]);audit('event.status',$eventId,$eventId);$back=url('events');flash('Status acara diperbarui.');break;
            case 'guest_save':
                event_access($eventId,true,true);$id=(int)($_POST['id']??0);$name=text_value('name',160);$category=text_value('category',60);
                if($id){$old=sql('SELECT id FROM guests WHERE id=? AND event_id=? AND deleted_at IS NULL',[$id,$eventId])->fetch();if(!$old)throw new DomainException('Tamu tidak ditemukan.');sql('UPDATE guests SET name=?,category=? WHERE id=? AND event_id=?',[$name,$category,$id,$eventId]);}
                else{sql('INSERT INTO guests (event_id,name,category,token) VALUES (?,?,?,?)',[$eventId,$name,$category,token()]);$id=(int)db()->lastInsertId();}
                audit('guest.save',$eventId,$id);$back=url('guests',['event'=>$eventId]);flash('Tamu tersimpan. Undangan QR siap diunduh.');break;
            case 'guest_remove':
                event_access($eventId,true,true);$id=(int)($_POST['id']??0);$r=sql('UPDATE guests SET deleted_at=UTC_TIMESTAMP() WHERE id=? AND event_id=? AND deleted_at IS NULL AND checked_at IS NULL',[$id,$eventId]);
                if(!$r->rowCount())throw new DomainException('Tamu tidak ditemukan atau sudah hadir. Tamu yang sudah hadir tidak dapat dihapus.');
                audit('guest.remove',$eventId,$id);$back=url('guests',['event'=>$eventId]);flash('Tamu dihapus dari daftar; QR lama tidak berlaku.');break;
            case 'guest_import':
                event_access($eventId,true,true);
                if(!isset($_FILES['csv'])||$_FILES['csv']['error']!==UPLOAD_ERR_OK||$_FILES['csv']['size']>1048576||!is_uploaded_file($_FILES['csv']['tmp_name']))throw new DomainException('Unggah CSV valid berukuran maksimal 1 MB.');
                $f=fopen($_FILES['csv']['tmp_name'],'r');$head=fgetcsv($f,0,',','"','');
                if(!$head)throw new DomainException('File CSV kosong.');$head[0]=preg_replace('/^\xEF\xBB\xBF/','',$head[0]);
                if(array_map('strtolower',array_map('trim',$head))!==['nama','kategori'])throw new DomainException('Header CSV harus: nama,kategori (pemisah koma).');
                $count=0;$insert=db()->prepare('INSERT INTO guests (event_id,name,category,token) VALUES (?,?,?,?)');
                while(($row=fgetcsv($f,0,',','"',''))!==false){if($row===[null]||($row[0]??'')==='')continue;if(++$count>2000)throw new DomainException('Maksimal 2.000 tamu per impor.');$name=trim($row[0]);$cat=trim($row[1]??'')?:'Umum';if($name===''||count($row)>2||strlen($name)>160||strlen($cat)>60||!preg_match('//u',$name.$cat))throw new DomainException('Data pada baris '.($count+1).' tidak valid. Gunakan UTF-8.');$insert->execute([$eventId,$name,$cat,token()]);}
                fclose($f);if(!$count)throw new DomainException('CSV tidak berisi tamu.');audit('guest.import',$eventId);$back=url('guests',['event'=>$eventId]);flash($count.' tamu berhasil diimpor. Impor ulang akan menambah data baru.');break;
            case 'user_save':
                role_required(['superadmin','admin']);$id=(int)($_POST['id']??0);$name=text_value('name',120);$username=strtolower(text_value('username',50));
                if(!valid_username($username))throw new DomainException('Username: 3–50 karakter a-z, angka, titik, garis bawah, atau tanda minus.');
                $role=$u['role']==='admin'?'petugas':(string)($_POST['role']??'');
                if(!in_array($role,['superadmin','admin','petugas'],true))throw new DomainException('Peran tidak valid.');
                $assignment=$role==='superadmin'?null:($u['role']==='admin'?(int)$u['event_id']:(int)($_POST['assignment']??0));
                if(!$assignment&&$role!=='superadmin')throw new DomainException('Admin dan petugas harus ditugaskan ke satu acara.');
                $old=$id?sql('SELECT * FROM users WHERE id=? FOR UPDATE',[$id])->fetch():null;
                if($id&&!$old)throw new DomainException('Akun tidak ditemukan.');
                if($id===(int)$u['id'])throw new DomainException('Akun sendiri hanya dapat diubah melalui menu Akun saya.');
                if($u['role']==='admin'&&$old&&($old['role']!=='petugas'||(int)$old['event_id']!==(int)$u['event_id']))throw new DomainException('Anda hanya dapat mengelola petugas pada acara sendiri.');
                if($assignment)event_access($assignment,true,!$old||(int)$old['event_id']!==$assignment);
                $pass=(string)($_POST['password']??'');$active=isset($_POST['active'])?1:0;
                if(!$old||$pass!=='')$pass=password_value();
                if($old){sql('UPDATE users SET name=?,username=?,role=?,event_id=?,active=?,session_version=session_version+1 WHERE id=?',[$name,$username,$role,$assignment,$active,$id]);if($pass!=='')sql('UPDATE users SET password_hash=? WHERE id=?',[password_hash($pass,PASSWORD_DEFAULT),$id]);}
                else{sql('INSERT INTO users (name,username,password_hash,role,event_id,active) VALUES (?,?,?,?,?,?)',[$name,$username,password_hash($pass,PASSWORD_DEFAULT),$role,$assignment,$active]);$id=(int)db()->lastInsertId();}
                audit('user.save',$assignment,$id);$back=url('users');flash('Akun tersimpan. Perubahan akses memutus sesi lama akun tersebut.');break;
            default: throw new DomainException('Tindakan tidak dikenali.');
        }
        db()->commit();go($back);
    } catch(Throwable $ex) {
        if(db_available_transaction())db()->rollBack();
        $msg=$ex instanceof DomainException?$ex->getMessage():($ex instanceof PDOException&&$ex->getCode()==='23000'?'Username atau data unik sudah digunakan.':'Permintaan gagal. Coba lagi atau periksa log server.');
        if(!($ex instanceof DomainException))error_log((string)$ex);
        if($ajax)json_reply(['ok'=>false,'message'=>$msg],$ex instanceof DomainException?422:500);
        flash($msg,'error');if($a==='login')go(url('login'));if($a==='password')go(url('account'));
        if(str_starts_with($a,'user_'))go(url('users'));
        if(str_starts_with($a,'event_'))go(url('events'));
        if(str_starts_with($a,'guest_'))go(url('guests',['event'=>(int)($_POST['event_id']??0)]));go($back);
    }
}

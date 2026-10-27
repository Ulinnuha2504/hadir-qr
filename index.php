<?php
require __DIR__.'/app/bootstrap.php';
if(!$config)go('install.php');
require __DIR__.'/app/actions.php';
require __DIR__.'/app/views.php';
if($_SERVER['REQUEST_METHOD']==='POST')handle_post();
$page=(string)($_GET['page']??'dashboard');
if($page==='login'){if(user())go(url());render_login();exit;}
$u=login_required();
try {
    if($page==='template'){
        role_required(['superadmin','admin']);header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="template-tamu.csv"');echo "\xEF\xBB\xBFnama,kategori\nAhmad Fauzi,Umum\nSiti Aminah,VIP\n";exit;
    }
    if($page==='export'){
        $ev=selected_event();if(!$ev)throw new DomainException('Pilih acara terlebih dahulu.');event_access((int)$ev['id'],true);
        header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="kehadiran-acara-'.(int)$ev['id'].'.csv"');$f=fopen('php://output','w');fwrite($f,"\xEF\xBB\xBF");fputcsv($f,['Nama','Kategori','Status','Waktu hadir ('.date_default_timezone_get().')','Petugas'],',','"','');
        $q=sql('SELECT g.name,g.category,g.checked_at,u.name AS officer FROM guests g LEFT JOIN users u ON u.id=g.checked_by WHERE g.event_id=? AND g.deleted_at IS NULL ORDER BY g.name',[$ev['id']]);
        while($g=$q->fetch()){$r=[$g['name'],$g['category'],$g['checked_at']?'Hadir':'Belum hadir',$g['checked_at']?date_time($g['checked_at'],'Y-m-d H:i:s'):'',$g['officer']??''];$r=array_map(fn($v)=>preg_match('/^[=+@\-\t\r\n]/u',$v)?"'".$v:$v,$r);fputcsv($f,$r,',','"','');}fclose($f);exit;
    }
    if($page==='ticket'){render_ticket();exit;}
    if(!in_array($page,['dashboard','events','guests','scan','users','logs','account'],true)){$page='dashboard';http_response_code(404);}
    if(in_array($page,['events','users','logs'],true))role_required(['superadmin','admin']);
    if($page==='scan'){$ev=selected_event();if($ev)event_access((int)$ev['id'],false,true);}
    render_header($page);match($page){'events'=>render_events(),'guests'=>render_guests(),'scan'=>render_scan(),'users'=>render_users(),'logs'=>render_logs(),'account'=>render_account(),default=>render_dashboard()};render_footer();
} catch(DomainException $ex){http_response_code(403);if(!defined('LAYOUT_OPEN'))render_header('dashboard');echo '<section class="panel empty"><span class="big-icon">◇</span><h2>Tidak dapat membuka halaman</h2><p>'.e($ex->getMessage()).'</p><a class="btn primary" href="'.e(url()).'">Kembali ke ringkasan</a></section>';render_footer();}

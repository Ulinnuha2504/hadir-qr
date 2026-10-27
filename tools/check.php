<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/../app/bootstrap.php';
$checks=['PHP >=8.1'=>version_compare(PHP_VERSION,'8.1.0','>='),'PDO MySQL'=>extension_loaded('pdo_mysql'),'Konfigurasi'=>is_array($config),'Storage dapat ditulis'=>is_writable(ROOT.'/storage')];
try{db();$checks['Koneksi database']=true;$checks['Instalasi selesai']=(bool)sql('SELECT id FROM installation WHERE id=1')->fetchColumn();}catch(Throwable $ex){$checks['Koneksi/skema database']=false;}
foreach($checks as $name=>$ok)echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL;
exit(in_array(false,$checks,true)?1:0);

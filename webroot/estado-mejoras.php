<?php
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><title>SAF estado</title>
<style>body{font-family:system-ui;max-width:720px;margin:40px auto;padding:0 16px}code{background:#f4f4f4;padding:2px 6px;border-radius:4px}.ok{color:#0a7}.fail{color:#c00}</style>
</head><body>
<h1>SAF · Estado mejoras v2</h1>
<?php
$checks=[];
try {
  $pdo=new PDO('mysql:unix_socket=/tmp/saf-mysqld.sock;dbname=saf','admin','Zuft_2026.');
  $checks[]=['MySQL local (socket)',true,'Conectado'];
  $checks[]=['Usuarios',true,(string)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn()];
  $checks[]=['Alertas activas',true,(string)$pdo->query('SELECT COUNT(*) FROM alertas_cumplimiento WHERE resuelto=0')->fetchColumn()];
  foreach(['alertas_reglas','notificaciones','sistema_logs','auditoria_cambios','verificaciones_historial'] as $t){
    $n=(int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $checks[]=["Tabla $t",true,(string)$n];
  }
} catch(Throwable $e){
  $checks[]=['MySQL local',false,$e->getMessage()];
}
$tcp3306=@fsockopen('127.0.0.1',3306,$errno,$errstr,1);
$checks[]=['MySQL host :3306',$tcp3306!==false,$tcp3306?'UP':'DOWN — ejecutar bin/reparar_mysql_host.sh por SSH'];
if($tcp3306) fclose($tcp3306);
foreach($checks as [$n,$ok,$d]){
  echo '<p><strong>'.htmlspecialchars($n).':</strong> <span class="'.($ok?'ok':'fail').'">'.($ok?'OK':'FAIL').'</span> '.htmlspecialchars($d).'</p>';
}
?>
<p>Login demo: <code>superadmin@saf.local</code> / <code>Admin123!</code></p>
<p><a href="/saf/login">Ir al login</a></p>
</body></html>

<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';
header('Content-Type:text/plain;charset=utf-8');
try{$c=db();$s=oci_parse($c,"SELECT USER AS DATABASE_USER,SYSDATE AS DATABASE_TIME FROM DUAL");if(!$s||!oci_execute($s)){throw new RuntimeException('Oracle query failed.');}$r=oci_fetch_assoc($s);echo "QMRS ORACLE CONNECTION TEST\n============================\nSTATUS        : CONNECTED\nDATABASE USER : ".($r['DATABASE_USER']??'UNKNOWN')."\nDATABASE TIME : ".($r['DATABASE_TIME']??'UNKNOWN')."\n";}catch(Throwable $e){http_response_code(500);echo "QMRS ORACLE CONNECTION TEST\n============================\nSTATUS : FAILED\nReason : ".$e->getMessage();}

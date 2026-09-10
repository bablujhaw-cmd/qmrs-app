<?php
declare(strict_types=1);
require_once __DIR__.'/../config/app.php';

function start_secure_session(): void {
    if(session_status()===PHP_SESSION_ACTIVE)return;
    $secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    session_name('QMRS_SESSION'); session_start();
    if(isset($_SESSION['last_activity']) && time()-(int)$_SESSION['last_activity']>SESSION_TIMEOUT_SECONDS){
        $_SESSION=[]; session_destroy(); session_start();
    }
    $_SESSION['last_activity']=time();
}
function login_user(int $id,string $uuid,string $type): void {
    start_secure_session(); session_regenerate_id(true);
    $_SESSION['user_id']=$id; $_SESSION['public_uuid']=$uuid; $_SESSION['user_type']=$type; $_SESSION['last_activity']=time();
}
function current_user_id(): ?int { start_secure_session(); return isset($_SESSION['user_id'])?(int)$_SESSION['user_id']:null; }
function require_login(): void { if(current_user_id()===null){header('Location:/login/');exit;} }
function logout_user(): never {
    if(session_status()!==PHP_SESSION_ACTIVE)start_secure_session();
    $_SESSION=[]; session_destroy(); header('Location:/login/'); exit;
}

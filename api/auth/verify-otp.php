<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/security.php';require_once __DIR__.'/../../includes/otp.php';require_once __DIR__.'/../../includes/auth.php';require_post();
try{$id=filter_input(INPUT_POST,'user_id',FILTER_VALIDATE_INT);$type=strtoupper(request_string('otp_type',20));$otp=request_string('otp',10);if(!$id||!in_array($type,['EMAIL','MOBILE'],true)||!preg_match('/^\d{6}$/',$otp))json_response(false,'Invalid verification request.',[],422);if(!verify_latest_otp($id,$type,$otp))json_response(false,'The OTP is invalid or expired.',[],422);update_user_active($id,$type);json_response(true,'Account verification successful.');}catch(Throwable $e){error_log('OTP: '.$e->getMessage());json_response(false,'Unable to verify the account right now.',[],500);}

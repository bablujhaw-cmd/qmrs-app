<?php
declare(strict_types=1);
require_once __DIR__.'/../../config/app.php';require_once __DIR__.'/../../config/database.php';require_once __DIR__.'/../../includes/security.php';require_once __DIR__.'/../../includes/otp.php';require_post();
try{
$full=request_string('full_name',150);$email=strtolower(request_string('email',254));$country=request_string('country_code',10);$mobile=normalize_mobile(request_string('mobile',20));$pass=request_string('password',128);$confirm=request_string('confirm_password',128);$type=strtoupper(request_string('user_type',20));$terms=isset($_POST['terms']);
if(mb_strlen($full)<2)json_response(false,'Please enter your full name.',[],422);
if($email===''&&$mobile==='')json_response(false,'Email or mobile number is required.',[],422);
if($email!==''&&!valid_email($email))json_response(false,'Invalid email address.',[],422);
if($mobile!==''&&strlen($mobile)<6)json_response(false,'Invalid mobile number.',[],422);
if(!in_array($type,['CANDIDATE','EMPLOYER'],true))json_response(false,'Invalid account type.',[],422);
if(strlen($pass)<8)json_response(false,'Password must contain at least 8 characters.',[],422);
if($pass!==$confirm)json_response(false,'Passwords do not match.',[],422);
if(!$terms)json_response(false,'Terms and Privacy acceptance is required.',[],422);
$conn=db();$ev=$email!==''?$email:null;$mv=$mobile!==''?$mobile:null;$s=oci_parse($conn,"SELECT USER_ID FROM USERS WHERE (:email IS NOT NULL AND LOWER(EMAIL)=:email) OR (:mobile IS NOT NULL AND MOBILE_NUMBER=:mobile) FETCH FIRST 1 ROWS ONLY");oci_bind_by_name($s,':email',$ev);oci_bind_by_name($s,':mobile',$mv);oci_execute($s);if(oci_fetch_assoc($s)){oci_free_statement($s);json_response(false,'The supplied email or mobile number cannot be registered.',[],409);}oci_free_statement($s);
$uuid=random_public_uuid();$hash=password_hash($pass,PASSWORD_DEFAULT);$cv=$country!==''?$country:null;$s=oci_parse($conn,"INSERT INTO USERS(PUBLIC_UUID,FULL_NAME,EMAIL,COUNTRY_CODE,MOBILE_NUMBER,PASSWORD_HASH,USER_TYPE,ACCOUNT_STATUS,EMAIL_VERIFIED,MOBILE_VERIFIED,TERMS_VERSION,PRIVACY_VERSION) VALUES(:uuid,:full,:email,:country,:mobile,:hash,:type,'PENDING',0,0,:tv,:pv) RETURNING USER_ID INTO :id");$id=0;$tv=TERMS_VERSION;$pv=PRIVACY_VERSION;oci_bind_by_name($s,':uuid',$uuid);oci_bind_by_name($s,':full',$full);oci_bind_by_name($s,':email',$ev);oci_bind_by_name($s,':country',$cv);oci_bind_by_name($s,':mobile',$mv);oci_bind_by_name($s,':hash',$hash);oci_bind_by_name($s,':type',$type);oci_bind_by_name($s,':tv',$tv);oci_bind_by_name($s,':pv',$pv);oci_bind_by_name($s,':id',$id,-1,SQLT_INT);
if(!oci_execute($s,OCI_NO_AUTO_COMMIT)){oci_rollback($conn);throw new RuntimeException('Account creation failed.');}oci_commit($conn);oci_free_statement($s);
$otpType=$email!==''?'EMAIL':'MOBILE';$sent=$email!==''?$email:$country.$mobile;$otp=create_otp($id,$otpType,$sent);$data=['user_id'=>$id,'otp_type'=>$otpType];if(APP_ENV!=='production')$data['development_otp']=$otp;json_response(true,'Account created. Verification is required.',$data,201);
}catch(Throwable $e){error_log('Registration: '.$e->getMessage());json_response(false,'Unable to create the account right now.',[],500);}

<?php
declare(strict_types=1);
require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/security.php';

function create_otp(int $userId,string $type,string $sentTo): string {
    $type=strtoupper($type); if(!in_array($type,['EMAIL','MOBILE'],true))throw new InvalidArgumentException('Invalid OTP type.');
    $otp=generate_otp(); $hash=password_hash($otp,PASSWORD_DEFAULT); $conn=db();
    $s=oci_parse($conn,"UPDATE USER_OTP_VERIFICATION SET STATUS='EXPIRED' WHERE USER_ID=:id AND OTP_TYPE=:type AND STATUS='PENDING'");
    oci_bind_by_name($s,':id',$userId);oci_bind_by_name($s,':type',$type);oci_execute($s,OCI_NO_AUTO_COMMIT);oci_free_statement($s);
    $s=oci_parse($conn,"INSERT INTO USER_OTP_VERIFICATION(USER_ID,OTP_CODE_HASH,OTP_TYPE,SENT_TO,EXPIRES_AT,ATTEMPT_COUNT,STATUS) VALUES(:id,:hash,:type,:sent,SYSTIMESTAMP+NUMTODSINTERVAL(:ttl,'MINUTE'),0,'PENDING')");
    $ttl=OTP_TTL_MINUTES;oci_bind_by_name($s,':id',$userId);oci_bind_by_name($s,':hash',$hash);oci_bind_by_name($s,':type',$type);oci_bind_by_name($s,':sent',$sentTo);oci_bind_by_name($s,':ttl',$ttl);
    if(!oci_execute($s,OCI_NO_AUTO_COMMIT)){oci_rollback($conn);throw new RuntimeException('OTP creation failed.');}
    oci_commit($conn);oci_free_statement($s);return $otp;
}
function verify_latest_otp(int $userId,string $type,string $otp): bool {
    $conn=db();$type=strtoupper($type);
    $s=oci_parse($conn,"SELECT OTP_ID,OTP_CODE_HASH,ATTEMPT_COUNT FROM USER_OTP_VERIFICATION WHERE USER_ID=:id AND OTP_TYPE=:type AND STATUS='PENDING' AND EXPIRES_AT>SYSTIMESTAMP ORDER BY CREATED_AT DESC FETCH FIRST 1 ROWS ONLY");
    oci_bind_by_name($s,':id',$userId);oci_bind_by_name($s,':type',$type);oci_execute($s);$r=oci_fetch_assoc($s);oci_free_statement($s);
    if(!$r)return false;$oid=(int)$r['OTP_ID'];$attempt=(int)$r['ATTEMPT_COUNT'];
    if($attempt>=OTP_MAX_ATTEMPTS){mark_otp_status($oid,'BLOCKED');return false;}
    if(!password_verify($otp,$r['OTP_CODE_HASH'])){
        $s=oci_parse($conn,"UPDATE USER_OTP_VERIFICATION SET ATTEMPT_COUNT=ATTEMPT_COUNT+1,STATUS=CASE WHEN ATTEMPT_COUNT+1>=:max THEN 'BLOCKED' ELSE STATUS END WHERE OTP_ID=:oid");
        $max=OTP_MAX_ATTEMPTS;oci_bind_by_name($s,':max',$max);oci_bind_by_name($s,':oid',$oid);oci_execute($s,OCI_NO_AUTO_COMMIT);oci_commit($conn);oci_free_statement($s);return false;
    }
    mark_otp_status($oid,'VERIFIED');return true;
}
function mark_otp_status(int $id,string $status): void {
    if(!in_array($status,['VERIFIED','EXPIRED','BLOCKED'],true))throw new InvalidArgumentException('Invalid OTP status.');
    $conn=db();$extra=$status==='VERIFIED'?',VERIFIED_AT=SYSTIMESTAMP':'';
    $s=oci_parse($conn,"UPDATE USER_OTP_VERIFICATION SET STATUS=:status$extra WHERE OTP_ID=:id");
    oci_bind_by_name($s,':status',$status);oci_bind_by_name($s,':id',$id);oci_execute($s,OCI_NO_AUTO_COMMIT);oci_commit($conn);oci_free_statement($s);
}

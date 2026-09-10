<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/otp.php';

require_post();

try {
    $id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $type = strtoupper(request_string('otp_type', 20));
    $otp = request_string('otp', 10);
    $new = request_string('new_password', 128);
    $confirm = request_string('confirm_password', 128);

    if (!$id || !preg_match('/^\d{6}$/', $otp) || strlen($new) < 8 || $new !== $confirm) {
        json_response(false, 'Invalid password reset request.', [], 422);
    }

    if (!verify_latest_otp($id, $type, $otp)) {
        json_response(false, 'The verification code is invalid or expired.', [], 422);
    }

    $conn = db();
    $hash = password_hash($new, PASSWORD_DEFAULT);

    // Update password
    $s = oci_parse($conn, "UPDATE USERS SET PASSWORD_HASH = :hash, UPDATED_AT = SYSTIMESTAMP WHERE USER_ID = :id AND ACCOUNT_STATUS = 'ACTIVE'");
    oci_bind_by_name($s, ':hash', $hash);
    oci_bind_by_name($s, ':id', $id);

    if (!oci_execute($s, OCI_NO_AUTO_COMMIT)) {
        oci_rollback($conn);
        throw new RuntimeException('Password update failed.');
    }

    // Mark used OTPs as expired
    $s2 = oci_parse($conn, "UPDATE USER_OTP_VERIFICATION SET STATUS = 'EXPIRED' WHERE USER_ID = :id AND STATUS = 'VERIFIED'");
    oci_bind_by_name($s2, ':id', $id);
    oci_execute($s2, OCI_NO_AUTO_COMMIT);

    oci_commit($conn);
    oci_free_statement($s);
    oci_free_statement($s2);

    json_response(true, 'Your password has been changed successfully.');
} catch (Throwable $e) {
    error_log('Reset password: ' . $e->getMessage());
    json_response(false, 'Unable to reset the password right now.', [], 500);
}
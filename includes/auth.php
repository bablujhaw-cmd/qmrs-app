<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/session.php';

function find_user_by_identity(string $identity): ?array {
    $conn = db();
    $identity = trim($identity);
    $email = str_contains($identity, '@') ? strtolower($identity) : null;
    $mobile = $email === null ? normalize_mobile($identity) : null;

    $sql = "SELECT USER_ID, PUBLIC_UUID, FULL_NAME, EMAIL, COUNTRY_CODE, MOBILE_NUMBER, PASSWORD_HASH, USER_TYPE, ACCOUNT_STATUS, EMAIL_VERIFIED, MOBILE_VERIFIED 
            FROM USERS 
            WHERE (:email IS NOT NULL AND LOWER(EMAIL) = :email) 
               OR (:mobile IS NOT NULL AND MOBILE_NUMBER = :mobile) 
            FETCH FIRST 1 ROWS ONLY";

    $s = oci_parse($conn, $sql);
    oci_bind_by_name($s, ':email', $email);
    oci_bind_by_name($s, ':mobile', $mobile);

    if (!oci_execute($s)) {
        throw new RuntimeException('Database query failed.');
    }
    $r = oci_fetch_assoc($s);
    oci_free_statement($s);
    return $r ?: null;
}

function find_user_by_id(int $id): ?array {
    $conn = db();
    $sql = "SELECT USER_ID, PUBLIC_UUID, FULL_NAME, EMAIL, COUNTRY_CODE, MOBILE_NUMBER, USER_TYPE, ACCOUNT_STATUS, EMAIL_VERIFIED, MOBILE_VERIFIED 
            FROM USERS 
            WHERE USER_ID = :id";

    $s = oci_parse($conn, $sql);
    oci_bind_by_name($s, ':id', $id);

    if (!oci_execute($s)) {
        throw new RuntimeException('Database query failed.');
    }
    $r = oci_fetch_assoc($s);
    oci_free_statement($s);
    return $r ?: null;
}

function update_user_active(int $id, string $type): void {
    $col = strtoupper($type) === 'EMAIL' ? 'EMAIL_VERIFIED' : 'MOBILE_VERIFIED';
    if (!in_array($col, ['EMAIL_VERIFIED', 'MOBILE_VERIFIED'], true)) {
        throw new RuntimeException('Invalid verification type.');
    }

    $conn = db();
    $sql = "UPDATE USERS 
            SET $col = 1,
                ACCOUNT_STATUS = CASE 
                    WHEN (EMAIL IS NULL OR EMAIL_VERIFIED = 1 OR :t1 = 'EMAIL') 
                     AND (MOBILE_NUMBER IS NULL OR MOBILE_VERIFIED = 1 OR :t2 = 'MOBILE') 
                    THEN 'ACTIVE' 
                    ELSE ACCOUNT_STATUS 
                END,
                UPDATED_AT = SYSTIMESTAMP 
            WHERE USER_ID = :id";

    $s = oci_parse($conn, $sql);
    $t1 = strtoupper($type);
    $t2 = $t1;
    oci_bind_by_name($s, ':t1', $t1);
    oci_bind_by_name($s, ':t2', $t2);
    oci_bind_by_name($s, ':id', $id);

    if (!oci_execute($s, OCI_NO_AUTO_COMMIT)) {
        oci_rollback($conn);
        throw new RuntimeException('Activation failed.');
    }
    oci_commit($conn);
    oci_free_statement($s);
}
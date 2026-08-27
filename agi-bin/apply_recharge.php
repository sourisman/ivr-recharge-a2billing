#!/usr/bin/php -q
<?php
require_once(__DIR__ . '/phpagi.php');

$agi = new AGI();
$callerid = isset($argv[1]) ? trim($argv[1]) : '';
$code = isset($argv[2]) ? trim($argv[2]) : '';

$agi->verbose("=== apply_recharge: $callerid / $code ===", 1);

$conn = @pg_connect("host=172.17.0.1 port=5432 dbname=a2billing user=a2billing password=a2billing_pass");

if (!$conn) {
    $agi->set_variable('RECHARGE_OK', '0');
    $agi->set_variable('RECHARGE_ERR', 'DB_ERROR');
    exit(1);
}

$agi->set_variable('RECHARGE_OK', '0');
$agi->set_variable('RECHARGE_ERR', '');

if (empty($callerid) || empty($code)) {
    $agi->set_variable('RECHARGE_ERR', 'INVALID_PARAMS');
    pg_close($conn);
    exit(1);
}

pg_query($conn, "BEGIN");

$res = pg_query_params(
    $conn,
    "SELECT id, credit, used, activated, expirationdate FROM cc_voucher WHERE voucher = \$1 FOR UPDATE",
    [$code]
);

if (!$res || pg_num_rows($res) === 0) {
    pg_query($conn, "ROLLBACK");
    $agi->verbose("Voucher NOT FOUND", 1);
    $agi->set_variable('RECHARGE_ERR', 'INTROUVABLE');
    pg_close($conn);
    exit(0);
}

$voucher = pg_fetch_assoc($res);

if ((int)$voucher['used'] !== 0) {
    pg_query($conn, "ROLLBACK");
    $agi->set_variable('RECHARGE_ERR', 'DEJA_UTILISE');
    pg_close($conn);
    exit(0);
}

if ($voucher['activated'] === 'f' || $voucher['activated'] === false) {
    pg_query($conn, "ROLLBACK");
    $agi->set_variable('RECHARGE_ERR', 'DESACTIVE');
    pg_close($conn);
    exit(0);
}

if (!empty($voucher['expirationdate']) && strtotime($voucher['expirationdate']) < time()) {
    pg_query($conn, "ROLLBACK");
    $agi->set_variable('RECHARGE_ERR', 'EXPIRE');
    pg_close($conn);
    exit(0);
}

$amount = (float)$voucher['credit'];

$update = pg_query_params(
    $conn,
    "UPDATE cc_card SET credit = credit + \$1 WHERE username = \$2 RETURNING credit",
    [$amount, $callerid]
);

if (!$update || pg_num_rows($update) === 0) {
    pg_query($conn, "ROLLBACK");
    $agi->set_variable('RECHARGE_ERR', 'USER_NOT_FOUND');
    pg_close($conn);
    exit(0);
}

$row = pg_fetch_assoc($update);
$new_credit = (float)$row['credit'];

pg_query_params(
    $conn,
    "UPDATE cc_voucher SET used = 1, usedcardnumber = \$1, usedate = NOW() WHERE id = \$2",
    [$callerid, $voucher['id']]
);

pg_query($conn, "COMMIT");

$agi->verbose("Recharge OK: $new_credit", 1);
$agi->set_variable('RECHARGE_OK', '1');
$agi->set_variable('NOUVEAU_SOLDE', (int)round($new_credit));

pg_close($conn);
exit(0);
?>

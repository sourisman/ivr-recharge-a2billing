#!/usr/bin/php -q
<?php
require_once(__DIR__ . '/phpagi.php');

$agi = new AGI();
$callerid = isset($argv[1]) ? trim($argv[1]) : '';

$agi->verbose("=== check_user.php: $callerid ===", 1);

$conn = @pg_connect("host=172.17.0.1 port=5432 dbname=a2billing user=a2billing password=a2billing_pass");

if (!$conn) {
    $agi->verbose("DB ERREUR", 1);
    $agi->set_variable('USER_EXISTS', '0');
    exit(1);
}

$res = pg_query_params($conn, "SELECT credit FROM cc_card WHERE username = \$1 LIMIT 1", [$callerid]);

if ($res && pg_num_rows($res) > 0) {
    $row = pg_fetch_assoc($res);
    $solde = (float)$row['credit'];
    $agi->verbose("User trouvé: $solde", 1);
    $agi->set_variable('USER_EXISTS', '1');
    $agi->set_variable('SOLDE', (int)round($solde));
} else {
    $agi->verbose("User NOT FOUND", 1);
    $agi->set_variable('USER_EXISTS', '0');
    $agi->set_variable('SOLDE', '0');
}

pg_close($conn);
exit(0);
?>

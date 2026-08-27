#!/bin/bash
# Test les scripts PHP directement

echo "=== Test DB ==="
php -r "
\$c = pg_connect('host=172.17.0.1 port=5432 dbname=a2billing user=a2billing password=a2billing_pass');
echo \$c ? 'OK' : 'FAIL';
"

echo -e "\n=== Test check_user (user: 201) ==="
php agi-bin/check_user.php 201

echo -e "\n=== Test fichiers ==="
ls -lh agi-bin/*.php

echo -e "\n=== Done ==="

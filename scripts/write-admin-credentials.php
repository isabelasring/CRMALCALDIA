<?php

/**
 * Guarda credenciales admin en data/.alcaldia-admin-credentials.json
 * para que seed-admin-user.php siempre las encuentre (Dokploy / .env).
 */

require_once __DIR__ . '/includes/admin-credentials.php';

$userName = alcaldiaEnv('ESPOCRM_ADMIN_USERNAME', 'admin');
$password = alcaldiaEnv('ESPOCRM_ADMIN_PASSWORD', '');

if ($userName === '') {
    fwrite(STDERR, 'ERROR: ESPOCRM_ADMIN_USERNAME vacío.' . PHP_EOL);
    exit(1);
}

if ($password === '') {
    fwrite(STDERR, 'ERROR: ESPOCRM_ADMIN_PASSWORD vacía en el contenedor.' . PHP_EOL);
    fwrite(STDERR, 'Configúrala en Dokploy → Environment (servicio espocrm y espocrm-init).' . PHP_EOL);
    exit(1);
}

alcaldiaWriteAdminCredentialsFile($userName, $password);

echo 'Credenciales admin guardadas (usuario: ' . $userName . ').' . PHP_EOL;

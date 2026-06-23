<?php

/**
 * Genera case-create-users.js con defaults según rol (Inspección / Radicación).
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$findUserByRole = static function (string $roleName) use ($em) {
    $role = $em->getRDBRepository('Role')->where(['name' => $roleName])->findOne();

    if (!$role) {
        return null;
    }

    $roleUser = $em->getRDBRepository('RoleUser')
        ->join('user')
        ->where([
            'roleId' => $role->getId(),
            'user.isActive' => true,
        ])
        ->order('user.createdAt', 'ASC')
        ->findOne();

    if (!$roleUser) {
        return null;
    }

    return $em->getEntityById('User', $roleUser->get('userId'));
};

$map = [
    'cRecibidaPor' => 'Inspección',
    'cRemitidoA' => 'Radicación',
];

$defaults = [];

foreach ($map as $field => $roleName) {
    $user = $findUserByRole($roleName);

    if (!$user) {
        echo "Sin usuario activo con rol {$roleName} (se omite {$field}).\n";
        continue;
    }

    $defaults[$field . 'Id'] = $user->getId();
    $defaults[$field . 'Name'] = $user->getName();
    echo "{$field} → {$user->get('userName')} ({$roleName})\n";
}

$outPaths = [
    '/var/www/html/custom/Espo/Custom/files/client/custom/src/config/case-create-users.js',
    '/var/www/html/client/custom/src/config/case-create-users.js',
];

$js = "define('custom:config/case-create-users', [], function () {\n\n    return " . json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";\n});\n";

foreach ($outPaths as $outPath) {
    file_put_contents($outPath, $js);
    echo "Generado: {$outPath}\n";
}

$localPath = dirname(__DIR__) . '/espocrm-custom/files/client/custom/src/config/case-create-users.js';

if (is_dir(dirname($localPath))) {
    file_put_contents($localPath, $js);
}

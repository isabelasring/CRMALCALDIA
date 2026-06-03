<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Authentication\LoginFactory;
use Espo\Core\Authentication\LoginParams;
use Espo\ORM\EntityManager;

$app = new Application();
$em = $app->getContainer()->getByClass(EntityManager::class);

$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();
if (!$user) {
    echo "Usuario no existe\n";
    exit(1);
}

echo "=== Usuario ===\n";
echo 'id: ' . $user->getId() . "\n";
echo 'active: ' . ($user->get('isActive') ? 'yes' : 'no') . "\n";
echo 'roles: ' . implode(',', $user->getLinkMultipleIdList('roles') ?? []) . "\n";

$role = $em->getRDBRepository('Role')->where(['name' => 'Asignador'])->findOne();
if ($role) {
    $data = $role->get('data');
    if ($data instanceof stdClass) {
        $data = json_decode(json_encode($data), true);
    }
    echo "\n=== Rol Asignador ===\n";
    echo 'assignmentPermission: ' . $role->get('assignmentPermission') . "\n";
    echo 'Case: ' . json_encode($data['Case'] ?? null) . "\n";
    echo 'Notification: ' . json_encode($data['Notification'] ?? null) . "\n";
    $fd = $role->get('fieldData');
    if ($fd instanceof stdClass) {
        $fd = json_decode(json_encode($fd), true);
    }
    $bad = [];
    foreach (($fd['Case'] ?? []) as $f => $p) {
        if (!in_array($f, ['assignedUser', 'assignedUserId'], true) && ($p['edit'] ?? '') === 'yes') {
            $bad[] = $f;
        }
    }
    echo 'Campos edit yes (debe ser solo assignedUser): ' . json_encode($bad) . "\n";
}

echo "\n=== Casos BD ===\n";
foreach ($em->getRDBRepository('Case')->find() as $c) {
    echo $c->getId() . ' | ' . $c->get('status') . ' | ' . $c->get('name') . "\n";
}

echo "\n=== Notificaciones Julian ===\n";
echo 'count=' . $em->getRDBRepository('Notification')
    ->where(['userId' => $user->getId()])
    ->count() . "\n";

echo "\n=== Login API test ===\n";
try {
    $auth = $app->getContainer()->getByClass(LoginFactory::class)->create();
    $result = $auth->login(
        LoginParams::create()
            ->withUsername('julian.asignador')
            ->withPassword('test')
    );
    echo 'login default password: ' . ($result->isSuccess() ? 'OK' : $result->getMessage()) . "\n";
} catch (Throwable $e) {
    echo 'login err: ' . $e->getMessage() . "\n";
}

echo "\n=== Mandatory class ===\n";
echo class_exists(\Espo\Custom\Classes\Select\Case\AccessControlFilters\Mandatory::class) ? "OK\n" : "MISSING\n";

$metadata = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);
echo "\n=== selectDefs.Case (merged) ===\n";
echo json_encode($metadata->get(['selectDefs', 'Case']), JSON_PRETTY_PRINT) . "\n";

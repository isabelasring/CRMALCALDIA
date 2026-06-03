<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\PasswordHash;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$user = $em->getRDBRepository('User')->where(['userName' => 'julian.asignador'])->findOne();

try {
    $hash = $app->getContainer()->getByClass(PasswordHash::class)->hash('Julian2026!');
    $user->set('password', $hash);
    $em->saveEntity($user, ['silent' => true]);
    echo "Password: Julian2026!\n";
} catch (Throwable $e) {
    echo 'password err: ' . $e->getMessage() . PHP_EOL;
}

$prefs = $em->getEntityById('Preferences', $user->getId());
if ($prefs) {
    $prefs->set('tabList', ['Case', 'Contact', 'Email']);
    $prefs->set('defaultTab', 'Case');
    $em->saveEntity($prefs);
    echo "Prefs OK\n";
}

$fixed = 0;
foreach ($em->getRDBRepository('Notification')->where(['userId' => $user->getId()])->find() as $n) {
    if ($n->get('type') === 'Radicado') {
        $n->set('type', 'Info');
        $em->saveEntity($n);
        $fixed++;
    }
}
echo "Notif fixed: $fixed\n";

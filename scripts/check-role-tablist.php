<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

foreach (['Asignador', 'Inspección', 'Radicación'] as $name) {
    $role = $em->getRDBRepository('Role')->where(['name' => $name])->findOne();
    if (!$role) {
        echo "{$name}: no existe\n";
        continue;
    }
    echo "{$name} tabList raw: ";
    var_export($role->get('tabList'));
    echo PHP_EOL;
    $pdo = $em->getPDO();
    $stmt = $pdo->prepare('SELECT tab_list FROM role WHERE id = ? AND deleted = 0');
    $stmt->execute([$role->getId()]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "{$name} DB tab_list: " . ($row['tab_list'] ?? 'null') . PHP_EOL . PHP_EOL;
}

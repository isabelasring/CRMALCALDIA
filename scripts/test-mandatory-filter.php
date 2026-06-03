<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Entities\User;

$app = new Application();
$container = $app->getContainer();
$em = $container->getByClass(Espo\ORM\EntityManager::class);

$julian = $em->getRDBRepositoryByClass(User::class)
    ->where(['userName' => 'julian.asignador'])
    ->findOne();

$container->set('user', $julian);

try {
    $factory = $container->getByClass(SelectBuilderFactory::class);
    $query = $factory->create()
        ->from('Case')
        ->withStrictAccessControl()
        ->buildQueryBuilder()
        ->build();

    $collection = $em->getRDBRepository('Case')->clone($query)->find();
    $n = 0;
    foreach ($collection as $c) {
        echo $c->get('name') . ' | ' . $c->get('status') . PHP_EOL;
        $n++;
    }
    echo "Total: $n\n";
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}

<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\ApplicationRunners\Cli;
use Espo\Core\Container;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

$app = new Application();
$em = $app->getContainer()->getByClass(EntityManager::class);

$julian = $em->getRDBRepositoryByClass(User::class)
    ->where(['userName' => 'julian.asignador'])
    ->findOne();

// Impersonate Julian via container rebuild pattern
$container = $app->getContainer();

$injectableFactory = $container->get('injectableFactory');

try {
    /** @var SelectBuilderFactory $selectBuilderFactory */
    $selectBuilderFactory = $container->getByClass(SelectBuilderFactory::class);

    $query = $selectBuilderFactory
        ->create()
        ->from('Case')
        ->withStrictAccessControl()
        ->buildQueryBuilder()
        ->build();

    echo "Query (system user):\n";
    print_r($query->getWhere()->getRaw());
} catch (Throwable $e) {
    echo 'ERR system: ' . $e->getMessage() . PHP_EOL;
}

// Test Mandatory filter directly
try {
    $filter = $injectableFactory->create(
        \Espo\Custom\Classes\Select\Case\AccessControlFilters\Mandatory::class,
        null,
        null,
        ['user' => $julian]
    );
    echo "Mandatory filter created OK\n";
} catch (Throwable $e) {
    echo 'Mandatory ERR: ' . $e->getMessage() . PHP_EOL;
}

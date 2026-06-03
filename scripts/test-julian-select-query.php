<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Entities\User;

$app = new Application();
$app->setupSystemUser();
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);
$julian = $em->getRDBRepositoryByClass(User::class)->where(['userName' => 'julian.asignador'])->findOne();

// Reload roles
$roleIds = $em->getRDBRepository('RoleUser')
    ->where(['userId' => $julian->getId()])
    ->find();
echo 'RoleUser links: ' . iterator_count($roleIds) . PHP_EOL;

$injectableFactory = $app->getContainer()->get('injectableFactory');

$user = $em->getEntityById('User', $julian->getId());
echo 'roles on entity: ' . implode(',', $user->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;

try {
    $filter = $injectableFactory->create(
        \Espo\Custom\Classes\Select\Case\AccessControlFilters\Mandatory::class,
        null,
        \Espo\ORM\EntityManager::class
    );
} catch (Throwable $e) {
    echo 'create filter err: ' . $e->getMessage() . PHP_EOL;
}

// Raw SQL test
$pdo = $em->getPDO();
$stmt = $pdo->query("SELECT id, name, status FROM \"case\" WHERE deleted = 0 AND status = 'Radicado'");
echo "SQL Radicado rows:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . PHP_EOL;
}

// Test hasAsignador via reflection
$filter = $injectableFactory->create(
    \Espo\Custom\Classes\Select\Case\AccessControlFilters\Mandatory::class
);
// Need user in constructor - use binding

echo "Done\n";

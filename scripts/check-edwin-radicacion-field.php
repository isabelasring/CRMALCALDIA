<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\Metadata;

$app = new Application();
$app->setupSystemUser();

$metadata = $app->getContainer()->getByClass(Metadata::class);
$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

echo 'Campo en metadata: ';
echo $metadata->get('entityDefs.Case.fields.cNumeroRadicacion') ? 'SI' : 'NO';
echo PHP_EOL;

$user = $em->getRDBRepository('User')->where(['userName' => 'edwin.radicacion'])->findOne();
echo 'Roles Edwin: ' . implode(', ', $user->getLinkMultipleIdList('roles') ?? []) . PHP_EOL;

$role = $em->getRDBRepository('Role')->where(['name' => 'Radicación'])->findOne();
if ($role) {
    $fieldData = $role->get('fieldData');
    if ($fieldData instanceof stdClass) {
        $fieldData = json_decode(json_encode($fieldData), true);
    }
    echo 'Field level Radicación Case/cNumeroRadicacion: '
        . json_encode($fieldData['Case']['cNumeroRadicacion'] ?? 'no definido')
        . PHP_EOL;
}

$case = $em->getRDBRepository('Case')->findOne();
if ($case) {
    echo 'Caso ejemplo status: ' . $case->get('status') . PHP_EOL;
}

$layoutList = [
    'layoutDefs.Case.detail',
    'layoutDefs.Case.detailSmall',
    'layoutDefs.Case.edit',
    'layoutDefs.Case.filters',
];

foreach ($layoutList as $key) {
    $layout = $metadata->get($key);
    $has = false;
    if (is_array($layout)) {
        $json = json_encode($layout);
        $has = str_contains($json, 'cNumeroRadicacion');
    }
    echo $key . ' tiene cNumeroRadicacion: ' . ($has ? 'SI' : 'NO') . PHP_EOL;
}

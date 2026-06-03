<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(EntityManager::class);

$year = date('Y');
$prefix = $year . '-';
$max = 0;

foreach ($em->getRDBRepository('Case')->select(['cExpediente'])->find() as $case) {
    $exp = (string) $case->get('cExpediente');

    if ($exp === '') {
        continue;
    }

    if (str_starts_with($exp, $prefix)) {
        $suffix = substr($exp, strlen($prefix));
        if (ctype_digit($suffix)) {
            $max = max($max, (int) $suffix);
        }
    }
}

$updated = 0;

foreach ($em->getRDBRepository('Case')->where(['status' => 'Radicado'])->find() as $case) {
    if (trim((string) ($case->get('cExpediente') ?? '')) !== '') {
        continue;
    }

    $max++;
    $case->set('cExpediente', $prefix . str_pad((string) $max, 5, '0', STR_PAD_LEFT));
    $em->saveEntity($case);
    echo 'Expediente ' . $case->get('cExpediente') . ' → ' . $case->get('name') . PHP_EOL;
    $updated++;
}

echo "Casos actualizados: {$updated}\n";

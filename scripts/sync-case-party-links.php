<?php

/**
 * Vincula casos existentes con Contact/Account por documento (peticionario e infractor).
 *
 * docker exec espocrm php /tmp/sync-case-party-links.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

$updated = 0;
$total = 0;

foreach ($em->getRDBRepository('Case')->find() as $case) {
    $total++;

    $before = [
        'contactId' => $case->get('contactId'),
        'accountId' => $case->get('accountId'),
        'cPerjudicanteContactId' => $case->get('cPerjudicanteContactId'),
        'cPerjudicanteCuentaId' => $case->get('cPerjudicanteCuentaId'),
    ];

    $em->saveEntity($case, [
        'forcePartyLinkSync' => true,
        'silent' => true,
        'skipNotifications' => true,
    ]);

    $after = [
        'contactId' => $case->get('contactId'),
        'accountId' => $case->get('accountId'),
        'cPerjudicanteContactId' => $case->get('cPerjudicanteContactId'),
        'cPerjudicanteCuentaId' => $case->get('cPerjudicanteCuentaId'),
    ];

    if ($before !== $after) {
        $updated++;
        $numero = trim((string) $case->get('cNumeroRadicado')) ?: $case->getId();
        echo "Vinculado: {$numero}\n";
    }
}

echo "Casos revisados: {$total}. Vínculos actualizados: {$updated}.\n";

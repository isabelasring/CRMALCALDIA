<?php

/**
 * Idioma español por defecto (menú Personas naturales / jurídicas, Casos, etc.).
 */
require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\Config;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var Config $config */
$config = $app->getContainer()->getByClass(Config::class);

$config->set('language', 'es_ES');
$config->set('defaultLanguage', 'es_ES');
$config->save();

/** @var EntityManager $em */
$em = $app->getContainer()->getByClass(EntityManager::class);

foreach ($em->getRDBRepository('User')->where(['isActive' => true])->find() as $user) {
    $prefs = $em->getEntityById('Preferences', $user->getId());

    if (!$prefs) {
        continue;
    }

    $prefs->set('language', 'es_ES');
    $em->saveEntity($prefs);
    echo $user->get('userName') . " → idioma es_ES\n";
}

echo "Idioma global: es_ES\n";

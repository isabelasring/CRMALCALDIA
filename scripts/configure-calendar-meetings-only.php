<?php

/**
 * Calendario: reuniones, tareas y casos (casos vía endpoint custom).
 *
 * docker cp scripts/configure-calendar-meetings-only.php espocrm:/tmp/configure-calendar-meetings-only.php
 * docker exec espocrm php /tmp/configure-calendar-meetings-only.php
 */

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\Config;

$app = new Application();
$app->setupSystemUser();

/** @var Config $config */
$config = $app->getContainer()->getByClass(Config::class);

$config->set('calendarEntityList', ['Meeting', 'Task', 'Case']);
$config->save();

echo "calendarEntityList = [Meeting, Task, Case]\n";
echo "Listo. Recarga con Cmd+Shift+R.\n";

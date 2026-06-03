<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\InjectableFactory;
use Espo\Tools\Layout\LayoutProvider;

$app = new Application();
$app->setupSystemUser();

$factory = $app->getContainer()->getByClass(InjectableFactory::class);
$provider = $factory->create(LayoutProvider::class);

foreach (['detail', 'edit', 'detailSmall'] as $name) {
    $json = $provider->get('Case', $name);
    $has = $json && str_contains($json, 'cNumeroRadicacion');
    echo "Layout Case/$name: " . ($has ? 'TIENE campo' : 'NO tiene campo') . PHP_EOL;
}

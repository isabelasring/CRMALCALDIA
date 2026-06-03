<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\InjectableFactory;

$app = new Application();
$app->setupSystemUser();

$injectableFactory = $app->getContainer()->getByClass(InjectableFactory::class);
$configWriter = $injectableFactory->create(ConfigWriter::class);

$path = '/var/www/html/custom/Espo/Custom/Resources/metadata/entityDefs/Case.json';

$data = json_decode((string) file_get_contents($path), true) ?? [];

if (!isset($data['fields'])) {
    $data['fields'] = [];
}

$data['fields']['cNumeroRadicacion'] = [
    'type' => 'varchar',
    'maxLength' => 100,
    'isCustom' => true,
    'tooltip' => true,
];

$data['fields']['cExpediente'] = [
    'type' => 'varchar',
    'maxLength' => 50,
    'isCustom' => true,
    'readOnly' => true,
    'tooltip' => true,
];

file_put_contents(
    $path,
    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);

echo "Campo cNumeroRadicacion agregado en Case.json\n";

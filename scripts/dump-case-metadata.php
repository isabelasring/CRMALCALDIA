<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;

$app = new Application();
$app->setupSystemUser();
$m = $app->getContainer()->getByClass(Espo\Core\Utils\Metadata::class);

echo "=== selectDefs.Case ===\n";
echo json_encode($m->get('selectDefs', 'Case'), JSON_PRETTY_PRINT) . "\n\n";

echo "=== Case field names (custom) ===\n";
$fields = $m->get(['entityDefs', 'Case', 'fields']) ?? [];
foreach ($fields as $name => $def) {
    if (!empty($def['isCustom'])) {
        echo $name . "\n";
    }
}

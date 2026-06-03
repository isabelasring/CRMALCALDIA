<?php
require_once '/var/www/html/bootstrap.php';
$m = (new Espo\Core\Application())->getContainer()->get('metadata');
$fields = $m->get(['entityDefs', 'Case', 'fields']) ?? [];
foreach (array_keys($fields) as $name) {
    if (str_starts_with($name, 'c') || in_array($name, ['name', 'type', 'priority', 'description'], true)) {
        $f = $fields[$name];
        echo "$name | type=" . ($f['type'] ?? '?') . ' | options=' . json_encode($f['options'] ?? null, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

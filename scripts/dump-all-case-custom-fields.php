<?php
require_once '/var/www/html/bootstrap.php';
$m = (new Espo\Core\Application())->getContainer()->get('metadata');
$fields = $m->get(['entityDefs', 'Case', 'fields']) ?? [];
ksort($fields);
foreach ($fields as $name => $f) {
    if (!str_starts_with($name, 'c') && !in_array($name, ['name', 'type', 'priority'], true)) {
        continue;
    }
    echo "$name\n";
    echo '  type: ' . ($f['type'] ?? '?') . "\n";
    if (!empty($f['options'])) {
        echo '  options: ' . json_encode($f['options'], JSON_UNESCAPED_UNICODE) . "\n";
    }
}

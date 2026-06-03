<?php
require_once '/var/www/html/bootstrap.php';
$m = (new Espo\Core\Application())->getContainer()->get('metadata');
foreach (['cTipoDeDocumento','cCanalDeReporte','type','priority'] as $f) {
    $opts = $m->get(['entityDefs', 'Case', 'fields', $f, 'options']);
    echo "$f: " . json_encode($opts, JSON_UNESCAPED_UNICODE) . "\n";
}

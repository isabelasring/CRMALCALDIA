<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\ApplicationRunners\Api;
use Espo\Core\Api\RequestWrapper;
use Espo\Entities\User;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

$app = new Application();
$app->setupSystemUser();

$em = $app->getContainer()->getByClass(Espo\ORM\EntityManager::class);

$julian = $em->getRDBRepositoryByClass(User::class)
    ->where(['userName' => 'julian.asignador'])
    ->findOne();

if (!$julian) {
    echo "Julian no existe\n";
    exit(1);
}

$app->getContainer()->set('user', $julian);

echo "=== Notificaciones Julian ===\n";
foreach ($em->getRDBRepository('Notification')
    ->where(['userId' => $julian->getId()])
    ->order('number', 'DESC')
    ->limit(0, 5)
    ->find() as $n) {
    echo '- type=' . $n->get('type') . ' | ' . substr(strip_tags((string) $n->get('message')), 0, 80) . "\n";
    $data = $n->get('data');
    echo '  data keys: ' . (is_object($data) ? implode(',', array_keys((array) $data)) : json_encode($data)) . "\n";
}

echo "\n=== Casos Radicado (ORM directo) ===\n";
echo 'count=' . $em->getRDBRepository('Case')->where(['status' => 'Radicado'])->count() . "\n";

try {
    $runner = new Api();
    $request = (new ServerRequestFactory())->createServerRequest(
        'GET',
        'http://localhost/api/v1/Case?maxSize=20&select=id,name,status'
    );
    $response = (new ResponseFactory())->createResponse();
    $app->run($runner, new RequestWrapper($request, $response));
    echo "\n=== API Case response status: " . $response->getStatusCode() . " ===\n";
    echo substr((string) $response->getBody(), 0, 500) . "\n";
} catch (Throwable $e) {
    echo "API error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

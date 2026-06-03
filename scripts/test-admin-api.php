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

foreach (['admin', 'julian.asignador'] as $uname) {
    $user = $em->getRDBRepositoryByClass(User::class)->where(['userName' => $uname])->findOne();
    $app->getContainer()->set('user', $user);

    $request = (new ServerRequestFactory())->createServerRequest(
        'GET',
        'http://localhost/api/v1/Case?maxSize=20&select=id,name,status'
    );
    $response = (new ResponseFactory())->createResponse();
    $app->run(new Api(), new RequestWrapper($request, $response));

    echo "=== $uname type={$user->get('type')} HTTP {$response->getStatusCode()} ===\n";
    echo substr((string) $response->getBody(), 0, 600) . "\n\n";
}

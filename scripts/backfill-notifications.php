<?php

require_once '/var/www/html/bootstrap.php';

use Espo\Core\Application;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Entities\Notification;
use Espo\ORM\EntityManager;

$app = new Application();
$app->setupSystemUser();

/** @var EntityManager $entityManager */
$entityManager = $app->getContainer()->getByClass(EntityManager::class);

$status = 'Pendiente de radicacion';

$cases = $entityManager
    ->getRDBRepository('Case')
    ->where([
        'status' => $status,
        'assignedUserId!=' => null,
    ])
    ->find();

$created = 0;

foreach ($cases as $case) {
    $assignedUserId = $case->get('assignedUserId');

    $existing = $entityManager
        ->getRDBRepositoryByClass(Notification::class)
        ->where([
            'type' => Notification::TYPE_ASSIGN,
            'userId' => $assignedUserId,
            'relatedId' => $case->getId(),
            'relatedType' => $case->getEntityType(),
        ])
        ->findOne();

    if ($existing) {
        continue;
    }

    $notification = $entityManager
        ->getRDBRepositoryByClass(Notification::class)
        ->getNew();

    $notification
        ->setType(Notification::TYPE_ASSIGN)
        ->setUserId($assignedUserId)
        ->setData([
            'entityType' => $case->getEntityType(),
            'entityId' => $case->getId(),
            'entityName' => $case->get(Field::NAME),
            'isNew' => true,
            'userId' => $case->get('createdById'),
            'userName' => $case->get('createdByName'),
        ])
        ->setRelated(LinkParent::createFromEntity($case));

    $entityManager->saveEntity($notification);
    $created++;
}

echo "Notificaciones creadas: {$created}\n";

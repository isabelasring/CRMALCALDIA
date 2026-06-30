<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Limpia enlaces Recibida por / Remitido a si el usuario no existe o está inactivo.
 */
class SanitizeCaseGestionLinksOnSave implements BeforeSave
{
    public static int $order = 1;

    /** @var string[] */
    private const LINK_PAIRS = [
        ['cRecibidaPorId', 'cRecibidaPorName'],
        ['cRemitidoAId', 'cRemitidoAName'],
    ];

    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        foreach (self::LINK_PAIRS as [$idField, $nameField]) {
            $this->sanitizeUserLink($entity, $idField, $nameField);
        }
    }

    private function sanitizeUserLink(Entity $entity, string $idField, string $nameField): void
    {
        $userId = trim((string) $entity->get($idField));

        if ($userId === '') {
            $entity->set($idField, null);
            $entity->set($nameField, null);

            return;
        }

        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user || !$user->get('isActive')) {
            $entity->set($idField, null);
            $entity->set($nameField, null);
        }
    }
}

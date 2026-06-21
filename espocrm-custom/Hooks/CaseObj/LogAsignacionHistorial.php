<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Custom\Entities\AsignacionHistorial;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Registra cada cambio de responsable en un caso ya radicado.
 */
class LogAsignacionHistorial implements AfterSave
{
    public static int $order = 20;

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private DateTimeUtil $dateTime
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if ($options->get('skipLogAsignacionHistorial')) {
            return;
        }

        if (!$entity->isAttributeChanged('assignedUserId')) {
            return;
        }

        if (!$this->isPostRadicado($entity)) {
            return;
        }

        $prevUserId = $entity->getFetched('assignedUserId');
        $newUserId = $entity->get('assignedUserId');

        if ($prevUserId === $newUserId) {
            return;
        }

        $prevName = $this->resolveUserLabel($prevUserId, $entity->getFetched('assignedUserName'));
        $newName = $this->resolveUserLabel($newUserId, $entity->get('assignedUserName'));

        $numero = trim((string) $entity->get('cNumeroRadicado'));
        $expediente = trim((string) $entity->get('cExpediente'));
        $caseLabel = $numero !== '' ? $numero : ($expediente !== '' ? $expediente : (string) $entity->get('name'));

        $motivo = trim((string) $entity->get('cMotivoReasignacion'));

        $historial = $this->entityManager
            ->getRDBRepositoryByClass(AsignacionHistorial::class)
            ->getNew();

        $historial
            ->set('caseId', $entity->getId())
            ->set('caseName', $caseLabel)
            ->set('numeroRadicado', $numero !== '' ? $numero : null)
            ->set('fecha', $this->dateTime->getSystemNowString())
            ->set('asignadoPorId', $this->user->getId())
            ->set('asignadoPorName', $this->user->getName())
            ->set('responsableAnteriorId', $prevUserId)
            ->set('responsableAnteriorName', $prevUserId ? $prevName : null)
            ->set('responsableNuevoId', $newUserId)
            ->set('responsableNuevoName', $newUserId ? $newName : null)
            ->set('motivo', $motivo !== '' ? $motivo : null)
            ->set('name', $caseLabel . ': ' . $prevName . ' → ' . $newName);

        $this->entityManager->saveEntity($historial);
    }

    private function isPostRadicado(Entity $entity): bool
    {
        $numero = trim((string) $entity->get('cNumeroRadicado'));
        $expediente = trim((string) $entity->get('cExpediente'));

        return $numero !== '' && $expediente !== '';
    }

    private function resolveUserLabel(?string $userId, mixed $name): string
    {
        $label = trim((string) $name);

        if ($label !== '') {
            return $label;
        }

        if (!$userId) {
            return 'Sin asignar';
        }

        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            return 'Sin asignar';
        }

        $userName = trim((string) $user->get('userName'));
        $fullName = trim((string) $user->getName());

        return $userName !== '' ? $userName : ($fullName !== '' ? $fullName : 'Sin asignar');
    }
}

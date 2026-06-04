<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Juan (Inspección): revisa acta en Visita realizada; visto bueno → Visita aprobada;
 * en Visita aprobada puede pasar a Finalizado solo si el acta fue diligenciada por patrulleros.
 */
class InspeccionActaReviewAccess implements BeforeSave
{
    public static int $order = 12;

    private const ROLE_INSPECCION = 'Inspección';
    private const STATUS_VISITA_REALIZADA = 'Visita realizada';
    private const STATUS_VISITA_APROBADA = 'Visita aprobada';
    private const STATUS_FINALIZADO = 'Finalizado';
    private const ACTA_DILIGENCIADA = 'Diligenciada';
    private const ACTA_APROBADA = 'Aprobada';

    /** Campos que el patrullero diligencia en el acta de visita. */
    private const ACTA_CONTENT_FIELDS = [
        'cActaHallazgos',
        'cActaMedidasTomadas',
        'cActaNombreVisitado',
        'cActaObservaciones',
    ];

    private const REVIEW_EDIT_FIELDS = [
        'cActaVistoBueno',
        'cActaObservacionesRevision',
    ];

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || !$this->hasInspeccionRole()) {
            return;
        }

        if ($entity->isNew()) {
            return;
        }

        $statusForEdit = $entity->hasFetched('status')
            ? $entity->getFetched('status')
            : $entity->get('status');

        if (in_array($statusForEdit, [self::STATUS_FINALIZADO, 'Proceso cerrado'], true)) {
            return;
        }

        if ($statusForEdit === self::STATUS_VISITA_REALIZADA) {
            $this->handleVisitaRealizada($entity);

            return;
        }

        if ($statusForEdit === self::STATUS_VISITA_APROBADA) {
            $this->handleVisitaAprobada($entity);

            return;
        }

        $this->revertStatusChange($entity, $statusForEdit);
    }

    private function handleVisitaRealizada(Entity $entity): void
    {
        if (!$this->hasPatrulleroActaRedactada($entity)) {
            $this->limitToAllowedFields($entity, []);
            $this->revertStatusChange($entity, self::STATUS_VISITA_REALIZADA);

            return;
        }

        $this->limitToAllowedFields($entity, array_merge(self::REVIEW_EDIT_FIELDS, [
            'cActaEstado',
            'cActaFechaAprobacion',
            'cActaRegistroOficial',
        ]));
        $this->applyVistoBueno($entity);
    }

    private function handleVisitaAprobada(Entity $entity): void
    {
        if (!$this->canFinalizarCaso($entity)) {
            $this->limitToAllowedFields($entity, []);
            $this->revertStatusChange($entity, self::STATUS_VISITA_APROBADA);

            return;
        }

        $this->limitToAllowedFields($entity, ['status']);
        $this->validateFinalizadoTransition($entity);
    }

    private function validateFinalizadoTransition(Entity $entity): void
    {
        if (!$entity->isAttributeChanged('status')) {
            return;
        }

        if (!$this->canFinalizarCaso($entity)) {
            throw Forbidden::create(
                'Solo puede finalizar casos con acta de visita diligenciada por patrulleros y aprobada.'
            );
        }

        if ($entity->get('status') !== self::STATUS_FINALIZADO) {
            throw Forbidden::create(
                'Solo puede cambiar el estado a Finalizado cuando el acta ya fue aprobada.'
            );
        }
    }

    private function hasPatrulleroActaRedactada(Entity $entity): bool
    {
        $estado = $entity->get('cActaEstado');

        if (!in_array($estado, [self::ACTA_DILIGENCIADA, self::ACTA_APROBADA], true)) {
            return false;
        }

        if (!$entity->get('cActaFechaVisita')) {
            return false;
        }

        foreach (self::ACTA_CONTENT_FIELDS as $field) {
            $value = $entity->get($field);

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function canFinalizarCaso(Entity $entity): bool
    {
        $status = $entity->hasFetched('status')
            ? $entity->getFetched('status')
            : $entity->get('status');

        if ($status !== self::STATUS_VISITA_APROBADA) {
            return false;
        }

        if ($entity->get('cActaEstado') !== self::ACTA_APROBADA) {
            return false;
        }

        if (!$entity->get('cActaRegistroOficial')) {
            return false;
        }

        return $this->hasPatrulleroActaRedactada($entity);
    }

    private function revertStatusChange(Entity $entity, string $expectedStatus): void
    {
        if (!$entity->isAttributeChanged('status')) {
            return;
        }

        if ($entity->get('status') === $expectedStatus) {
            return;
        }

        $entity->set('status', $expectedStatus);
    }

    private function limitToAllowedFields(Entity $entity, array $allowed): void
    {
        foreach ($entity->getAttributeList() as $attribute) {
            if (!$entity->isAttributeChanged($attribute)) {
                continue;
            }

            if (in_array($attribute, $allowed, true)) {
                continue;
            }

            if ($entity->hasFetched($attribute)) {
                $entity->set($attribute, $entity->getFetched($attribute));
            }
        }
    }

    private function applyVistoBueno(Entity $entity): void
    {
        if (!$entity->isAttributeChanged('cActaVistoBueno') || !$entity->get('cActaVistoBueno')) {
            return;
        }

        if (!$this->hasPatrulleroActaRedactada($entity)) {
            throw Forbidden::create(
                'No hay acta de visita diligenciada por patrulleros para revisar.'
            );
        }

        if ($entity->get('cActaEstado') !== self::ACTA_DILIGENCIADA) {
            throw Forbidden::create('El acta debe estar diligenciada para dar el visto bueno.');
        }

        $entity->set('cActaEstado', self::ACTA_APROBADA);
        $entity->set('status', self::STATUS_VISITA_APROBADA);
        $entity->set('cActaRegistroOficial', true);
        $entity->set('cActaFechaAprobacion', date('Y-m-d'));
    }

    private function hasInspeccionRole(): bool
    {
        $role = $this->entityManager
            ->getRDBRepositoryByClass(Role::class)
            ->where(['name' => self::ROLE_INSPECCION])
            ->findOne();

        if (!$role) {
            return false;
        }

        $roles = $this->user->getLinkMultipleIdList('roles') ?? [];

        return in_array($role->getId(), $roles, true);
    }
}

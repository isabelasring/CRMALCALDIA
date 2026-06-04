<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Juan (Inspección): diligencia acto de cierre en casos Finalizado (con acta de visita aprobada).
 */
class InspeccionActoCierreAccess implements BeforeSave
{
    public static int $order = 13;

    private const ROLE_INSPECCION = 'Inspección';
    private const STATUS_FINALIZADO = 'Finalizado';
    private const STATUS_PROCESO_CERRADO = 'Proceso cerrado';
    private const ACTA_APROBADA = 'Aprobada';
    private const CIERRE_DILIGENCIADO = 'Diligenciado';

    private const ACTA_CONTENT_FIELDS = [
        'cActaHallazgos',
        'cActaMedidasTomadas',
        'cActaNombreVisitado',
        'cActaObservaciones',
    ];

    private const CIERRE_EDIT_FIELDS = [
        'cCierreFecha',
        'cCierreResumen',
        'cCierreConclusiones',
        'cCierreMedidasAdoptadas',
        'cCierreObservaciones',
    ];

    private const CIERRE_SYSTEM_FIELDS = [
        'cCierreEstado',
        'cCierreProcesoCompleto',
        'cCierreFechaRegistro',
        'status',
    ];

    public function __construct(
        private User $user,
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($this->user->isAdmin() || !$this->hasInspeccionRole() || $entity->isNew()) {
            return;
        }

        $statusForEdit = $entity->hasFetched('status')
            ? $entity->getFetched('status')
            : $entity->get('status');

        if ($statusForEdit === self::STATUS_PROCESO_CERRADO) {
            $this->limitToAllowedFields($entity, []);
            $this->revertStatusChange($entity, self::STATUS_PROCESO_CERRADO);

            return;
        }

        if ($statusForEdit !== self::STATUS_FINALIZADO) {
            return;
        }

        if (!$this->canDiligenciarActoCierre($entity)) {
            $this->limitToAllowedFields($entity, []);
            $this->revertStatusChange($entity, self::STATUS_FINALIZADO);

            return;
        }

        if ($entity->get('cCierreProcesoCompleto')) {
            $this->limitToAllowedFields($entity, []);

            return;
        }

        $this->limitToAllowedFields($entity, array_merge(self::CIERRE_EDIT_FIELDS, self::CIERRE_SYSTEM_FIELDS));
        $this->applyActoCierre($entity);
    }

    private function applyActoCierre(Entity $entity): void
    {
        if (!$this->isActoCierreComplete($entity)) {
            if (!$entity->has('cCierreEstado') || $entity->get('cCierreEstado') === null) {
                $entity->set('cCierreEstado', 'Pendiente');
            }

            return;
        }

        $entity->set('cCierreEstado', self::CIERRE_DILIGENCIADO);
        $entity->set('cCierreProcesoCompleto', true);
        $entity->set('cCierreFechaRegistro', date('Y-m-d'));
        $entity->set('status', self::STATUS_PROCESO_CERRADO);
    }

    private function isActoCierreComplete(Entity $entity): bool
    {
        $fecha = $entity->get('cCierreFecha');
        $resumen = $entity->get('cCierreResumen');

        return $fecha !== null && $fecha !== ''
            && is_string($resumen) && trim($resumen) !== '';
    }

    private function canDiligenciarActoCierre(Entity $entity): bool
    {
        $status = $entity->hasFetched('status')
            ? $entity->getFetched('status')
            : $entity->get('status');

        if ($status !== self::STATUS_FINALIZADO) {
            return false;
        }

        return $this->hasPatrulleroActaRedactada($entity);
    }

    private function hasPatrulleroActaRedactada(Entity $entity): bool
    {
        $estado = $entity->get('cActaEstado');

        if (!in_array($estado, ['Diligenciada', self::ACTA_APROBADA], true)) {
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

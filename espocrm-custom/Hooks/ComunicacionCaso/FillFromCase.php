<?php

namespace Espo\Custom\Hooks\ComunicacionCaso;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

class FillFromCase implements BeforeSave
{
    public static int $order = 5;

    public function __construct(
        private EntityManager $entityManager,
        private DateTimeUtil $dateTime
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        $caseId = $entity->get('caseId');

        if (!$caseId && $entity->isNew()) {
            $fetchedCaseId = $entity->getFetched('caseId');

            if ($fetchedCaseId) {
                $caseId = $fetchedCaseId;
                $entity->set('caseId', $caseId);
            }
        }

        if ($caseId) {
            $case = $this->entityManager->getEntityById('Case', $caseId);

            if ($case) {
                $radicado = trim((string) $case->get('cNumeroRadicado'));
                $entity->set('numeroRadicado', $radicado !== '' ? $radicado : null);
            }
        }

        if (!$entity->get('fecha')) {
            $entity->set('fecha', $this->dateTime->getSystemNowString());
        }

        $this->syncDestinatarioLabel($entity);

        $entity->set('name', $this->buildName($entity));
    }

    private function syncDestinatarioLabel(Entity $entity): void
    {
        $type = $entity->get('destinatarioTerceroType');
        $id = $entity->get('destinatarioTerceroId');

        if ($type && $id) {
            $related = $this->entityManager->getEntityById((string) $type, (string) $id);

            if ($related) {
                $entity->set('destinatario', trim((string) $related->get('name')));

                return;
            }
        }

        $linkedName = trim((string) $entity->get('destinatarioTerceroName'));

        if ($linkedName !== '') {
            $entity->set('destinatario', $linkedName);
        }
    }

    private function buildName(Entity $entity): string
    {
        $tipo = trim((string) $entity->get('tipo'));
        $destinatario = trim((string) $entity->get('destinatario'));
        $fecha = trim((string) $entity->get('fecha'));

        $parts = [];

        if ($tipo !== '') {
            $parts[] = $tipo;
        }

        if ($destinatario !== '') {
            $parts[] = $destinatario;
        }

        if ($fecha !== '') {
            $parts[] = $fecha;
        }

        if ($parts === []) {
            return 'Comunicación';
        }

        return implode(' — ', $parts);
    }
}

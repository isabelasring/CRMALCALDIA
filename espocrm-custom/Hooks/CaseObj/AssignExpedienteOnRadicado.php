<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Al pasar a Radicado, asigna número de expediente consecutivo (solo una vez).
 */
class AssignExpedienteOnRadicado implements BeforeSave
{
    public static int $order = 10;

    private const STATUS_RADICADO = 'Radicado';

    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($entity->get('status') !== self::STATUS_RADICADO) {
            return;
        }

        $current = trim((string) ($entity->get('cExpediente') ?? ''));

        if ($current !== '') {
            return;
        }

        $entity->set('cExpediente', $this->generateNextExpediente());
    }

    private function generateNextExpediente(): string
    {
        $year = date('Y');
        $prefix = $year . '-';
        $max = 0;

        $cases = $this->entityManager
            ->getRDBRepository('Case')
            ->select(['id', 'cExpediente'])
            ->where([
                'cExpediente!=' => null,
                'cExpediente!=' => '',
            ])
            ->find();

        foreach ($cases as $case) {
            $expediente = (string) $case->get('cExpediente');

            if (str_starts_with($expediente, $prefix)) {
                $suffix = substr($expediente, strlen($prefix));
                if (ctype_digit($suffix)) {
                    $max = max($max, (int) $suffix);
                }
            } elseif (ctype_digit($expediente)) {
                $max = max($max, (int) $expediente);
            }
        }

        $next = $max + 1;

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

<?php

namespace Espo\Custom\Tools\CaseObj;

use Espo\ORM\Entity;

/**
 * Criterios compartidos entre timeline/cronograma y transición de estado del caso.
 */
class CaseActaVisitaHelper
{
    public const STATUS_EN_PROCESO = 'En proceso';

    /** @var string[] */
    private const ADVANCE_TO_EN_PROCESO_FROM = [
        'Asignado',
    ];

    public static function isActaWithContent(Entity $acta): bool
    {
        $estado = trim((string) $acta->get('estado'));

        if (in_array($estado, ['Diligenciada', 'Aprobada'], true)) {
            return true;
        }

        foreach (['objetoVisita', 'situacionEncontrada', 'conclusion'] as $field) {
            if (trim((string) $acta->get($field)) !== '') {
                return true;
            }
        }

        return (bool) $acta->get('cFormatoActaVisitaPdfId');
    }

    public static function canAdvanceCaseToEnProceso(Entity $case): bool
    {
        if (!CaseRadicadoHelper::isRadicadoCompleto($case)) {
            return false;
        }

        $current = trim((string) $case->get('status'));

        return in_array($current, self::ADVANCE_TO_EN_PROCESO_FROM, true);
    }
}

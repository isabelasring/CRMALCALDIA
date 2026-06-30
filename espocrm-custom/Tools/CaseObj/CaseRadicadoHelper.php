<?php

namespace Espo\Custom\Tools\CaseObj;

use Espo\ORM\Entity;

/**
 * Radicado válido = número y expediente con formato oficial (solo tras radicación).
 */
class CaseRadicadoHelper
{
    /** @var string[] */
    public const FIELD_LIST = [
        'cNumeroRadicado',
        'cExpediente',
        'cRadicadoModo',
        'cRadicadoSiglas',
        'cRadicadoAnio',
    ];

    public static function isRadicadoCompleto(Entity $entity): bool
    {
        $numero = trim((string) $entity->get('cNumeroRadicado'));
        $expediente = trim((string) $entity->get('cExpediente'));

        if ($numero === '' || $expediente === '') {
            return false;
        }

        if (self::isPlaceholderExpediente($expediente)) {
            return false;
        }

        return RadicadoCatalog::parseRadicado($numero) !== null
            && RadicadoCatalog::parseExpediente($expediente) !== null;
    }

    public static function isPlaceholderExpediente(string $expediente): bool
    {
        $expediente = trim($expediente);

        return $expediente === '-' || $expediente === '—' || $expediente === '–';
    }

    public static function clearRadicadoFields(Entity $entity): void
    {
        foreach (self::FIELD_LIST as $field) {
            $entity->set($field, null);
        }
    }

    public static function restoreRadicadoFromFetched(Entity $entity): void
    {
        if ($entity->isNew()) {
            return;
        }

        foreach (self::FIELD_LIST as $field) {
            if ($entity->isAttributeChanged($field)) {
                $entity->set($field, $entity->getFetched($field));
            }
        }
    }
}

<?php

namespace Espo\Custom\Tools\CaseObj;

use Espo\ORM\Entity;

class CasePartyNameHelper
{
    public const PERSONA_JURIDICA = 'Persona jurídica';

    public const PERSONA_NATURAL = 'Persona natural';

    public static function buildFullName(?string $nombre, ?string $apellido, ?string $tipoPersona = null): string
    {
        $nombre = trim((string) $nombre);
        $apellido = trim((string) $apellido);

        if ($tipoPersona === self::PERSONA_JURIDICA) {
            return $nombre;
        }

        if ($nombre === '') {
            return $apellido;
        }

        if ($apellido === '') {
            return $nombre;
        }

        return $nombre . ' ' . $apellido;
    }

    public static function getPeticionarioFullName(Entity $entity): string
    {
        return self::buildFullName(
            $entity->get('cNombrePeticionario'),
            $entity->get('cApellidoPeticionario'),
            $entity->get('cTipoPersonaPeticionario')
        );
    }

    public static function hasPeticionarioName(Entity $entity): bool
    {
        return self::getPeticionarioFullName($entity) !== '';
    }
}

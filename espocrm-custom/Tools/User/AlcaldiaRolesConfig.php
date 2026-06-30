<?php

namespace Espo\Custom\Tools\User;

/**
 * Interruptor global de roles Alcaldía.
 * true = sin restricciones por rol (comportamiento tipo admin vía ACL EspoCRM).
 */
class AlcaldiaRolesConfig
{
    public const DISABLED = true;

    public static function isDisabled(): bool
    {
        return self::DISABLED;
    }
}

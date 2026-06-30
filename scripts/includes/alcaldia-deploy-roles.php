<?php

function alcaldiaDeployRolesDisabled(): bool
{
    static $disabled = null;

    if ($disabled !== null) {
        return $disabled;
    }

    $class = 'Espo\\Custom\\Tools\\User\\AlcaldiaRolesConfig';

    if (!class_exists($class)) {
        $path = '/var/www/html/custom/Espo/Custom/Tools/User/AlcaldiaRolesConfig.php';

        if (is_file($path)) {
            require_once $path;
        }
    }

    $disabled = class_exists($class) && $class::isDisabled();

    return $disabled;
}

function alcaldiaDeploySkipIfRolesDisabled(string $scriptLabel): bool
{
    if (!alcaldiaDeployRolesDisabled()) {
        return false;
    }

    echo "Modo sin roles activo — omitiendo {$scriptLabel}." . PHP_EOL;

    return true;
}

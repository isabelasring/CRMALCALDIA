<?php

function alcaldiaDeployRolesDisabled(): bool
{
    return true;
}

function alcaldiaDeploySkipIfRolesDisabled(string $scriptLabel): bool
{
    echo "Flujo por roles desactivado — omitiendo {$scriptLabel}." . PHP_EOL;

    return true;
}

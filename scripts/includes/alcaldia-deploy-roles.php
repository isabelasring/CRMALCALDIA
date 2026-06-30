<?php

function alcaldiaDeployRolesDisabled(): bool
{
    return false;
}

function alcaldiaDeploySkipIfRolesDisabled(string $scriptLabel): bool
{
    return false;
}

# Despliega backend (metadata, hooks) y frontend (JS) al contenedor EspoCRM.
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

Write-Host 'Copiando backend custom...'
docker cp "$root\espocrm-custom\." espocrm:/var/www/html/custom/Espo/Custom/

Write-Host 'Copiando frontend client/custom...'
docker cp "$root\espocrm-custom\files\client\custom\." espocrm:/var/www/html/client/custom/

Write-Host 'Rebuild + clear cache...'
docker exec espocrm php command.php rebuild
docker exec espocrm php command.php clear-cache

Write-Host 'Listo. Recarga el navegador con Ctrl+Shift+R en http://localhost:8080'

<?php

/**
 * Ejecuta manualmente el job de alertas de vencimiento (pruebas).
 *
 * docker cp scripts/run-case-vencimiento-alerts.php espocrm:/tmp/
 * docker exec espocrm php command.php run-job CheckCaseVencimientoAlerts
 */

echo "Ejecute: docker exec espocrm php command.php run-job CheckCaseVencimientoAlerts\n";
echo "Verifique que espocrm-daemon esté corriendo para el job programado diario.\n";

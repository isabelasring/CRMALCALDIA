<?php

use Espo\Core\Utils\Metadata;

/**
 * Permisos de ComunicacionCaso para todos los roles operativos.
 *
 * @param array<string, mixed> $data
 * @param array<string, mixed> $fieldData
 */
function alcaldiaApplyComunicacionCasoPermissions(
    Metadata $metadata,
    array &$data,
    array &$fieldData,
    string $read = 'all',
    string $edit = 'all'
): void {
    if (!$metadata->get(['scopes', 'ComunicacionCaso', 'entity'])) {
        return;
    }

    $data['ComunicacionCaso'] = [
        'create' => 'yes',
        'read' => $read,
        'edit' => $edit,
        'delete' => 'no',
        'stream' => 'no',
    ];

    $fields = array_keys($metadata->get(['entityDefs', 'ComunicacionCaso', 'fields']) ?? []);

    if ($fields === []) {
        return;
    }

    $fieldData['ComunicacionCaso'] = [];

    foreach ($fields as $field) {
        $fieldData['ComunicacionCaso'][$field] = [
            'read' => 'yes',
            'edit' => 'yes',
        ];
    }
}

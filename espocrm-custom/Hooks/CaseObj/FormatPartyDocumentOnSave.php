<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Custom\Tools\Party\DocumentNormalizer;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Formatea NIT (900.123.456-7) en documentos de persona jurídica.
 */
class FormatPartyDocumentOnSave implements BeforeSave
{
    public static int $order = 3;

    private const PERSONA_JURIDICA = 'Persona jurídica';

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        $this->formatIfJuridica(
            $entity,
            'cTipoPersonaPeticionario',
            'cDocumentoPeticionario'
        );

        $this->formatIfJuridica(
            $entity,
            'cTipoPersonaPerjudicante',
            'cDocumentoPerjudicante'
        );
    }

    private function formatIfJuridica(Entity $entity, string $tipoField, string $documentField): void
    {
        if (trim((string) $entity->get($tipoField)) !== self::PERSONA_JURIDICA) {
            return;
        }

        $document = trim((string) $entity->get($documentField));

        if ($document === '') {
            return;
        }

        $entity->set($documentField, DocumentNormalizer::formatNit($document));
    }
}

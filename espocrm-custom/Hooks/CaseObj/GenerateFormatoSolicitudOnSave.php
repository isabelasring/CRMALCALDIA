<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\InjectableFactory;
use Espo\Custom\Tools\CaseObj\FormatoSolicitudAttacher;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Al guardar un caso, genera automáticamente el PDF del formato de solicitud.
 */
class GenerateFormatoSolicitudOnSave implements AfterSave
{
    public static int $order = 45;

    private const FORMATO_FIELDS = [
        'cFechaCaso',
        'cNumeroRadicado',
        'cExpediente',
        'cPeticionario',
        'cCedula',
        'cDireccion',
        'cTelefono',
        'cBarrio',
        'cCorreo',
        'cCanalDeReporte',
        'cPerjudicante',
        'cTelefonoPerjudicante',
        'cDireccionPerjudicante',
        'cBarrioPerjudicante',
        'cRespuestaInmediata',
        'cTipo',
        'cCategoria',
        'description',
        'cRecibidaPorId',
        'cRemitidoAId',
    ];

    public function __construct(
        private InjectableFactory $injectableFactory
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if ($options->get('skipFormatoSolicitud')) {
            return;
        }

        if (!$this->shouldGenerate($entity)) {
            return;
        }

        $attacher = $this->injectableFactory->create(FormatoSolicitudAttacher::class);
        $attacher->attachToCase($entity);
    }

    private function shouldGenerate(Entity $entity): bool
    {
        if (trim((string) $entity->get('cPeticionario')) === '') {
            return false;
        }

        if ($this->isJustCreated($entity)) {
            return true;
        }

        foreach (self::FORMATO_FIELDS as $field) {
            if ($entity->isAttributeChanged($field)) {
                return true;
            }
        }

        return false;
    }

    private function isJustCreated(Entity $entity): bool
    {
        $createdAt = $entity->get('createdAt');
        $modifiedAt = $entity->get('modifiedAt');

        return $createdAt && $modifiedAt && $createdAt === $modifiedAt;
    }
}

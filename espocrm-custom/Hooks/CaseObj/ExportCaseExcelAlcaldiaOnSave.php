<?php

namespace Espo\Custom\Hooks\CaseObj;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\InjectableFactory;
use Espo\Custom\Tools\CaseObj\CasePartyNameHelper;
use Espo\Custom\Tools\CaseObj\ExcelAlcaldiaExporter;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Actualiza excelAlcaldia.xlsx al guardar un caso radicado (radicado + expediente).
 */
class ExportCaseExcelAlcaldiaOnSave implements AfterSave
{
    public static int $order = 46;

    private const EXPORT_FIELDS = [
        'cFechaCaso',
        'cNumeroRadicado',
        'cExpediente',
        'cNombrePeticionario',
        'cApellidoPeticionario',
        'cDocumentoPeticionario',
        'cViaPrincipalPeticionario',
        'cNumViaPrincipalPeticionario',
        'cLetraViaPrincipalPeticionario',
        'cCuadranteViaPrincipalPeticionario',
        'cGeneradoraPeticionario',
        'cLetraGeneradoraPeticionario',
        'cCuadranteGeneradoraPeticionario',
        'cPlacaPeticionario',
        'cBloquePeticionario',
        'cInteriorPeticionario',
        'cDireccionPeticionario',
        'cTelefonoPeticionario',
        'cBarrioPeticionario',
        'cCorreoPeticionario',
        'cCanalDeReportePeticionario',
        'cRecursoTema',
        'cAsunto',
        'cZonaAlcaldiaPeticionario',
        'cFechaVencimiento',
        'cUltimaActuacion',
        'cProximaActuacion',
        'cNombrePerjudicante',
        'cApellidoPerjudicante',
        'cDocumentoPerjudicante',
        'cTelefonoPerjudicante',
        'cViaPrincipalPerjudicante',
        'cNumViaPrincipalPerjudicante',
        'cLetraViaPrincipalPerjudicante',
        'cCuadranteViaPrincipalPerjudicante',
        'cGeneradoraPerjudicante',
        'cLetraGeneradoraPerjudicante',
        'cCuadranteGeneradoraPerjudicante',
        'cPlacaPerjudicante',
        'cBloquePerjudicante',
        'cInteriorPerjudicante',
        'cDireccionPerjudicante',
        'cBarrioPerjudicante',
        'cRespuestaInmediata',
        'description',
        'assignedUserId',
    ];

    public function __construct(
        private InjectableFactory $injectableFactory
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if ($options->get('skipCaseExcelAlcaldia') || $options->get('skipCaseSolicitudExcel')) {
            return;
        }

        if (!$this->shouldExport($entity)) {
            return;
        }

        $this->injectableFactory->create(ExcelAlcaldiaExporter::class)->exportCase($entity);
    }

    private function shouldExport(Entity $entity): bool
    {
        if (!CasePartyNameHelper::hasPeticionarioName($entity)) {
            return false;
        }

        if (!$this->isPostRadicado($entity)) {
            return false;
        }

        if ($entity->isNew()) {
            return true;
        }

        foreach (self::EXPORT_FIELDS as $field) {
            if ($entity->isAttributeChanged($field)) {
                return true;
            }
        }

        return false;
    }

    private function isPostRadicado(Entity $entity): bool
    {
        $numero = trim((string) $entity->get('cNumeroRadicado'));
        $expediente = trim((string) $entity->get('cExpediente'));

        return $numero !== '' && $expediente !== '';
    }
}

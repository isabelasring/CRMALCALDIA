define('custom:helpers/formato-acta-visita-access', [
    'custom:helpers/acta-visita-case-status',
], function (ActaVisitaCaseStatus) {

    const canDownloadFormatoActaVisita = function (user, model) {
        return !!(user && model);
    };

    const isFormatoActaHabilitado = function (model) {
        return ActaVisitaCaseStatus.isFormatoActaHabilitado(model);
    };

    return {
        canDownloadFormatoActaVisita: canDownloadFormatoActaVisita,
        isFormatoActaHabilitado: isFormatoActaHabilitado,
    };
});

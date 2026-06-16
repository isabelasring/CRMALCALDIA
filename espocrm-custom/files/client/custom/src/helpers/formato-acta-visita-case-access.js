define('custom:helpers/formato-acta-visita-case-access', [
    'custom:helpers/post-radicacion-fields',
], function (PostRadicacionFields) {

    const canDownloadFormatoActaVisitaFromCase = function (user, model) {
        if (!user || !model) {
            return false;
        }

        return PostRadicacionFields.isCasePostRadicado(model);
    };

    return {
        canDownloadFormatoActaVisitaFromCase: canDownloadFormatoActaVisitaFromCase,
    };
});

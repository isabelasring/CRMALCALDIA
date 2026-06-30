define('custom:helpers/formato-solicitud-access', [], function () {

    const isCasePostRadicado = function (model) {
        if (!model) {
            return false;
        }

        return !!String(model.get('cNumeroRadicado') || '').trim();
    };

    const canDownloadFormatoSolicitud = function (user, model) {
        if (!user || !model) {
            return false;
        }

        return isCasePostRadicado(model);
    };

    const isFormatoSolicitudHabilitado = function (model) {
        return isCasePostRadicado(model);
    };

    const requiresActaDiligenciada = function () {
        return false;
    };

    return {
        canDownloadFormatoSolicitud: canDownloadFormatoSolicitud,
        isFormatoSolicitudHabilitado: isFormatoSolicitudHabilitado,
        requiresActaDiligenciada: requiresActaDiligenciada,
    };
});

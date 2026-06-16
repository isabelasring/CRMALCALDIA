define('custom:helpers/formato-solicitud-access', [
    'custom:helpers/post-radicacion-fields',
], function (PostRadicacionFields) {

    const canDownloadFormatoSolicitud = function (user, model) {
        if (!user || !model) {
            return false;
        }

        return PostRadicacionFields.isCasePostRadicado(model);
    };

    const isFormatoSolicitudHabilitado = function (model) {
        return PostRadicacionFields.isCasePostRadicado(model);
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

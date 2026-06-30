define('custom:helpers/formato-solicitud-access', [
    'custom:helpers/radicacion-fields',
], function (RadicacionFields) {

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

        if (!isCasePostRadicado(model)) {
            return false;
        }

        if (RadicacionFields.isAdminUser(user)) {
            return true;
        }

        if (RadicacionFields.isRadicacionUser(user)) {
            return true;
        }

        if (RadicacionFields.isInspeccionUser(user)) {
            return true;
        }

        return false;
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

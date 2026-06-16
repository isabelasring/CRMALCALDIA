define('custom:helpers/formato-actuo-archivo-access', [
    'custom:helpers/actuo-archivo-case-status',
], function (ActuoArchivoCaseStatus) {

    const canDownloadFormatoActuoArchivo = function (user, model) {
        return !!(user && model);
    };

    const isFormatoActuoHabilitado = function (model) {
        return ActuoArchivoCaseStatus.isFormatoActuoHabilitado(model);
    };

    return {
        canDownloadFormatoActuoArchivo: canDownloadFormatoActuoArchivo,
        isFormatoActuoHabilitado: isFormatoActuoHabilitado,
    };
});

define('custom:helpers/formato-actuo-archivo-case-access', [], function () {

    const canDownloadFormatoActuoArchivoFromCase = function (user, model) {
        if (!user || !model) {
            return false;
        }

        return model.get('status') === 'Finalizado';
    };

    return {
        canDownloadFormatoActuoArchivoFromCase: canDownloadFormatoActuoArchivoFromCase,
    };
});

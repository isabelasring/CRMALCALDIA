define('custom:helpers/formato-acta-visita-case-access', [], function () {

    const canDownloadFormatoActaVisitaFromCase = function (user, model) {
        if (!user || !model) {
            return false;
        }

        if (user.isAdmin && user.isAdmin()) {
            return true;
        }

        return !!model.id;
    };

    return {
        canDownloadFormatoActaVisitaFromCase: canDownloadFormatoActaVisitaFromCase,
    };
});

define('custom:helpers/formato-acta-visita-case-access', [
    'custom:helpers/radicacion-fields',
    'custom:helpers/post-radicacion-fields',
], function (RadicacionFields, PostRadicacionFields) {

    const canDownloadFormatoActaVisitaFromCase = function (user, model) {
        if (!user || !model) {
            return false;
        }

        if (!PostRadicacionFields.isCasePostRadicado(model)) {
            return false;
        }

        if (user.isAdmin()) {
            return true;
        }

        if (RadicacionFields.isInspeccionUser(user)) {
            return true;
        }

        if (PostRadicacionFields.isAsignadorUser(user)) {
            return true;
        }

        return model.get('assignedUserId') === user.id;
    };

    return {
        canDownloadFormatoActaVisitaFromCase: canDownloadFormatoActaVisitaFromCase,
    };
});

define('custom:helpers/formato-acta-visita-access', [
    'custom:helpers/radicacion-fields',
    'custom:helpers/post-radicacion-fields',
    'custom:helpers/patrullero-acta',
], function (RadicacionFields, PostRadicacionFields, PatrulleroActa) {

    const canDownloadFormatoActaVisita = function (user, model) {
        if (!user || !model) {
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

        if (PatrulleroActa.isPatrulleroUser(user)) {
            return model.get('assignedUserId') === user.id;
        }

        return model.get('assignedUserId') === user.id;
    };

    return {
        canDownloadFormatoActaVisita: canDownloadFormatoActaVisita,
    };
});

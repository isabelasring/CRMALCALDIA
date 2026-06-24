define('custom:helpers/post-radicacion-fields', [
    'custom:helpers/radicacion-fields',
], function (RadicacionFields) {

    const ASIGNACION_FIELD = 'assignedUser';

    const isAsignadorUser = function (user) {
        return RadicacionFields.isAsignadorUser(user);
    };

    const isCasePostRadicado = function (model) {
        return RadicacionFields.isCasePostRadicado(model);
    };

    const shouldShowAsignacion = function (user, model) {
        if (!isCasePostRadicado(model)) {
            return false;
        }

        return isAsignadorUser(user);
    };

    const canEditAsignacion = function (user, model) {
        return shouldShowAsignacion(user, model);
    };

    return {
        ASIGNACION_FIELD: ASIGNACION_FIELD,
        isAsignadorUser: isAsignadorUser,
        isCasePostRadicado: isCasePostRadicado,
        shouldShowAsignacion: shouldShowAsignacion,
        canEditAsignacion: canEditAsignacion,
    };
});

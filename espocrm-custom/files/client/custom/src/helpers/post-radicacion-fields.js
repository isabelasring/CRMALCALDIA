define('custom:helpers/post-radicacion-fields', [], function () {

    const ROLE_ASIGNADOR = 'Asignador';
    const ASIGNACION_FIELD = 'assignedUser';

    const isAsignadorUser = function (user) {
        if (!user) {
            return false;
        }

        if (user.isAdmin()) {
            return true;
        }

        const roles = user.get('rolesNames') || {};

        return Object.values(roles).includes(ROLE_ASIGNADOR);
    };

    const isCasePostRadicado = function (model) {
        if (!model) {
            return false;
        }

        const numero = String(model.get('cNumeroRadicado') || '').trim();
        const expediente = String(model.get('cExpediente') || '').trim();

        return numero !== '' && expediente !== '';
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

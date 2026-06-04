define('custom:helpers/radicacion-fields', [], function () {

    const ROLE_RADICACION = 'Radicación';
    const RADICADO_FIELDS = ['cNumeroRadicado', 'cExpediente'];

    const isRadicacionUser = function (user) {
        if (!user) {
            return false;
        }

        if (user.isAdmin()) {
            return true;
        }

        const roles = user.get('rolesNames') || {};

        return Object.values(roles).includes(ROLE_RADICACION);
    };

    const isCaseRadicado = function (model) {
        if (!model) {
            return false;
        }

        const numero = String(model.get('cNumeroRadicado') || '').trim();
        const expediente = String(model.get('cExpediente') || '').trim();

        return numero !== '' || expediente !== '';
    };

    const shouldShowRadicacionFields = function (user, model) {
        if (isRadicacionUser(user)) {
            return true;
        }

        return isCaseRadicado(model);
    };

    return {
        RADICADO_FIELDS: RADICADO_FIELDS,
        isRadicacionUser: isRadicacionUser,
        isCaseRadicado: isCaseRadicado,
        shouldShowRadicacionFields: shouldShowRadicacionFields,
    };
});

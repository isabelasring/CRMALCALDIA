define('custom:helpers/radicacion-fields', [], function () {

    const ROLE_RADICACION = 'radicacion';
    const ROLE_INSPECCION = 'inspeccion';
    const RADICADO_FIELDS = ['cNumeroRadicado', 'cExpediente'];
    const FECHA_VENCIMIENTO_FIELD = 'cFechaVencimiento';

    const normalize = function (value) {
        return String(value)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    };

    const RADICADO_ALL_FIELDS = [
        'cRadicadoModo',
        'cRadicadoSiglas',
        'cRadicadoAnio',
        'cNumeroRadicado',
        'cExpediente',
    ];

    const hasRole = function (user, roleKey) {
        const rolesNames = user.get('rolesNames') || {};
        const fromNames = Object.values(rolesNames);

        if (fromNames.some((name) => normalize(name) === roleKey)) {
            return true;
        }

        const roles = user.get('roles') || user.get('rolesIds') || [];

        return roles.some((role) => normalize(String(role.name || role || '')) === roleKey);
    };

    const matchesUserName = function (user, names) {
        const userName = normalize(user.get('userName') || '');

        return names.some((name) => userName === normalize(name));
    };

    const isRadicacionUser = function (user) {
        if (!user) {
            return false;
        }

        if (user.isAdmin()) {
            return true;
        }

        if (matchesUserName(user, ['edwin.radicacion', 'edwin'])) {
            return true;
        }

        return hasRole(user, ROLE_RADICACION);
    };

    const isInspeccionUser = function (user) {
        if (!user) {
            return false;
        }

        if (user.isAdmin()) {
            return true;
        }

        if (matchesUserName(user, ['juan.inspeccion', 'juan'])) {
            return true;
        }

        return hasRole(user, ROLE_INSPECCION);
    };

    const shouldShowFechaVencimiento = function (user) {
        return isInspeccionUser(user);
    };

    const isCaseRadicado = function (model) {
        if (!model) {
            return false;
        }

        const numero = String(model.get('cNumeroRadicado') || '').trim();
        const expediente = String(model.get('cExpediente') || '').trim();

        return numero !== '' || expediente !== '';
    };

    const isCasePostRadicado = function (model) {
        if (!model) {
            return false;
        }

        const numero = String(model.get('cNumeroRadicado') || '').trim();
        const expediente = String(model.get('cExpediente') || '').trim();

        return numero !== '' && expediente !== '';
    };

    const hasRadicadoMetadataChanged = function (model, baseline) {
        if (!model || !baseline) {
            return false;
        }

        return ['cRadicadoModo', 'cRadicadoSiglas', 'cRadicadoAnio'].some(function (field) {
            return String(model.get(field) || '').trim() !== String(baseline[field] || '').trim();
        });
    };

    const shouldMutateRadicadoPreview = function (recordView) {
        const model = recordView.model;

        if (!model || model.isNew()) {
            return true;
        }

        return hasRadicadoMetadataChanged(model, recordView._lockedRadicadoValues);
    };

    const shouldShowRadicacionFields = function (user, model) {
        if (isRadicacionUser(user)) {
            return true;
        }

        return isCaseRadicado(model);
    };

    const stripRadicadoFromModel = function (model) {
        if (!model) {
            return;
        }

        RADICADO_ALL_FIELDS.forEach(function (field) {
            if (model.get(field)) {
                model.set(field, null, {silent: true});
            }
        });
    };

    return {
        RADICADO_FIELDS: RADICADO_FIELDS,
        RADICADO_ALL_FIELDS: RADICADO_ALL_FIELDS,
        FECHA_VENCIMIENTO_FIELD: FECHA_VENCIMIENTO_FIELD,
        isRadicacionUser: isRadicacionUser,
        isInspeccionUser: isInspeccionUser,
        isCaseRadicado: isCaseRadicado,
        isCasePostRadicado: isCasePostRadicado,
        shouldShowRadicacionFields: shouldShowRadicacionFields,
        shouldShowFechaVencimiento: shouldShowFechaVencimiento,
        hasRadicadoMetadataChanged: hasRadicadoMetadataChanged,
        shouldMutateRadicadoPreview: shouldMutateRadicadoPreview,
        stripRadicadoFromModel: stripRadicadoFromModel,
    };
});

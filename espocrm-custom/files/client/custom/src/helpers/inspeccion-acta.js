define('custom:helpers/inspeccion-acta', [], function () {

    const isInspeccionUser = function (user) {
        if (!user || user.isAdmin()) {
            return false;
        }

        const roles = user.get('rolesNames') || {};

        return Object.values(roles).some(function (name) {
            return name === 'Inspección'
                || name === 'Inspeccion'
                || /inspecci[oó]n/i.test(String(name));
        });
    };

    const isPendingActaReview = function (model) {
        if (!model) {
            return false;
        }

        return model.get('status') === 'Visita realizada'
            && model.get('cActaEstado') === 'Diligenciada';
    };

    const hasPatrulleroActaRedactada = function (model) {
        if (!model) {
            return false;
        }

        const estado = model.get('cActaEstado');

        if (estado !== 'Diligenciada' && estado !== 'Aprobada') {
            return false;
        }

        if (!model.get('cActaFechaVisita')) {
            return false;
        }

        return !!(
            model.get('cActaHallazgos')
            || model.get('cActaMedidasTomadas')
            || model.get('cActaNombreVisitado')
            || model.get('cActaObservaciones')
        );
    };

    const shouldShowActaRevision = function (user, model) {
        return isInspeccionUser(user)
            && isPendingActaReview(model)
            && hasPatrulleroActaRedactada(model);
    };

    const hasActaVisitaCompleta = function (model) {
        return hasPatrulleroActaRedactada(model)
            && model.get('cActaEstado') === 'Aprobada'
            && model.get('cActaRegistroOficial');
    };

    const shouldShowActoCierre = function (user, model) {
        if (!isInspeccionUser(user) || !model) {
            return false;
        }

        return model.get('status') === 'Finalizado'
            && hasPatrulleroActaRedactada(model)
            && !model.get('cCierreProcesoCompleto');
    };

    const shouldShowActoCierreReadOnly = function (user, model) {
        if (!isInspeccionUser(user) || !model) {
            return false;
        }

        return model.get('status') === 'Proceso cerrado'
            || (model.get('cCierreProcesoCompleto') && hasActaVisitaCompleta(model));
    };

    const shouldFinalizeCaseStatus = function (user, model) {
        if (!isInspeccionUser(user) || !model) {
            return false;
        }

        return model.get('status') === 'Visita aprobada'
            && hasActaVisitaCompleta(model);
    };

    const shouldShowActaVisitaReadOnly = function (user, model) {
        if (!model) {
            return false;
        }

        if (isPendingActaReview(model) && hasPatrulleroActaRedactada(model)) {
            return true;
        }

        return shouldFinalizeCaseStatus(user, model);
    };

    return {
        isInspeccionUser: isInspeccionUser,
        hasPatrulleroActaRedactada: hasPatrulleroActaRedactada,
        hasActaVisitaCompleta: hasActaVisitaCompleta,
        shouldShowActaRevision: shouldShowActaRevision,
        shouldFinalizeCaseStatus: shouldFinalizeCaseStatus,
        shouldShowActoCierre: shouldShowActoCierre,
        shouldShowActoCierreReadOnly: shouldShowActoCierreReadOnly,
        shouldShowActaVisitaReadOnly: shouldShowActaVisitaReadOnly,
    };
});

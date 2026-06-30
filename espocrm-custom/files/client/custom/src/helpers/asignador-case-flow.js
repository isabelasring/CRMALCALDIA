define('custom:helpers/asignador-case-flow', [
    'custom:helpers/radicacion-fields',
], function (RadicacionFields) {

    const BODY_CLASS = 'alcaldia-asignador-asignar-page';

    const ASIGNACION_FIELDS = [
        'assignedUser',
        'cMotivoReasignacion',
    ];

    const isAsignadorOnlyUser = function (user) {
        if (RadicacionFields.isAdminUser(user)) {
            return false;
        }

        return RadicacionFields.resolveHomeProfile(user) === 'asignador';
    };

    const syncBodyClass = function (enabled) {
        document.body.classList.toggle(BODY_CLASS, enabled);
    };

    const restoreNonAsignacionAttributes = function (model) {
        if (!model || model.isNew()) {
            return;
        }

        const attributes = model.attributes || {};

        Object.keys(attributes).forEach(function (key) {
            if (ASIGNACION_FIELDS.indexOf(key) !== -1
                || key === 'assignedUserId'
                || key === 'assignedUserName') {
                return;
            }

            if (typeof model.getFetched !== 'function') {
                return;
            }

            const fetched = model.getFetched(key);

            if (fetched !== undefined) {
                model.set(key, fetched, {silent: true});
            }
        });
    };

    const apply = function (recordView) {
        if (!recordView || !recordView.isRendered || !recordView.isRendered()) {
            return;
        }

        const user = recordView.getUser();

        if (!isAsignadorOnlyUser(user) || recordView.model.isNew() || recordView.mode === 'detail') {
            syncBodyClass(false);
            return;
        }

        syncBodyClass(true);
    };

    const schedule = function (recordView) {
        apply(recordView);

        [150, 500, 1200].forEach(function (delay) {
            window.setTimeout(function () {
                apply(recordView);
            }, delay);
        });
    };

    const prepareModelForSave = function (recordView) {
        if (!isAsignadorOnlyUser(recordView.getUser())) {
            return;
        }

        restoreNonAsignacionAttributes(recordView.model);
    };

    const setup = function (recordView) {
        const self = recordView;

        RadicacionFields.ensureProfile(self.getUser());

        RadicacionFields.onProfileReady(function () {
            if (!self.isRendered || !self.isRendered()) {
                return;
            }

            schedule(self);
        });

        self.once('remove', function () {
            syncBodyClass(false);
        });
    };

    return {
        setup: setup,
        schedule: schedule,
        prepareModelForSave: prepareModelForSave,
    };
});

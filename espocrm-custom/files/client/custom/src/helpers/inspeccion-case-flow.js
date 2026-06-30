define('custom:helpers/inspeccion-case-flow', [
    'custom:helpers/radicacion-fields',
], function (RadicacionFields) {

    const PANEL_RADICACION = 'radicacionCaso';

    const findPanel = function (recordView, name) {
        return recordView.$el.find(
            '.panel[data-name="' + name + '"], ' +
            '.panel[data-panel-name="' + name + '"], ' +
            '.record-panel[data-name="' + name + '"], ' +
            '[data-name="' + name + '"].panel'
        );
    };

    const lockRadicadoFields = function (recordView) {
        const user = recordView.getUser();

        if (!RadicacionFields.isInspeccionUser(user)) {
            return;
        }

        if (RadicacionFields.canEditRadicadoCase(user)) {
            return;
        }

        const show = RadicacionFields.shouldShowRadicacionFields(user, recordView.model);
        const $panel = findPanel(recordView, PANEL_RADICACION);

        if ($panel.length) {
            $panel.toggle(show);
            $panel.addClass('alcaldia-inspeccion-radicado-readonly');
        }

        RadicacionFields.RADICADO_ALL_FIELDS.forEach(function (field) {
            const view = recordView.getFieldView(field);

            if (view && typeof view.setReadOnly === 'function') {
                view.setReadOnly();
            }

            recordView.$el
                .find('[data-name="' + field + '"]')
                .closest('.cell')
                .addClass('alcaldia-field-readonly');
        });
    };

    const apply = function (recordView) {
        if (!recordView || !recordView.isRendered || !recordView.isRendered()) {
            return;
        }

        lockRadicadoFields(recordView);
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
        const user = recordView.getUser();
        const model = recordView.model;

        if (!RadicacionFields.isInspeccionUser(user)) {
            return;
        }

        if (RadicacionFields.canEditRadicadoCase(user)) {
            return;
        }

        if (model.isNew() || !RadicacionFields.isCaseRadicado(model)) {
            RadicacionFields.stripRadicadoFromModel(model);
        }
    };

    const setup = function (recordView) {
        const self = recordView;

        if (!self.model.isNew()) {
            RadicacionFields.ensureProfile(self.getUser());
        }

        RadicacionFields.onProfileReady(function () {
            if (!self.isRendered || !self.isRendered()) {
                return;
            }

            schedule(self);
        });
    };

    return {
        setup: setup,
        schedule: schedule,
        prepareModelForSave: prepareModelForSave,
        lockRadicadoFields: lockRadicadoFields,
    };
});

define('custom:helpers/case-create-form', [], function () {

    const DETAIL_ONLY_PANELS = [
        'radicacionCaso',
        'actaVisita',
        'formatoGenerado',
    ];

    const DETAIL_ONLY_FIELDS = [
        'status',
        'cNumeroRadicado',
        'cExpediente',
        'cPanelActaVisita',
        'cFormatoSolicitudPdf',
    ];

    const panelSelector = function (name) {
        return '.panel[data-name="' + name + '"], ' +
            '.panel[data-panel-name="' + name + '"], ' +
            '.record-panel[data-name="' + name + '"], ' +
            '[data-name="' + name + '"].panel';
    };

    const hideDetailOnlyUi = function (recordView) {
        if (!recordView || !recordView.model || !recordView.model.isNew()) {
            return;
        }

        DETAIL_ONLY_PANELS.forEach(function (name) {
            recordView.$el.find(panelSelector(name)).addClass('hidden');
        });

        DETAIL_ONLY_FIELDS.forEach(function (field) {
            recordView.$el
                .find('.field[data-name="' + field + '"]')
                .closest('.cell, .field')
                .addClass('hidden');
        });
    };

    const applyBogotaFechaCaso = function (recordView) {
        if (!recordView || !recordView.model || !recordView.model.isNew()) {
            return;
        }

        if (typeof recordView.getDateTime !== 'function') {
            return;
        }

        const dateTime = recordView.getDateTime();
        const now = dateTime.toDisplay(dateTime.getNow(1));

        if (now) {
            recordView.model.set('cFechaCaso', now, {silent: true});
        }
    };

    const fetchServerDefaults = function (recordView) {
        if (!recordView || !recordView.model || !recordView.model.isNew()) {
            return Promise.resolve();
        }

        if (typeof Espo === 'undefined' || !Espo.Ajax) {
            return Promise.resolve();
        }

        return Espo.Ajax.getRequest('Case/action/createDefaults')
            .then(function (data) {
                if (!data || !data.cFechaCaso) {
                    return;
                }

                recordView.model.set('cFechaCaso', data.cFechaCaso, {silent: true});

                const fieldView = recordView.getFieldView('cFechaCaso');

                if (fieldView && fieldView.isRendered && fieldView.isRendered()) {
                    fieldView.reRender();
                }
            })
            .catch(function () {});
    };

    const schedule = function (recordView) {
        hideDetailOnlyUi(recordView);

        [100, 400, 1000].forEach(function (delay) {
            window.setTimeout(function () {
                hideDetailOnlyUi(recordView);
            }, delay);
        });
    };

    const setup = function (recordView) {
        if (!recordView.model.isNew()) {
            return;
        }

        applyBogotaFechaCaso(recordView);
        fetchServerDefaults(recordView);
    };

    return {
        setup: setup,
        schedule: schedule,
        hideDetailOnlyUi: hideDetailOnlyUi,
        applyBogotaFechaCaso: applyBogotaFechaCaso,
    };
});

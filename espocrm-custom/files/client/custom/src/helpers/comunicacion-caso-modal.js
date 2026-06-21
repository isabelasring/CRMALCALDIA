define('custom:helpers/comunicacion-caso-modal', [
    'helpers/record-modal',
    'custom:helpers/comunicacion-caso-from-case',
], function (RecordModal, ComunicacionFromCase) {

    var RecordModalHelper = RecordModal.default || RecordModal;

    var resolveHostView = function (view) {
        if (!view) {
            return null;
        }

        if (view.recordViewObject) {
            return view.recordViewObject;
        }

        if (view.scope === 'Case' && typeof view.createView === 'function') {
            return view;
        }

        var current = view;

        for (var i = 0; i < 15 && current; i++) {
            if (current.scope === 'Case' && typeof current.createView === 'function') {
                return current;
            }

            current = current.getParentView ? current.getParentView() : null;
        }

        return null;
    };

    var openCreate = function (hostView, caseModel, options) {
        options = options || {};

        var host = resolveHostView(hostView);

        if (!host || !caseModel || !caseModel.id) {
            Espo.Ui.error('No se pudo abrir el formulario de comunicación.');

            return Promise.reject();
        }

        var helper = new RecordModalHelper();
        var attributes = ComunicacionFromCase.buildDefaultsFromCase(caseModel);
        var afterSave = function () {
            if (typeof options.onAfterSave === 'function') {
                options.onAfterSave();
            }
        };

        return helper.showCreate(host, {
            entityType: 'ComunicacionCaso',
            attributes: attributes,
            layoutName: 'edit',
            fullFormDisabled: true,
            relate: {
                model: caseModel,
                link: 'case',
            },
            afterSave: afterSave,
        });
    };

    var openEdit = function (hostView, id, options) {
        options = options || {};

        var host = resolveHostView(hostView);

        if (!host || !id) {
            Espo.Ui.error('No se pudo abrir la comunicación.');

            return Promise.reject();
        }

        var helper = new RecordModalHelper();
        var afterSave = function () {
            if (typeof options.onAfterSave === 'function') {
                options.onAfterSave();
            }
        };

        return helper.showEdit(host, {
            entityType: 'ComunicacionCaso',
            id: id,
            layoutName: 'edit',
            fullFormDisabled: true,
            afterSave: afterSave,
        });
    };

    return {
        openCreate: openCreate,
        openEdit: openEdit,
    };
});

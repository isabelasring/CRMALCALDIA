define('custom:helpers/radicacion-edit-mode', [
    'custom:helpers/radicacion-fields',
], function (RadicacionFields) {

    const normalize = function (value) {
        return String(value)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    };

    const getUserRoleNames = function (user) {
        const names = [];

        Object.values(user.get('rolesNames') || {}).forEach(function (name) {
            names.push(name);
        });

        Object.values(user.get('teamsNames') || {}).forEach(function (name) {
            names.push(name);
        });

        const defaultTeam = user.get('defaultTeamName');

        if (defaultTeam) {
            names.push(defaultTeam);
        }

        return names;
    };

    const userHasRoleKey = function (user, roleKey) {
        return getUserRoleNames(user).some(function (name) {
            const normalized = normalize(name);

            return normalized === roleKey || normalized.indexOf(roleKey) !== -1;
        });
    };

    const isPureRadicacionUser = function (user) {
        if (!user || user.isAdmin()) {
            return false;
        }

        if (userHasRoleKey(user, 'radicacion')) {
            return true;
        }

        if (!RadicacionFields.isRadicacionUser(user)) {
            return false;
        }

        if (RadicacionFields.isPatrulleroUser(user) && !RadicacionFields.isRadicacionUser(user)) {
            return false;
        }

        return true;
    };

    const PANELS_HIDDEN_FOR_RADICACION = [
        'actaVisita',
        'actaRevision',
        'actuoArchivoPanel',
        'gestionPosteriorRadicacion',
    ];

    const hideNonRadicacionPanels = function (recordView) {
        if (!recordView || !isPureRadicacionUser(recordView.getUser())) {
            return;
        }

        if (typeof recordView.findPanel !== 'function') {
            return;
        }

        PANELS_HIDDEN_FOR_RADICACION.forEach(function (name) {
            recordView.findPanel(name).hide();
        });

        recordView.$el.find('[data-name="cPanelActaVisita"]').closest('.panel, .record-panel, .cell').hide();
    };

    const getEditableFields = function () {
        return [
            'cNumeroRadicado',
            'cExpediente',
            'cRadicadoModo',
            'cRadicadoSiglas',
            'cRadicadoAnio',
        ];
    };

    const isRadicacionEditSession = function (recordView) {
        if (!recordView || !recordView.model) {
            return false;
        }

        if (recordView.model.isNew && recordView.model.isNew()) {
            return false;
        }

        return isPureRadicacionUser(recordView.getUser());
    };

    const isRadicarMode = function (recordView) {
        return isRadicacionEditSession(recordView);
    };

    const bootstrapRadicarMode = function (recordView) {
        if (isRadicacionEditSession(recordView)) {
            recordView._radicarMode = true;
        }
    };

    const isCasePostRadicado = function (model) {
        return RadicacionFields.isCasePostRadicado(model);
    };

    const isCaseSinRadicar = function (model) {
        return !isCasePostRadicado(model);
    };

    const prepareRadicacionEditView = function (recordView) {
        if (!isRadicacionEditSession(recordView)) {
            return;
        }

        if (typeof recordView.findPanel !== 'function') {
            return;
        }

        hideNonRadicacionPanels(recordView);
        recordView.findPanel('radicacionCaso').show();
    };

    const unlockEditableRadicacionFields = function (recordView) {
        if (!recordView) {
            return;
        }

        getEditableFields().forEach(function (field) {
            const view = typeof recordView.getFieldView === 'function'
                ? recordView.getFieldView(field)
                : null;

            if (view && typeof view.setNotReadOnly === 'function') {
                view.setNotReadOnly();
            }

            recordView.$el.find('[data-name="' + field + '"]').closest('.cell').show();
        });
    };

    const lockAllFieldViewsExcept = function (recordView, editableFields) {
        const editable = editableFields.slice();
        const fieldViews = typeof recordView.getFieldViews === 'function'
            ? recordView.getFieldViews()
            : {};

        Object.keys(fieldViews).forEach(function (field) {
            const view = fieldViews[field];

            if (!view) {
                return;
            }

            if (editable.indexOf(field) !== -1) {
                if (typeof view.setNotReadOnly === 'function') {
                    view.setNotReadOnly();
                }

                return;
            }

            if (typeof view.setReadOnly === 'function') {
                view.setReadOnly();
            }
        });
    };

    const applyRestrictedEdit = function (recordView) {
        if (!isRadicacionEditSession(recordView)) {
            return;
        }

        recordView._radicarMode = true;

        if (typeof recordView.setReadOnlyExcept === 'function') {
            recordView.setReadOnlyExcept(getEditableFields());
        }

        lockAllFieldViewsExcept(recordView, getEditableFields());
        unlockEditableRadicacionFields(recordView);
        prepareRadicacionEditView(recordView);

        recordView.$el.find(
            '[data-action="editLink"], [data-action="selectLink"], [data-action="quickCreate"]'
        ).closest('.btn, a, .input-group-btn').hide();

        recordView.$el.find('[data-action="save"], [data-action="saveAndContinueEditing"]').show();
    };

    const scheduleRestrictedEdit = function (recordView) {
        if (!isRadicacionEditSession(recordView)) {
            return;
        }

        applyRestrictedEdit(recordView);

        [100, 300, 700, 1200, 2000].forEach(function (delay) {
            window.setTimeout(function () {
                if (!recordView.isEditMode || !recordView.isEditMode()) {
                    return;
                }

                if (!isRadicacionEditSession(recordView)) {
                    return;
                }

                applyRestrictedEdit(recordView);

                if (typeof recordView.ensureRadicacionAssistant === 'function') {
                    recordView.ensureRadicacionAssistant();
                }
            }, delay);
        });
    };

    return {
        isPureRadicacionUser: isPureRadicacionUser,
        bootstrapRadicarMode: bootstrapRadicarMode,
        isRadicarMode: isRadicarMode,
        isRadicacionEditSession: isRadicacionEditSession,
        isCaseSinRadicar: isCaseSinRadicar,
        isCasePostRadicado: isCasePostRadicado,
        getEditableFields: getEditableFields,
        unlockEditableRadicacionFields: unlockEditableRadicacionFields,
        applyRestrictedEdit: applyRestrictedEdit,
        scheduleRestrictedEdit: scheduleRestrictedEdit,
        hideNonRadicacionPanels: hideNonRadicacionPanels,
        prepareRadicacionEditView: prepareRadicacionEditView,
    };
});

define('custom:helpers/inspeccion-registro-excel', [
    'custom:helpers/radicacion-fields',
], function (RadicacionFields) {

    const PANEL_NAME = 'registroExcelAlcaldia';
    const RECURSO_TEMA_FIELD = 'cRecursoTema';

    const INSPECTOR_ONLY_FIELDS = [
        'cAsunto',
        'cUltimaActuacion',
        'cProximaActuacion',
        RadicacionFields.FECHA_VENCIMIENTO_FIELD,
    ];

    const canEditInspectorFields = function (user) {
        return RadicacionFields.isInspeccionUser(user)
            || RadicacionFields.isRadicacionUser(user);
    };

    const canViewInspectorFields = function (user) {
        return canEditInspectorFields(user)
            || RadicacionFields.isAsignadorUser(user);
    };

    const canEditRecursoTema = function (user) {
        return canEditInspectorFields(user);
    };

    const togglePanel = function (recordView) {
        const user = recordView.getUser();
        const showInspectorFields = canViewInspectorFields(user);
        const editInspectorFields = canEditInspectorFields(user);
        const $panel = recordView.$el.find(
            '.panel[data-name="' + PANEL_NAME + '"], ' +
            '.record-panel[data-name="' + PANEL_NAME + '"], ' +
            '[data-name="' + PANEL_NAME + '"].panel'
        );

        if ($panel.length) {
            $panel.show();
        }

        const $recursoCell = recordView.$el.find('[data-name="' + RECURSO_TEMA_FIELD + '"]').closest('.cell');

        if ($recursoCell.length) {
            $recursoCell.show();
        }

        INSPECTOR_ONLY_FIELDS.forEach(function (field) {
            const $cell = recordView.$el.find('[data-name="' + field + '"]').closest('.cell');

            if ($cell.length) {
                $cell.toggle(showInspectorFields);
            }
        });

        if (!recordView.isEditMode || typeof recordView.isEditMode !== 'function' || !recordView.isEditMode()) {
            return;
        }

        const recursoView = recordView.getFieldView(RECURSO_TEMA_FIELD);

        if (recursoView) {
            if (canEditRecursoTema(user) && typeof recursoView.setNotReadOnly === 'function') {
                recursoView.setNotReadOnly();
            } else if (typeof recursoView.setReadOnly === 'function') {
                recursoView.setReadOnly();
            }
        }

        INSPECTOR_ONLY_FIELDS.forEach(function (field) {
            const fieldView = recordView.getFieldView(field);

            if (!fieldView) {
                return;
            }

            if (editInspectorFields && typeof fieldView.setNotReadOnly === 'function') {
                fieldView.setNotReadOnly();
            } else if (typeof fieldView.setReadOnly === 'function') {
                fieldView.setReadOnly();
            }
        });
    };

    return {
        PANEL_NAME: PANEL_NAME,
        RECURSO_TEMA_FIELD: RECURSO_TEMA_FIELD,
        INSPECTOR_ONLY_FIELDS: INSPECTOR_ONLY_FIELDS,
        canEditRecursoTema: canEditRecursoTema,
        togglePanel: togglePanel,
    };
});

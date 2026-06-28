define('custom:helpers/patrullero-edit-mode', [
    'custom:helpers/patrullero-acta',
], function (PatrulleroActa) {

    const ACTA_PANEL_FIELD = 'cPanelActaVisita';
    const isPurePatrulleroUser = PatrulleroActa.isPurePatrulleroUser;

    const hideCaseEditActions = function (recordView) {
        if (!recordView || !recordView.$el) {
            return;
        }

        const $root = recordView.getDetailActionElements
            ? recordView.getDetailActionElements()
            : recordView.$el;

        $root.find('[data-action="edit"], [data-action="delete"], [data-action="remove"]')
            .closest('.btn, .dropdown-item, li')
            .hide();
    };

    const lockCaseFieldsExceptActa = function (recordView) {
        const fieldViews = typeof recordView.getFieldViews === 'function'
            ? recordView.getFieldViews()
            : {};

        Object.keys(fieldViews).forEach(function (field) {
            const view = fieldViews[field];

            if (!view) {
                return;
            }

            if (field === ACTA_PANEL_FIELD) {
                view.readOnly = false;

                if (typeof view.setNotReadOnly === 'function') {
                    view.setNotReadOnly();
                }

                return;
            }

            if (typeof view.setReadOnly === 'function') {
                view.setReadOnly();
            }
        });

        if (typeof recordView.findPanel === 'function') {
            recordView.findPanel('actaVisita').show();
        }
    };

    const applyDetailReadOnly = function (recordView) {
        if (!recordView || !isPurePatrulleroUser(recordView.getUser())) {
            return;
        }

        lockCaseFieldsExceptActa(recordView);
        hideCaseEditActions(recordView);

        const actaField = recordView.getFieldView(ACTA_PANEL_FIELD);

        if (actaField && typeof actaField.loadActaState === 'function') {
            actaField.loadActaState();
        }
    };

    const actionImprimirActaManual = function (recordView) {
        if (!recordView || !PatrulleroActa.canPrintManualActa(recordView.getUser(), recordView.model)) {
            Espo.Ui.warning(recordView.translate('actaVisitaManualUnavailable', 'Case'));

            return;
        }

        if (!recordView.model || !recordView.model.id) {
            Espo.Ui.error(recordView.translate('Error'));

            return;
        }

        const url = recordView.getBasePath()
            + '?entryPoint=FormatoActaVisitaCaso'
            + '&id=' + encodeURIComponent(recordView.model.id)
            + '&modo=manual'
            + '&inline=1';

        Espo.Ui.notify(recordView.translate('pleaseWait', 'messages'));

        const printWindow = window.open(url, '_blank');

        if (!printWindow) {
            Espo.Ui.error(recordView.translate('actaVisitaPrintBlocked', 'Case'));
            Espo.Ui.notify(false);

            return;
        }

        window.setTimeout(function () {
            Espo.Ui.notify(false);
        }, 2000);
    };

    return {
        isPurePatrulleroUser: isPurePatrulleroUser,
        applyDetailReadOnly: applyDetailReadOnly,
        hideCaseEditActions: hideCaseEditActions,
        actionImprimirActaManual: actionImprimirActaManual,
    };
});

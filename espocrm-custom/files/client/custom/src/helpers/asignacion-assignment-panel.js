define('custom:helpers/asignacion-assignment-panel', [
    'custom:helpers/radicacion-fields',
], function (RadicacionFields) {

    const PANEL_CLASS = 'asignacion-assignment-panel-mount';

    const buildHtml = function () {
        return ''
            + '<div class="panel panel-default asignacion-assignment-panel ' + PANEL_CLASS + '" style="margin-bottom:15px;">'
            + '<div class="panel-heading"><strong>Asignación de patrullero</strong></div>'
            + '<div class="panel-body">'
            + '<div class="cell form-group" data-name="assignedUser"></div>'
            + '<div class="cell form-group hidden" data-name="cMotivoReasignacion"></div>'
            + '</div>'
            + '</div>';
    };

    const mount = function (recordView, options) {
        options = options || {};

        if (!options.force && !recordView._asignarMode) {
            return;
        }

        if (!recordView || !recordView.$el || !recordView.model) {
            return;
        }

        let $panel = recordView.$el.find('.' + PANEL_CLASS).first();

        if (!$panel.length) {
            const $host = recordView.$el.find('.middle .record-grid, .middle, form.record').first();

            if ($host.length) {
                $host.prepend(buildHtml());
            } else {
                recordView.$el.prepend(buildHtml());
            }

            $panel = recordView.$el.find('.' + PANEL_CLASS).first();
        }

        if (!$panel.length) {
            return;
        }

        $panel.show();
        recordView.$el.find('[data-name="assignedUser"]').closest('.cell, .field').show();

        const fieldView = recordView.getFieldView('assignedUser');

        if (fieldView) {
            fieldView.readOnly = false;

            if (typeof fieldView.setNotReadOnly === 'function') {
                fieldView.setNotReadOnly();
            }

            const $cell = $panel.find('[data-name="assignedUser"]').first();

            if ($cell.length && fieldView.$el && fieldView.$el.length) {
                if (!$cell.find(fieldView.$el).length) {
                    $cell.empty().append(fieldView.$el);
                }
            }

            if (fieldView.isRendered && fieldView.isRendered()) {
                fieldView.reRender();
            }

            unlockField(fieldView);

            return;
        }

        if (typeof recordView.createFieldView !== 'function') {
            return;
        }

        const $cell = $panel.find('[data-name="assignedUser"]').first();

        recordView.createFieldView('assignedUser', null, {
            el: $cell,
            mode: 'edit',
            readOnly: false,
        }, function (view) {
            if (!view) {
                return;
            }

            view.readOnly = false;
            view.render();
            unlockField(view);
        });
    };

    const unlockField = function (fieldView) {
        if (!fieldView || !fieldView.$el) {
            return;
        }

        fieldView.$el.removeClass('field-readonly hidden');
        fieldView.$el.find('input, select, textarea, button').prop('disabled', false).removeAttr('readonly');
        fieldView.$el.find(
            '[data-action="editLink"], [data-action="selectLink"], [data-action="quickCreate"]'
        ).closest('.btn, a, .input-group-btn, .link-container').show();
    };

    return {
        mount: mount,
    };
});

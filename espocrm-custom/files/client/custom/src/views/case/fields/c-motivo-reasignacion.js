define('custom:views/case/fields/c-motivo-reasignacion', [
    'views/fields/text',
    'custom:helpers/asignador-case-flow',
], function (Dep, AsignadorCaseFlow) {

    const VISIBLE_CLASS = 'alcaldia-motivo-reasignacion-visible';

    const getCell = function (view) {
        if (view.$el && view.$el.length) {
            const $cell = view.$el.closest('.cell[data-name="cMotivoReasignacion"]');

            if ($cell.length) {
                return $cell;
            }
        }

        const recordView = view.getRecordView && view.getRecordView();

        if (recordView && recordView.$el) {
            const $cell = recordView.$el.find('.cell[data-name="cMotivoReasignacion"]').first();

            if ($cell.length) {
                return $cell;
            }
        }

        return null;
    };

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this._hadAssigneeOnOpen = AsignadorCaseFlow.isReasignacionCase(this.model);

            this.listenTo(this.model, 'sync', function () {
                if (!this._hadAssigneeOnOpen) {
                    this._hadAssigneeOnOpen = AsignadorCaseFlow.isReasignacionCase(this.model);
                }

                this.manageVisibility();
            });
        },

        isReasignacion: function () {
            return this._hadAssigneeOnOpen || AsignadorCaseFlow.isReasignacionCase(this.model);
        },

        manageVisibility: function () {
            const isReasignacion = this.isReasignacion();
            const $cell = getCell(this);

            if (isReasignacion) {
                if ($cell) {
                    $cell.addClass(VISIBLE_CLASS).removeClass('hidden');
                }

                this.show();

                return;
            }

            if ($cell) {
                $cell.removeClass(VISIBLE_CLASS);
            }

            this.hide();

            if (this.mode === 'edit' || this.mode === 'detail') {
                this.model.set('cMotivoReasignacion', null, {silent: true});
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.manageVisibility();

            const self = this;

            [0, 150, 500].forEach(function (delay) {
                window.setTimeout(function () {
                    self.manageVisibility();
                }, delay);
            });
        },
    });
});

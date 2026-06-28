define('custom:views/case/fields/c-motivo-reasignacion', [
    'views/fields/text',
    'custom:helpers/post-radicacion-fields',
], function (Dep, PostRadicacionFields) {

    return Dep.extend({

        getRecordView: function () {
            let parent = typeof this.getParentView === 'function'
                ? this.getParentView()
                : null;

            while (parent) {
                if (parent.model && typeof parent.getFieldView === 'function') {
                    return parent;
                }

                parent = typeof parent.getParentView === 'function'
                    ? parent.getParentView()
                    : null;
            }

            return null;
        },

        isReadOnly: function () {
            const recordView = this.getRecordView();

            if (!recordView) {
                return Dep.prototype.isReadOnly.call(this);
            }

            const inAssignment = !!recordView._asignacionEditMode
                || !!recordView._asignarMode
                || recordView.layoutName === 'asignar'
                || !!(recordView.options && recordView.options.asignar);

            if (
                inAssignment
                && PostRadicacionFields.shouldShowMotivoReasignacion(
                    this.getUser(),
                    this.model,
                    recordView._initialAssignedUserId
                )
            ) {
                return false;
            }

            return Dep.prototype.isReadOnly.call(this);
        },
    });
});

define('custom:views/case/fields/c-motivo-reasignacion', [
    'views/fields/text',
    'custom:helpers/post-radicacion-fields',
], function (Dep, PostRadicacionFields) {

    const isAssignmentEditing = function (recordView) {
        if (!recordView) {
            return false;
        }

        return !!recordView._asignacionEditMode
            || !!recordView._asignarMode
            || recordView.layoutName === 'asignar'
            || !!(recordView.options && recordView.options.asignar);
    };

    const shouldEditMotivo = function (recordView, model, user) {
        if (!isAssignmentEditing(recordView)) {
            return false;
        }

        const initialAssignedUserId = recordView._initialAssignedUserId;

        return PostRadicacionFields.shouldShowMotivoReasignacion(user, model, initialAssignedUserId);
    };

    return Dep.extend({

        isReadOnly: function () {
            const recordView = this.getRecordView();

            if (shouldEditMotivo(recordView, this.model, this.getUser())) {
                return false;
            }

            return Dep.prototype.isReadOnly.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            const recordView = this.getRecordView();

            if (!shouldEditMotivo(recordView, this.model, this.getUser())) {
                return;
            }

            this.readOnly = false;

            if (typeof this.setNotReadOnly === 'function') {
                this.setNotReadOnly();
            }

            if (this.mode === 'detail' && typeof this.reRender === 'function') {
                this.mode = 'edit';
                this.reRender();

                return;
            }

            if (!this.$el) {
                return;
            }

            this.$el.removeClass('field-readonly hidden');
            this.$el.closest('.cell, .field').show().removeClass('hidden');
            this.$el.find('input, select, textarea, button').prop('disabled', false).removeAttr('readonly');
        },
    });
});

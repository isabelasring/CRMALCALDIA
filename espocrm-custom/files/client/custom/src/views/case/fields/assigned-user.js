define('custom:views/case/fields/assigned-user', [
    'views/fields/assigned-user',
    'custom:helpers/post-radicacion-fields',
], function (Dep, PostRadicacionFields) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.isEditMode() && this.model.isNew()) {
                this.clearAssignedUserIfHidden();
            }

            this.listenTo(this.model, 'change:assignedUserId', () => {
                if (this.isEditMode() && this.model.isNew()) {
                    this.clearAssignedUserIfHidden();
                }
            });
        },

        isReadOnly: function () {
            if (this.isInlineAsignacionField()) {
                return false;
            }

            const recordView = this.getRecordView();

            if (
                recordView
                && (
                    recordView._asignarMode
                    || recordView.layoutName === 'asignar'
                    || (recordView.options && recordView.options.asignar)
                )
            ) {
                return false;
            }

            return Dep.prototype.isReadOnly.call(this);
        },

        isInlineAsignacionField: function () {
            let parent = this.getParentView ? this.getParentView() : null;

            while (parent) {
                if (parent.name === 'cPanelAsignacionPatrullero') {
                    return true;
                }

                parent = parent.getParentView ? parent.getParentView() : null;
            }

            return false;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.isInlineAsignacionField()) {
                this.readOnly = false;

                if (typeof this.setNotReadOnly === 'function') {
                    this.setNotReadOnly();
                }

                this.$el.find(
                    '[data-action="editLink"], [data-action="selectLink"], [data-action="quickCreate"]'
                ).closest('.btn, a, .input-group-btn, .link-container').show();
            }

            const recordView = this.getRecordView();

            if (
                recordView
                && (
                    recordView._asignarMode
                    || recordView.layoutName === 'asignar'
                )
            ) {
                this.readOnly = false;

                if (typeof this.setNotReadOnly === 'function') {
                    this.setNotReadOnly();
                }

                this.$el.find(
                    '[data-action="editLink"], [data-action="selectLink"], [data-action="quickCreate"]'
                ).closest('.btn, a, .input-group-btn, .link-container').show();
            }

            if (this.isEditMode() && this.model.isNew()) {
                this.clearAssignedUserIfHidden();
            }
        },

        clearAssignedUserIfHidden: function () {
            if (PostRadicacionFields.shouldShowAsignacion(this.getUser(), this.model)) {
                return;
            }

            if (!this.model.get('assignedUserId')) {
                return;
            }

            this.model.set({
                assignedUserId: null,
                assignedUserName: null,
            }, {silent: true});
        },

        getSelectPrimaryFilterName: function () {
            if (PostRadicacionFields.isCasePostRadicado(this.model)) {
                return 'patrulleros';
            }

            return Dep.prototype.getSelectPrimaryFilterName.call(this);
        },
    });
});

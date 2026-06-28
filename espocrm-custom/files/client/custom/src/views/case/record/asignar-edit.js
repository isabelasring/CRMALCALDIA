define('custom:views/case/record/asignar-edit', [
    'views/record/edit',
    'custom:helpers/asignador-edit-mode',
    'custom:helpers/post-radicacion-fields',
    'custom:helpers/radicacion-fields',
    'custom:helpers/asignacion-assignment-panel',
], function (Dep, AsignadorEditMode, PostRadicacionFields, RadicacionFields, AsignacionAssignmentPanel) {

    return Dep.extend({

        layoutName: 'asignar',
        sideDisabled: true,
        bottomDisabled: true,
        isWide: false,

        setup: function () {
            this._asignarMode = true;
            this.scope = this.scope || this.options.scope || 'Case';
            this.entityType = this.entityType || this.options.entityType || this.scope;

            Dep.prototype.setup.call(this);

            if (!this.model) {
                return;
            }

            this._initialAssignedUserId = this.model.get('assignedUserId') || null;

            if (!PostRadicacionFields.isCasePostRadicado(this.model)) {
                Espo.Ui.warning(this.translate('asignadorCaseNotReady', 'messages', 'Case'));
                this.getRouter().navigate('#Case/view/' + this.model.id, {trigger: true});
            }

            RadicacionFields.ensureProfile(this.getUser());

            this.listenTo(this.model, 'change:assignedUserId', function () {
                this.toggleMotivoReasignacion();
                AsignadorEditMode.ensureAssignedUserEditable(this);
            });
        },

        findPanel: function (name) {
            return this.$el.find(
                '.panel[data-name="' + name + '"], ' +
                '.record-panel[data-name="' + name + '"], ' +
                '[data-name="' + name + '"].panel'
            );
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            $('body').addClass('alcaldia-asignador-asignar-page');
            this.applyAsignacionUi();

            [100, 350, 800, 1500].forEach((delay) => {
                window.setTimeout(() => {
                    if (!this.isRendered || !this.isRendered()) {
                        return;
                    }

                    this.applyAsignacionUi();
                }, delay);
            });
        },

        applyAsignacionUi: function () {
            this.$el.find('[data-name="assignedUser"], [data-name="cMotivoReasignacion"]')
                .closest('.cell, .field')
                .show()
                .removeClass('hidden');

            AsignacionAssignmentPanel.mount(this, {force: true});
            this.toggleMotivoReasignacion();
            AsignadorEditMode.applyRestrictedEdit(this);
            AsignadorEditMode.ensureAssignedUserEditable(this);
        },

        toggleMotivoReasignacion: function () {
            const showMotivo = PostRadicacionFields.shouldShowMotivoReasignacion(
                this.getUser(),
                this.model,
                this._initialAssignedUserId
            );
            const $motivoCell = this.$el.find('[data-name="cMotivoReasignacion"]').closest('.cell');

            if ($motivoCell.length) {
                $motivoCell.toggle(showMotivo);
            }

            const motivoView = this.getFieldView('cMotivoReasignacion');

            if (!motivoView) {
                return;
            }

            if (showMotivo && typeof motivoView.setNotReadOnly === 'function') {
                motivoView.setNotReadOnly();
            } else if (!showMotivo && typeof motivoView.setReadOnly === 'function') {
                motivoView.setReadOnly();
            }
        },

        setReadOnly: function () {
            // Pantalla dedicada de asignación: el helper habilita solo assignedUser.
        },

        save: function (options) {
            options = options || {};

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            return Dep.prototype.save.call(this, options).then((result) => {
                Espo.Ui.notify(false);
                Espo.Ui.success(this.translate('caseEditedSuccess', 'labels', 'Case'));
                AsignadorEditMode.cleanupAsignarPage();

                return result;
            }).catch((error) => {
                Espo.Ui.notify(false);

                if (error === 'notModified') {
                    Espo.Ui.warning(this.translate('notModified', 'messages'));

                    return Promise.reject(error);
                }

                const message = (error && (error.message || error.statusText))
                    || this.translate('Error');

                Espo.Ui.error(message);

                return Promise.reject(error);
            });
        },

        actionSave: function (data) {
            data = data || {};
            const self = this;

            return this.save(data.options).then(function () {
                window.setTimeout(function () {
                    self.getRouter().navigate('#Case/view/' + self.model.id, {trigger: true});
                }, 450);
            });
        },

        actionCancel: function () {
            AsignadorEditMode.cleanupAsignarPage();
            this.getRouter().navigate('#Case/view/' + this.model.id, {trigger: true});
        },

        exit: function (after) {
            $('body').removeClass('alcaldia-asignador-asignar-page');
            AsignadorEditMode.cleanupAsignarPage();

            return Dep.prototype.exit.call(this, after);
        },
    });
});

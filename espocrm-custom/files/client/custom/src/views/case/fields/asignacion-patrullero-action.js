define('custom:views/case/fields/asignacion-patrullero-action', [
    'views/fields/base',
    'custom:helpers/post-radicacion-fields',
    'custom:helpers/radicacion-fields',
    'custom:helpers/asignador-edit-mode',
], function (Dep, PostRadicacionFields, RadicacionFields, AsignadorEditMode) {

    return Dep.extend({

        detailTemplate: 'custom:case/fields/asignacion-patrullero-action',

        setup: function () {
            Dep.prototype.setup.call(this);

            this._initialAssignedUserId = this.model.get('assignedUserId') || null;
            this.showPanel = false;
            this.showMotivo = false;
            this._assignedUserView = null;
            this._motivoView = null;

            this.listenTo(this.model, 'change:assignedUserId change:cNumeroRadicado change:cExpediente sync', function () {
                this.refreshState();
            });

            this.refreshState();
        },

        data: function () {
            return {
                showPanel: this.showPanel,
                showMotivo: this.showMotivo,
                assignedUserLabel: this.translate('assignedUser', 'fields', 'Case'),
                motivoLabel: this.translate('cMotivoReasignacion', 'fields', 'Case'),
                saveLabel: this.translate('asignarCaso', 'labels', 'Case'),
            };
        },

        refreshState: function () {
            const user = this.getUser();
            const canAssign = PostRadicacionFields.shouldShowAsignacion(user, this.model)
                || AsignadorEditMode.isPureAsignadorUser(user)
                || RadicacionFields.canAssignCase(user);

            this.showPanel = canAssign && PostRadicacionFields.isCasePostRadicado(this.model);
            this.showMotivo = this.showPanel && PostRadicacionFields.shouldShowMotivoReasignacion(
                user,
                this.model,
                this._initialAssignedUserId
            );

            this.updatePanelVisibility(this.showPanel);
            this.reRenderIfNeeded();
        },

        reRenderIfNeeded: function () {
            if (this.isRendered()) {
                this.reRender();
            }
        },

        updatePanelVisibility: function (show) {
            const $panel = this.$el.closest(
                '.panel[data-name="gestionPosteriorRadicacion"], ' +
                '.record-panel[data-name="gestionPosteriorRadicacion"], ' +
                '[data-name="gestionPosteriorRadicacion"].panel'
            );

            if ($panel.length) {
                $panel.toggle(show);
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (!this.showPanel) {
                return;
            }

            this.mountFields();
            this.bindSave();
        },

        getHostRecordView: function () {
            let view = this;

            while (view) {
                if (typeof view.createFieldView === 'function' && view.model === this.model) {
                    return view;
                }

                view = view.getParentView ? view.getParentView() : null;
            }

            return null;
        },

        mountFields: function () {
            const recordView = this.getHostRecordView();
            const $userCell = this.$el.find('[data-role="assigned-user-cell"]');

            if (!recordView || !$userCell.length) {
                return;
            }

            const self = this;

            if (this._assignedUserView) {
                this._assignedUserView.remove();
                this._assignedUserView = null;
            }

            recordView.createFieldView('assignedUser', null, {
                el: $userCell,
                mode: 'edit',
                readOnly: false,
            }, function (view) {
                self._assignedUserView = view;
                view._asignarMode = true;
                view.render();
                self.unlockAssignedUserField(view);
            });

            if (!this.showMotivo) {
                if (this._motivoView) {
                    this._motivoView.remove();
                    this._motivoView = null;
                }

                return;
            }

            const $motivoCell = this.$el.find('[data-role="motivo-cell"]');

            if (!$motivoCell.length) {
                return;
            }

            if (this._motivoView) {
                this._motivoView.remove();
                this._motivoView = null;
            }

            recordView.createFieldView('cMotivoReasignacion', null, {
                el: $motivoCell,
                mode: 'edit',
                readOnly: false,
            }, function (view) {
                self._motivoView = view;
                view.render();
            });
        },

        unlockAssignedUserField: function (view) {
            if (!view) {
                return;
            }

            view.readOnly = false;

            if (typeof view.setNotReadOnly === 'function') {
                view.setNotReadOnly();
            }

            if (!view.$el) {
                return;
            }

            view.$el.find('input, select, textarea, button').prop('disabled', false).removeAttr('readonly');
            view.$el.find(
                '[data-action="editLink"], [data-action="selectLink"], [data-action="quickCreate"]'
            ).closest('.btn, a, .input-group-btn, .link-container').show();
        },

        bindSave: function () {
            this.$el.find('[data-action="saveAsignacion"]').off('click.asignacion').on('click.asignacion', (event) => {
                event.preventDefault();
                event.stopPropagation();
                this.saveAsignacion();
            });
        },

        syncFieldsToModel: function () {
            if (this._assignedUserView && typeof this._assignedUserView.fetch === 'function') {
                this.model.set(this._assignedUserView.fetch());
            }

            if (this._motivoView && typeof this._motivoView.fetch === 'function') {
                this.model.set(this._motivoView.fetch());
            }
        },

        saveAsignacion: function () {
            if (!PostRadicacionFields.isCasePostRadicado(this.model)) {
                Espo.Ui.warning(this.translate('asignadorCaseNotReady', 'messages', 'Case'));

                return;
            }

            this.syncFieldsToModel();

            const assignedUserId = this.model.get('assignedUserId');

            if (!assignedUserId) {
                Espo.Ui.error(this.translate('validationRequired', 'messages')
                    .replace('{field}', this.translate('assignedUser', 'fields', 'Case')));

                return;
            }

            const data = {
                assignedUserId: assignedUserId,
                assignedUserName: this.model.get('assignedUserName'),
            };

            if (this.showMotivo) {
                const motivo = String(this.model.get('cMotivoReasignacion') || '').trim();

                if (!motivo) {
                    Espo.Ui.error(this.translate('validationRequired', 'messages')
                        .replace('{field}', this.translate('cMotivoReasignacion', 'fields', 'Case')));

                    return;
                }

                data.cMotivoReasignacion = motivo;
            }

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            this.model.save(data, {patch: true}).then(() => {
                Espo.Ui.notify(false);
                Espo.Ui.success(this.translate('caseEditedSuccess', 'labels', 'Case'));
                this._initialAssignedUserId = assignedUserId;
                this.refreshState();

                const recordView = this.getHostRecordView();

                if (recordView && typeof recordView.model.fetch === 'function') {
                    recordView.model.fetch();
                }
            }).catch((error) => {
                Espo.Ui.notify(false);

                const message = (error && (error.message || error.statusText))
                    || this.translate('Error');

                Espo.Ui.error(message);
            });
        },

        remove: function () {
            if (this._assignedUserView) {
                this._assignedUserView.remove();
                this._assignedUserView = null;
            }

            if (this._motivoView) {
                this._motivoView.remove();
                this._motivoView = null;
            }

            Dep.prototype.remove.call(this);
        },
    });
});

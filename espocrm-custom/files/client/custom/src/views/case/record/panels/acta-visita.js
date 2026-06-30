define('custom:views/case/record/panels/acta-visita', [
    'views/record/panels/side',
    'custom:helpers/acta-visita-modal',
    'custom:helpers/acta-visita-case-status',
], function (Dep, ActaVisitaModal, ActaVisitaCaseStatus) {

    return Dep.extend({

        template: 'custom:case/record/panels/acta-visita',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.actaIsEditMode = false;
            this.showButton = false;

            this.listenTo(this.model, 'change:status change:assignedUserId change:cNumeroRadicado change:cExpediente', function () {
                this.loadActaState();
            });

            this.listenTo(this.model, 'sync', function () {
                this.loadActaState();
            });

            this.loadActaState();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.togglePanel();
            this.bindButton();
        },

        canManageActa: function () {
            const user = this.getUser();

            if (!user) {
                return false;
            }

            if (user.isAdmin && user.isAdmin()) {
                return true;
            }

            const acl = this.getAcl();

            return acl.check('ActaVisita', 'edit') || acl.check('ActaVisita', 'create');
        },

        loadActaState: function () {
            const user = this.getUser();

            if (!this.model.id) {
                this.actaIsEditMode = false;
                this.showButton = false;

                if (this.isRendered()) {
                    this.reRender();
                    this.togglePanel();
                    this.bindButton();
                }

                return;
            }

            const self = this;

            ActaVisitaCaseStatus.fetchActaForCase(self.model.id, user, self.model).then((acta) => {
                self.actaIsEditMode = ActaVisitaCaseStatus.isActaDiligenciada(acta);
                self.showButton = self.canManageActa();

                if (self.isRendered()) {
                    self.reRender();
                    self.togglePanel();
                    self.bindButton();
                }
            });
        },

        bindButton: function () {
            this.$el.find('[data-action="llenarActa"]').off('click.acta');

            this.$el.find('[data-action="llenarActa"]').on('click.acta', (e) => {
                e.preventDefault();
                e.stopPropagation();

                ActaVisitaModal.open(this, this.model, this.getUser(), {
                    onAfterSave: () => this.loadActaState(),
                });
            });
        },

        togglePanel: function () {
            const $panel = this.$el.closest('.panel, .record-panel');

            if (!$panel.length) {
                return;
            }

            $panel.toggle(!!this.showButton);
        },

        data: function () {
            let unavailableReason = '';

            if (!this.showButton) {
                unavailableReason = this.translate('actaVisitaPanelUnavailable', 'Case');
            }

            let helpText = this.translate('actaVisitaPanelHelp', 'Case');
            let buttonLabel = this.translate('llenarActaVisita', 'Case');

            if (this.actaIsEditMode) {
                helpText = this.translate('actaVisitaEditHelp', 'Case');
                buttonLabel = this.translate('editarActaVisita', 'Case');
            }

            return {
                showButton: !!this.showButton,
                unavailableReason: unavailableReason,
                helpText: helpText,
                buttonLabel: buttonLabel,
            };
        },
    });
});

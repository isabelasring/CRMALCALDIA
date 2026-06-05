define('custom:views/case/record/panels/acta-visita', [
    'views/record/panels/side',
    'custom:helpers/patrullero-acta',
    'custom:helpers/acta-visita-modal',
    'custom:helpers/acta-visita-case-status',
], function (Dep, PatrulleroActa, ActaVisitaModal, ActaVisitaCaseStatus) {

    return Dep.extend({

        template: 'custom:case/record/panels/acta-visita',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.isEditMode = false;

            this.listenTo(this.model, 'change:status change:assignedUserId change:cNumeroRadicado change:cExpediente', function () {
                this.loadActaState();
            });

            this.loadActaState();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.togglePanel();
            this.bindButton();
        },

        loadActaState: function () {
            if (!this.model.id) {
                this.isEditMode = false;

                if (this.isRendered()) {
                    this.reRender();
                    this.bindButton();
                }

                return;
            }

            ActaVisitaCaseStatus.fetchActaForCase(this.model.id).then((acta) => {
                this.isEditMode = ActaVisitaCaseStatus.isActaDiligenciada(acta);

                if (this.isRendered()) {
                    this.reRender();
                    this.bindButton();
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

            const user = this.getUser();
            const show = PatrulleroActa.isPatrulleroUser(user)
                || PatrulleroActa.shouldShowLlenarActaButton(user, this.model);

            $panel.toggle(show);
        },

        data: function () {
            const user = this.getUser();
            const showButton = PatrulleroActa.shouldShowLlenarActaButton(user, this.model);
            let unavailableReason = PatrulleroActa.getUnavailableReason(user, this.model);

            if (!unavailableReason) {
                unavailableReason = 'Disponible cuando el caso esté En proceso, asignado a usted, con radicado y expediente.';
            }

            return {
                showButton: showButton,
                unavailableReason: unavailableReason,
                helpText: this.isEditMode
                    ? this.translate('actaVisitaEditHelp', 'Case')
                    : this.translate('actaVisitaPanelHelp', 'Case'),
                buttonLabel: this.isEditMode
                    ? this.translate('editarActaVisita', 'Case')
                    : this.translate('llenarActaVisita', 'Case'),
            };
        },
    });
});

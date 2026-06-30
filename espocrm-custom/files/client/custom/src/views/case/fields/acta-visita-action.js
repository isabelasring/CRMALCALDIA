define('custom:views/case/fields/acta-visita-action', [
    'views/fields/base',
    'custom:helpers/acta-visita-case-status',
], function (Dep, ActaVisitaCaseStatus) {

    return Dep.extend({

        detailTemplate: 'custom:case/fields/acta-visita-action',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.showButton = false;
            this.showPrintManual = false;

            if (!this.model.id) {
                return;
            }

            this.listenTo(this.model, 'change:status change:assignedUserId sync', function () {
                this.loadActaState();
            });

            this.loadActaState();
        },

        data: function () {
            return {
                showPanel: this.showButton || this.showPrintManual,
                showLlenarActa: this.showButton,
                showPrintManual: this.showPrintManual,
                helpText: this.translate('actaVisitaPanelHelp', 'Case'),
                buttonLabelDigital: this.translate('llenarActaVisitaDigital', 'Case'),
                buttonLabelManual: this.translate('imprimirActaVisitaManual', 'Case'),
            };
        },

        loadActaState: function () {
            var self = this;

            if (!this.model.id) {
                this.showButton = false;
                this.showPrintManual = false;
                this.updatePanelVisibility(false);

                if (this.isRendered()) {
                    this.reRender();
                }

                return;
            }

            ActaVisitaCaseStatus.fetchActaForCase(this.model.id, this.getUser(), this.model, {bypassCache: true})
                .then(function (acta) {
                    var diligenciada = ActaVisitaCaseStatus.isActaDiligenciada(acta);
                    var radicado = String(self.model.get('cNumeroRadicado') || '').trim() !== '';

                    self.showButton = radicado && !diligenciada;
                    self.showPrintManual = radicado && diligenciada;
                    self.updatePanelVisibility(self.showButton || self.showPrintManual);

                    if (self.isRendered()) {
                        self.reRender();
                        self.bindButtons();
                    }
                })
                .catch(function () {
                    self.showButton = false;
                    self.showPrintManual = false;
                    self.updatePanelVisibility(false);
                });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.bindButtons();
        },

        updatePanelVisibility: function (show) {
            this.$el.closest(
                '.panel[data-name="actaVisita"], .record-panel[data-name="actaVisita"], [data-name="actaVisita"].panel'
            ).toggle(show);
        },

        bindButtons: function () {
            var self = this;

            this.$el.find('[data-action="llenarActa"]').off('click.acta').on('click.acta', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!self.model.id) {
                    return;
                }

                self.getRouter().navigate('#ActaVisita/create?caseId=' + encodeURIComponent(self.model.id), {trigger: true});
            });

            this.$el.find('[data-action="imprimirActaManual"]').off('click.actaManual').on('click.actaManual', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!self.model.id) {
                    return;
                }

                var url = self.getBasePath()
                    + '?entryPoint=FormatoActaVisitaCaso'
                    + '&id=' + encodeURIComponent(self.model.id)
                    + '&modo=manual&inline=1';

                window.open(url, '_blank');
            });
        },
    });
});

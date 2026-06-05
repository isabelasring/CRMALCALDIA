define('custom:views/acta-visita/record/panels/formato-acta-visita', [
    'views/record/panels/side',
    'custom:helpers/formato-acta-visita-access',
], function (Dep, FormatoActaVisitaAccess) {

    return Dep.extend({

        template: 'custom:acta-visita/record/panels/formato-acta-visita',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:cFormatoActaVisitaPdfId', function () {
                this.reRender();
                this.togglePanel();
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.togglePanel();
            this.bindDownloadButtons();
        },

        bindDownloadButtons: function () {
            this.$el.find('[data-action="downloadFormatoActa"]').off('click.formatoActa');

            this.$el.find('[data-action="downloadFormatoActa"]').on('click.formatoActa', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const format = $(e.currentTarget).data('format') || 'pdf';

                this.actionDownloadFormatoActa({format: format});
            });
        },

        togglePanel: function () {
            const $panel = this.$el.closest('.panel, .record-panel');

            if (!$panel.length) {
                return;
            }

            if (this.isVisible()) {
                $panel.show();
            } else {
                $panel.hide();
            }
        },

        data: function () {
            const pdfName = this.model.get('cFormatoActaVisitaPdfName');
            const canDownload = this.canDownload();

            return {
                visible: canDownload,
                hasAutoPdf: canDownload && !!this.model.get('cFormatoActaVisitaPdfId'),
                autoPdfName: pdfName || this.translate('downloadFormatoActaPdf', 'ActaVisita'),
                autoPdfUrl: canDownload && this.model.id
                    ? this.getBasePath()
                        + '?entryPoint=FormatoActaVisita'
                        + '&id=' + encodeURIComponent(this.model.id)
                        + '&format=pdf'
                    : '',
            };
        },

        canDownload: function () {
            return FormatoActaVisitaAccess.canDownloadFormatoActaVisita(this.getUser(), this.model);
        },

        isVisible: function () {
            return this.canDownload();
        },

        actionDownloadFormatoActa: function (data) {
            const format = (data && data.format) || 'pdf';

            if (!this.canDownload()) {
                Espo.Ui.warning(this.translate('formatoActaVisitaUnavailable', 'ActaVisita'));

                return;
            }

            if (!this.model.id) {
                Espo.Ui.error(this.translate('Error'));

                return;
            }

            const url = this.getBasePath()
                + '?entryPoint=FormatoActaVisita'
                + '&id=' + encodeURIComponent(this.model.id)
                + '&format=' + encodeURIComponent(format);

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            window.location.assign(url);

            setTimeout(() => {
                Espo.Ui.notify(false);
            }, 5000);
        },
    });
});

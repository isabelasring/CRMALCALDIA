define('custom:views/case/record/panels/formato-acta-visita', [
    'views/record/panels/side',
    'custom:helpers/formato-acta-visita-case-access',
], function (Dep, FormatoActaVisitaCaseAccess) {

    return Dep.extend({

        template: 'custom:case/record/panels/formato-acta-visita',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:cNumeroRadicado change:cExpediente change:assignedUserId', function () {
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
            this.$el.find('[data-action="downloadFormatoActaCaso"]').off('click.formatoActaCaso');

            this.$el.find('[data-action="downloadFormatoActaCaso"]').on('click.formatoActaCaso', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const format = $(e.currentTarget).data('format') || 'pdf';

                this.actionDownloadFormatoActaCaso({format: format});
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
            const canDownload = this.canDownload();

            return {
                visible: canDownload,
                helpText: this.translate('formatoActaVisitaCaseHelp', 'Case'),
                unavailableText: this.translate('formatoActaVisitaCaseUnavailable', 'Case'),
                wordLabel: this.translate('downloadFormatoActaWord', 'Case'),
                pdfLabel: this.translate('downloadFormatoActaPdf', 'Case'),
            };
        },

        canDownload: function () {
            return FormatoActaVisitaCaseAccess.canDownloadFormatoActaVisitaFromCase(
                this.getUser(),
                this.model
            );
        },

        isVisible: function () {
            return this.canDownload();
        },

        actionDownloadFormatoActaCaso: function (data) {
            const format = (data && data.format) || 'pdf';

            if (!this.canDownload()) {
                Espo.Ui.warning(this.translate('formatoActaVisitaCaseUnavailable', 'Case'));

                return;
            }

            if (!this.model.id) {
                Espo.Ui.error(this.translate('Error'));

                return;
            }

            const url = this.getBasePath()
                + '?entryPoint=FormatoActaVisitaCaso'
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

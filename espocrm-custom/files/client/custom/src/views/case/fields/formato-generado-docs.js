define('custom:views/case/fields/formato-generado-docs', [
    'views/fields/base',
    'custom:helpers/case-documentos',
], function (Dep, CaseDocumentos) {

    return Dep.extend({

        detailTemplate: 'custom:case/fields/formato-generado-docs',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.documentos = [];

            this.listenTo(this.model, 'change:cFormatoSolicitudPdfId change:status change:cNumeroRadicado change:cExpediente sync', function () {
                this.loadDocumentos();
            });

            this.loadDocumentos();
        },

        data: function () {
            return {
                documentos: this.documentos,
                hasDocumentos: this.documentos.length > 0,
                emptyText: this.translate('formatoGeneradoEmpty', 'labels', 'Case'),
            };
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            const $cell = this.$el.closest('.cell');

            if ($cell.length) {
                $cell.find('label.control-label').addClass('hidden');
            }
        },

        loadDocumentos: function () {
            CaseDocumentos.fetchDocumentos(
                this.model,
                this.getUser(),
                this.getBasePath()
            ).then((documentos) => {
                this.documentos = documentos.map((doc) => {
                    return {
                        key: doc.key,
                        label: this.translate(doc.labelKey, 'labels', 'Case'),
                        name: doc.name,
                        url: doc.url,
                        icon: doc.icon,
                    };
                });

                if (this.isRendered()) {
                    this.reRender();
                }
            });
        },
    });
});

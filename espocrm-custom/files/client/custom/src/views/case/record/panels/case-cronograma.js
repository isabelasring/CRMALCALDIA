define('custom:views/case/record/panels/case-cronograma', [
    'view',
    'custom:helpers/case-cronograma',
], function (Dep, CaseCronograma) {

    return Dep.extend({

        template: 'custom:case/record/panels/case-cronograma',

        setup: function () {
            this.cronogramaData = CaseCronograma.createPlaceholder(this);

            this.listenTo(this.model, 'change:status change:cFechaCaso change:cFechaVencimiento change:cNumeroRadicado change:cExpediente change:assignedUserId sync', function () {
                this.loadCronograma();
            });

            this.loadCronograma();
        },

        data: function () {
            return {
                cronograma: this.cronogramaData,
            };
        },

        loadCronograma: function () {
            CaseCronograma.fetch(this).then((data) => {
                this.cronogramaData = data;

                if (this.isRendered()) {
                    this.reRender();
                }
            });
        },
    });
});

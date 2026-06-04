define('custom:views/case/fields/status', [
    'views/fields/enum',
    'custom:helpers/inspeccion-acta',
], function (Dep, InspeccionActa) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:status', function () {
                this.reRender();
            });
        },

        getItemList: function () {
            const list = Dep.prototype.getItemList.call(this);

            if (!InspeccionActa.shouldFinalizeCaseStatus(this.getUser(), this.model)) {
                return list;
            }

            return list.filter(function (item) {
                return item === 'Visita aprobada' || item === 'Finalizado';
            });
        },
    });
});

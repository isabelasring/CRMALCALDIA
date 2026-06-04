define('custom:views/case/record/detail', [
    'views/record/detail',
    'custom:helpers/patrullero-acta',
], function (Dep, PatrulleroActa) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:assignedUserId change:status', function () {
                this.toggleActaVisitaPanel();
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.toggleActaVisitaPanel();
        },

        toggleActaVisitaPanel: function () {
            const show = PatrulleroActa.shouldShowActaVisita(this.getUser(), this.model);
            const $panel = this.$el.find('.panel[data-name="actaVisita"]');

            if (!$panel.length) {
                return;
            }

            $panel.toggle(show);
        },
    });
});

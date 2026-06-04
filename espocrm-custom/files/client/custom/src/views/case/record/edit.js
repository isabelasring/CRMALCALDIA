define('custom:views/case/record/edit', [
    'views/record/edit',
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
            this.applyPatrulleroFieldMode();
        },

        toggleActaVisitaPanel: function () {
            const show = PatrulleroActa.shouldShowActaVisita(this.getUser(), this.model);
            const $panel = this.$el.find('.panel[data-name="actaVisita"]');

            if (!$panel.length) {
                return;
            }

            $panel.toggle(show);
        },

        applyPatrulleroFieldMode: function () {
            if (!PatrulleroActa.shouldShowActaVisita(this.getUser(), this.model)) {
                return;
            }

            const actaFields = [
                'cActaFechaVisita',
                'cActaHoraVisita',
                'cActaDireccionVisita',
                'cActaNombreVisitado',
                'cActaDocumentoVisitado',
                'cActaHallazgos',
                'cActaMedidasTomadas',
                'cActaObservaciones',
            ];

            Object.keys(this.getFieldList()).forEach((field) => {
                if (actaFields.includes(field)) {
                    return;
                }

                const view = this.getFieldView(field);

                if (view && typeof view.setReadOnly === 'function') {
                    view.setReadOnly();
                }
            });
        },
    });
});

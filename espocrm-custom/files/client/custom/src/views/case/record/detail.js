define('custom:views/case/record/detail', [
    'views/record/detail',
    'custom:helpers/patrullero-acta',
    'custom:helpers/inspeccion-acta',
], function (Dep, PatrulleroActa, InspeccionActa) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change', function () {
                this.toggleActaPanels();
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.toggleActaPanels();
            this.setActaFieldsReadOnlyForReview();
        },

        findPanel: function (name) {
            return this.$el.find(
                '.panel[data-name="' + name + '"], ' +
                '.record-panel[data-name="' + name + '"], ' +
                '[data-name="' + name + '"].panel'
            );
        },

        toggleActaPanels: function () {
            const user = this.getUser();
            const model = this.model;

            const $acta = this.findPanel('actaVisita');
            const $revision = this.findPanel('actaRevision');

            const showPatrullero = PatrulleroActa.shouldShowActaVisita(user, model);
            const showForReview = InspeccionActa.shouldShowActaVisitaReadOnly(user, model);
            const showRevision = InspeccionActa.shouldShowActaRevision(user, model);

            if ($acta.length) {
                $acta.toggle(showPatrullero || showForReview);
            }

            if ($revision.length) {
                $revision.toggle(showRevision);
            }
        },

        setActaFieldsReadOnlyForReview: function () {
            if (!InspeccionActa.shouldShowActaVisitaReadOnly(this.getUser(), this.model)) {
                return;
            }

            if (InspeccionActa.shouldShowActaRevision(this.getUser(), this.model)) {
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
                'cActaEstado',
            ];

            actaFields.forEach((field) => {
                const view = this.getFieldView(field);

                if (view && typeof view.setReadOnly === 'function') {
                    view.setReadOnly();
                }
            });
        },
    });
});

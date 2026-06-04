define('custom:views/case/record/edit', [
    'views/record/edit',
    'custom:helpers/patrullero-acta',
    'custom:helpers/inspeccion-acta',
], function (Dep, PatrulleroActa, InspeccionActa) {

    return Dep.extend({

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.applyFieldModes();
        },

        applyFieldModes: function () {
            const user = this.getUser();
            const model = this.model;

            if (PatrulleroActa.shouldShowActaVisita(user, model)) {
                this.setReadOnlyExcept([
                    'cActaFechaVisita',
                    'cActaHoraVisita',
                    'cActaDireccionVisita',
                    'cActaNombreVisitado',
                    'cActaDocumentoVisitado',
                    'cActaHallazgos',
                    'cActaMedidasTomadas',
                    'cActaObservaciones',
                ]);

                return;
            }

            if (InspeccionActa.shouldShowActaRevision(user, model)) {
                this.setReadOnlyExcept([
                    'cActaVistoBueno',
                    'cActaObservacionesRevision',
                ]);

                return;
            }

            if (InspeccionActa.shouldFinalizeCaseStatus(user, model)) {
                this.setReadOnlyExcept(['status']);

                return;
            }

            if (InspeccionActa.shouldShowActoCierre(user, model)) {
                this.setReadOnlyExcept([
                    'cCierreFecha',
                    'cCierreResumen',
                    'cCierreConclusiones',
                    'cCierreMedidasAdoptadas',
                    'cCierreObservaciones',
                ]);
            }
        },

        setReadOnlyExcept: function (editableFields) {
            Object.keys(this.getFieldList()).forEach((field) => {
                if (editableFields.includes(field)) {
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

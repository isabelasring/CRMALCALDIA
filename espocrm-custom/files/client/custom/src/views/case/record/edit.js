define('custom:views/case/record/edit', [
    'views/record/edit',
    'custom:helpers/persona-tipo-fields',
    'custom:helpers/party-document-lookup',
    'custom:helpers/direccion-estructurada',
    'custom:helpers/radicacion-fields',
    'custom:helpers/inspeccion-case-flow',
    'custom:helpers/case-create-form',
], function (Dep, PersonaTipoFields, PartyDocumentLookup, DireccionEstructurada, RadicacionFields, InspeccionCaseFlow, CaseCreateForm) {

    return Dep.extend({

        setup: function () {
            CaseCreateForm.setup(this);
            InspeccionCaseFlow.setup(this);

            Dep.prototype.setup.call(this);

            if (this.model.isNew()) {
                this.isWide = true;
            }

            PersonaTipoFields.setup(this);
            PartyDocumentLookup.setup(this);
            DireccionEstructurada.setup(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            InspeccionCaseFlow.schedule(this);
            CaseCreateForm.schedule(this);
        },

        prepareModelForSave: function () {
            if (PersonaTipoFields.isInfractorDesconocido(this.model.get('cTipoPersonaPerjudicante'))) {
                PersonaTipoFields.clearInfractorFields(this);
            }

            InspeccionCaseFlow.prepareModelForSave(this);

            Dep.prototype.prepareModelForSave.apply(this, arguments);
        },
    });
});

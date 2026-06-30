define('custom:views/case/record/edit', [
    'views/record/edit',
    'custom:helpers/persona-tipo-fields',
    'custom:helpers/party-document-lookup',
    'custom:helpers/direccion-estructurada',
], function (Dep, PersonaTipoFields, PartyDocumentLookup, DireccionEstructurada) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.isNew()) {
                this.isWide = true;
            }

            PersonaTipoFields.setup(this);
            PartyDocumentLookup.setup(this);
            DireccionEstructurada.setup(this);
        },
    });
});

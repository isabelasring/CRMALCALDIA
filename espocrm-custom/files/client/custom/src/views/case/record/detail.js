define('custom:views/case/record/detail', [
    'views/record/detail',
    'custom:helpers/persona-tipo-fields',
    'custom:helpers/radicacion-case-flow',
    'custom:helpers/asignador-case-flow',
], function (Dep, PersonaTipoFields, RadicacionCaseFlow, AsignadorCaseFlow) {

    return Dep.extend({

        bottomDisabled: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            PersonaTipoFields.setup(this);
            RadicacionCaseFlow.setup(this);
            AsignadorCaseFlow.setup(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            RadicacionCaseFlow.schedule(this);
            AsignadorCaseFlow.schedule(this);
        },
    });
});

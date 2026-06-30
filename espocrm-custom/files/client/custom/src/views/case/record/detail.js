define('custom:views/case/record/detail', [
    'views/record/detail',
    'custom:helpers/persona-tipo-fields',
], function (Dep, PersonaTipoFields) {

    return Dep.extend({

        bottomDisabled: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            PersonaTipoFields.setup(this);
        },
    });
});

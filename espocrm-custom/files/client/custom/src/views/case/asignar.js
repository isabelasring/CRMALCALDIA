define('custom:views/case/asignar', [
    'custom:views/case/edit',
], function (Dep) {

    return Dep.extend({

        recordViewName: 'custom:views/case/record/asignar-edit',

        setup: function () {
            this.scope = this.options.scope || 'Case';
            this.entityType = this.options.entityType || this.scope;

            Dep.prototype.setup.call(this);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.translate('asignarCaso', 'labels', 'Case'));
        },
    });
});

define('custom:views/case/asignar', [
    'custom:views/case/edit',
], function (Dep) {

    return Dep.extend({

        recordViewName: 'custom:views/case/record/asignar-edit',

        updatePageTitle: function () {
            this.setPageTitle(this.translate('asignarCaso', 'labels', 'Case'));
        },
    });
});

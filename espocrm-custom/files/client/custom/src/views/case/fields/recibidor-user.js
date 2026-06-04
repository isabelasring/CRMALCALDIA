define('custom:views/case/fields/recibidor-user', ['views/fields/link'], function (Dep) {

    return Dep.extend({

        getSelectPrimaryFilterName: function () {
            return 'recibidores';
        },
    });
});

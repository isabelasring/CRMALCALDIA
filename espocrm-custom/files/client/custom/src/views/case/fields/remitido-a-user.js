define('custom:views/case/fields/remitido-a-user', ['views/fields/link'], function (Dep) {

    return Dep.extend({

        getSelectPrimaryFilterName: function () {
            return 'radicacion';
        },
    });
});

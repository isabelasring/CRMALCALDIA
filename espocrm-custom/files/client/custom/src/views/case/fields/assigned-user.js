define('custom:views/case/fields/assigned-user', ['views/fields/assigned-user'], function (Dep) {

    return Dep.extend({

        getSelectPrimaryFilterName: function () {
            if (this.model.get('status') === 'Radicado') {
                return 'patrulleros';
            }

            return Dep.prototype.getSelectPrimaryFilterName.call(this);
        },
    });
});

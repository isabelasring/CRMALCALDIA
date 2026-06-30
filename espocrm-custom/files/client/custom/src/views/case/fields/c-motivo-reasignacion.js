define('custom:views/case/fields/c-motivo-reasignacion', ['views/fields/text'], function (Dep) {

    return Dep.extend({

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.show();
        },
    });
});

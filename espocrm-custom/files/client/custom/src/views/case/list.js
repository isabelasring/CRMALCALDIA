define('custom:views/case/list', ['views/list'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (!this.getAcl().check(this.scope, 'create')) {
                this.menu = this.menu || {};

                var hidden = this.menu.hiddenItemList || [];

                if (hidden.indexOf('create') === -1) {
                    hidden.push('create');
                }

                this.menu.hiddenItemList = hidden;
            }
        },

        checkAccessAction: function (action) {
            if (action === 'create' && !this.getAcl().check(this.scope, 'create')) {
                return false;
            }

            return Dep.prototype.checkAccessAction
                ? Dep.prototype.checkAccessAction.call(this, action)
                : true;
        },
    });
});

define('custom:controllers/case', ['controllers/record'], function (Dep) {

    return Dep.extend({

        canCreateCase: function () {
            if (this.getUser().isAdmin()) {
                return true;
            }

            return this.getAcl().check(this.name, 'create');
        },

        redirectCreateBlocked: function () {
            var message = this.translate('caseCreateNotAllowed', 'messages', 'Case');

            if (!message || message === 'caseCreateNotAllowed') {
                message = 'Su rol no puede crear casos nuevos.';
            }

            Espo.Ui.warning(message);
            this.getRouter().navigate('#Home', {trigger: true, replace: true});
        },

        beforeCreate: function (options) {
            if (!this.canCreateCase()) {
                return;
            }

            Dep.prototype.beforeCreate.call(this, options);
        },

        actionCreate: function (options) {
            if (!this.canCreateCase()) {
                this.redirectCreateBlocked();

                return;
            }

            Dep.prototype.actionCreate.call(this, options);
        },
    });
});

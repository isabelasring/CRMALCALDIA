define('custom:views/account/record/panels/casos-asociados', ['view'], function (Dep) {

    return Dep.extend({

        template: 'custom:account/record/panels/casos-asociados',

        setup: function () {
            this.list = [];

            this.listenTo(this.model, 'sync', function () {
                this.loadCasos();
            });

            this.loadCasos();
        },

        data: function () {
            return {
                list: this.list,
            };
        },

        loadCasos: function () {
            if (!this.model.id) {
                this.list = [];

                if (this.isRendered()) {
                    this.reRender();
                }

                return;
            }

            Espo.Ajax.getRequest('Account/action/casosAsociados', {
                accountId: this.model.id,
            }).then((response) => {
                this.list = response.list || [];

                if (this.isRendered()) {
                    this.reRender();
                }
            });
        },
    });
});

define('custom:views/contact/record/panels/casos-asociados', ['view'], function (Dep) {

    return Dep.extend({

        template: 'custom:contact/record/panels/casos-asociados',

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

            Espo.Ajax.getRequest('Contact/action/casosAsociados', {
                contactId: this.model.id,
            }).then((response) => {
                this.list = response.list || [];

                if (this.isRendered()) {
                    this.reRender();
                }
            });
        },
    });
});

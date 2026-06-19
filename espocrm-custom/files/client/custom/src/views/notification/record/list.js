define('custom:views/notification/record/list', ['views/notification/record/list'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenToOnce(this.collection, 'sync', function () {
                Espo.Ajax.postRequest('Notification/action/markAllRead')
                    .then(() => {
                        this.collection.models.forEach((model) => {
                            model.set('read', true, {sync: true});
                        });
                    });
            });
        },
    });
});

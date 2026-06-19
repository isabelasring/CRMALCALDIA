define('custom:views/notification/panel', ['views/notification/panel'], function (Dep) {

    return Dep.extend({

        afterRender: function () {
            $('#navbar li.notifications-badge-container').addClass('open');
            this.$el.find('> .panel').focus();

            this.collection.fetch()
                .then(() => Espo.Ajax.postRequest('Notification/action/markAllRead'))
                .then(() => {
                    this.collection.models.forEach((model) => {
                        model.set('read', true, {sync: true});
                    });

                    this.trigger('all-read');

                    return this.createRecordView();
                })
                .then((view) => view.render());
        },
    });
});

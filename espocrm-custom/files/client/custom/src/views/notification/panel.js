define('custom:views/notification/panel', ['views/notification/panel'], function (Dep) {

    return Dep.extend({

        createRecordView: function () {
            const viewName = this.getMetadata().get(['clientDefs', 'Notification', 'recordViews', 'list'])
                || 'views/notification/record/list';
            const containerView = this.getMetadata().get(['clientDefs', 'Notification', 'fields', 'data', 'view'])
                || 'custom:views/notification/fields/container';

            return this.createView('list', viewName, {
                selector: '.list-container',
                collection: this.collection,
                showCount: false,
                listLayout: {
                    rows: [
                        [
                            {
                                name: 'data',
                                view: containerView,
                                options: {
                                    containerSelector: this.getSelector(),
                                    groupingEnabled: this.groupingEnabled,
                                },
                            },
                        ],
                    ],
                },
            });
        },

        afterRender: function () {
            var self = this;
            var $window = $(window);

            $window.off('resize.notifications-height');
            $window.on('resize.notifications-height', this.processSizing.bind(this));
            this.processSizing();

            $('#navbar li.notifications-badge-container').addClass('open');
            this.$el.find('> .panel').focus();

            Espo.Ajax.postRequest('Notification/action/markAllRead')
                .then(function () {
                    self.trigger('all-read');

                    return self.collection.fetch();
                })
                .then(function () {
                    self.collection.models.forEach(function (model) {
                        model.set('read', true, {sync: true});
                    });

                    return self.createRecordView();
                })
                .then(function (view) {
                    return view.render();
                })
                .catch(function () {
                    self.collection.fetch()
                        .then(function () {
                            return self.createRecordView();
                        })
                        .then(function (view) {
                            return view.render();
                        });
                });
        },
    });
});

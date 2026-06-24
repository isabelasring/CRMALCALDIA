define('custom:views/calendar/modals/day-events', ['views/modal'], function (Dep) {

    return Dep.extend({

        className: 'dialog dialog-record',

        template: 'custom:calendar/modals/day-events',

        events: {
            'click [data-action="open"]': function (e) {
                e.preventDefault();

                var $target = $(e.currentTarget);
                var scope = $target.data('scope');
                var recordId = $target.data('record-id');

                if (!scope || !recordId) {
                    return;
                }

                this.trigger('open-record', {
                    scope: scope,
                    recordId: recordId,
                });
            },
        },

        setup: function () {
            this.headerText = this.translate('dayEventsTitle', 'labels', 'Calendar');
            this.eventsList = this.options.events || [];

            this.buttonList = [{
                name: 'close',
                label: this.translate('Close'),
            }];
        },

        data: function () {
            var date = this.options.date || '';
            var momentDate = this.getDateTime().toMoment(date + ' 12:00:00');
            var dateLabel = momentDate.isValid()
                ? momentDate.format('dddd, D MMMM YYYY')
                : date;

            return {
                dateLabel: dateLabel,
                countLabel: this.eventsList.length + ' actividad(es)',
                items: this.eventsList.map(function (event) {
                    if (event.isMoreLink || event.scope === 'CaseMore') {
                        return null;
                    }

                    return {
                        scope: event.scope,
                        recordId: event.recordId || event.id,
                        name: event.name || '',
                        color: event.color || '#1d8a6e',
                    };
                }).filter(function (item) {
                    return item && item.scope && item.recordId;
                }),
            };
        },
    });
});

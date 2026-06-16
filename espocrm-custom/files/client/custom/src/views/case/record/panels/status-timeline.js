define('custom:views/case/record/panels/status-timeline', [
    'view',
    'custom:helpers/case-status-timeline',
], function (Dep, CaseStatusTimeline) {

    return Dep.extend({

        template: 'custom:case/record/panels/status-timeline',

        setup: function () {
            this.timelineData = CaseStatusTimeline.createPlaceholder(this);

            this.listenTo(this.model, 'change:status sync', function () {
                this.loadTimeline();
            });

            this.loadTimeline();
        },

        data: function () {
            return {
                timeline: this.timelineData,
            };
        },

        loadTimeline: function () {
            CaseStatusTimeline.fetch(this).then((data) => {
                this.timelineData = data;

                if (this.isRendered()) {
                    this.reRender();
                }
            });
        },
    });
});

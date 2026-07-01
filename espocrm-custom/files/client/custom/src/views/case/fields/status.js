define('custom:views/case/fields/status', [
    'views/fields/enum',
    'custom:helpers/case-status-colors',
], function (Dep, CaseStatusColors) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:' + this.name, function () {
                this.applyStatusStyle();
            });
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.applyStatusStyle();
        },

        applyStatusStyle: function () {
            var status = String(this.model.get(this.name) || '').trim();
            var self = this;

            this.$el.find('.label').each(function () {
                CaseStatusColors.applyToLabel($(this), status);
            });
        },
    });
});

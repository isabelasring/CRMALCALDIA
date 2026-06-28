define('custom:views/case/radicar', [
    'custom:views/case/edit',
    'custom:helpers/radicacion-edit-mode',
], function (Dep, RadicacionEditMode) {

    return Dep.extend({

        recordViewName: 'custom:views/case/record/radicar-edit',

        updatePageTitle: function () {
            this.setPageTitle(this.translate('radicarCaso', 'labels', 'Case'));
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            RadicacionEditMode.hideRadicacionTextButtons(this);

            [100, 400].forEach((delay) => {
                window.setTimeout(() => {
                    RadicacionEditMode.hideRadicacionTextButtons(this);
                }, delay);
            });
        },
    });
});

define('custom:views/case/record/panels/formato-solicitud', [
    'views/record/panels/side',
    'custom:helpers/radicacion-fields',
], function (Dep, RadicacionFields) {

    return Dep.extend({

        template: 'custom:case/record/panels/formato-solicitud',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:cNumeroRadicado change:cExpediente', function () {
                this.reRender();
            });
        },

        data: function () {
            return {
                visible: this.isVisible(),
            };
        },

        isVisible: function () {
            const user = this.getUser();

            if (!RadicacionFields.isCaseRadicado(this.model)) {
                return false;
            }

            if (user.isAdmin()) {
                return true;
            }

            const roles = user.get('rolesNames') || {};

            return Object.values(roles).includes('Inspección');
        },

        actionDownloadFormato: function (data) {
            const format = (data && data.format) || 'doc';

            if (!this.isVisible()) {
                return;
            }

            const radicado = String(this.model.get('cNumeroRadicado') || '').trim() || this.model.id;
            const url = '?entryPoint=FormatoSolicitud'
                + '&id=' + encodeURIComponent(this.model.id)
                + '&format=' + encodeURIComponent(format);

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            window.location.href = url;

            setTimeout(function () {
                Espo.Ui.notify(false);
            }, 1500);
        },
    });
});

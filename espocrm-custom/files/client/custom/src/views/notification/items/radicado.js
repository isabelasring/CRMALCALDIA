define('custom:views/notification/items/radicado', ['views/notification/items/message'], function (Dep) {

    return Dep.extend({

        template: 'custom:notification/items/radicado',

        setup: function () {
            var data = this.model.get('data') || {};

            if (!data.isAsignador && !data.isRadicado) {
                Dep.prototype.setup.call(this);
                this.template = 'notification/items/message';

                return;
            }

            this.userId = data.userId;
            this.style = data.style || 'text-muted';

            var userName = data.userName || '';
            var entityName = data.entityName || '';
            var entityType = data.entityType || 'Case';
            var entityId = data.entityId || '';
            var numero = data.numeroRadicacion || '';
            var expediente = data.expediente || '';
            var href = data.recordUrl || ('#' + entityType + '/view/' + entityId);

            var escapedName = Handlebars.Utils.escapeExpression(entityName);
            var escapedUser = Handlebars.Utils.escapeExpression(userName);
            var escapedNumero = Handlebars.Utils.escapeExpression(numero);
            var escapedExp = Handlebars.Utils.escapeExpression(expediente);

            if (data.isAsignador) {
                this.message = escapedUser
                    + ' radicó un caso para asignar: <a href="' + href + '">'
                    + escapedName + '</a>'
                    + (expediente ? ' · Expediente ' + escapedExp : '')
                    + (numero ? ' (N.º ' + escapedNumero + ')' : '');
            } else {
                this.message = escapedUser
                    + ' radicó el caso <a href="' + href + '">'
                    + escapedName + '</a>'
                    + (numero ? ' (N.º ' + escapedNumero + ')' : '')
                    + (expediente ? ' · Expediente: ' + escapedExp : '');
            }
        },

        data: function () {
            if (this.template === 'notification/items/message') {
                return Dep.prototype.data.call(this);
            }

            return {
                avatar: this.getAvatarHtml(),
                message: this.message,
                style: this.style,
            };
        },
    });
});

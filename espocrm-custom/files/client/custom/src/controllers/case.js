define('custom:controllers/case', [
    'controllers/record',
    'custom:helpers/radicacion-edit-mode',
    'custom:helpers/radicacion-fields',
    'custom:helpers/asignador-edit-mode',
    'custom:helpers/post-radicacion-fields',
], function (Dep, RadicacionEditMode, RadicacionFields, AsignadorEditMode, PostRadicacionFields) {

    return Dep.extend({

        canCreateCase: function () {
            if (this.getUser().isAdmin()) {
                return true;
            }

            return this.getAcl().check(this.name, 'create');
        },

        getCreateBlockedMessage: function () {
            var message = 'Su rol no puede crear casos nuevos.';

            if (!this.getLanguage || typeof this.getLanguage !== 'function') {
                return message;
            }

            var translated = this.getLanguage().translate('caseCreateNotAllowed', 'messages', 'Case');

            if (translated && translated !== 'caseCreateNotAllowed') {
                return translated;
            }

            return message;
        },

        redirectCreateBlocked: function () {
            Espo.Ui.warning(this.getCreateBlockedMessage());
            this.getRouter().dispatch('Home', 'index', {trigger: true});
        },

        getRadicarViewName: function () {
            return this.getMetadata().get(['clientDefs', this.name, 'views', 'radicar'])
                || 'custom:views/case/radicar';
        },

        getAsignarViewName: function () {
            return this.getMetadata().get(['clientDefs', this.name, 'views', 'asignar'])
                || 'custom:views/case/asignar';
        },

        loadAsignarView: function (id, options) {
            options = options || {};
            var self = this;

            if (!id) {
                throw new Error('Case id required for asignar.');
            }

            if (!this.getAcl().check(this.name, 'edit')) {
                this.accessDenied();

                return;
            }

            AsignadorEditMode.activateAsignarMode(id);

            var open = function () {
                if (!AsignadorEditMode.isPureAsignadorUser(self.getUser())) {
                    Espo.Ui.warning(self.translate('Access denied', 'messages'));
                    self.getRouter().navigate('#Case/view/' + id, {trigger: true});

                    return;
                }

                self.main(self.getAsignarViewName(), {
                    scope: self.name,
                    id: id,
                    returnUrl: options.returnUrl || ('#Case/view/' + id),
                    returnDispatchParams: options.returnDispatchParams,
                    isReturned: options.isReturned || (self.store && self.store.get('isReturned')),
                    asignar: true,
                });
            };

            if (RadicacionFields.isProfileLoaded()) {
                open();

                return;
            }

            RadicacionFields.ensureProfile(this.getUser()).then(open);
        },

        loadRadicarView: function (id, options) {
            options = options || {};
            var self = this;

            if (!id) {
                throw new Error('Case id required for radicar.');
            }

            if (!this.getAcl().check(this.name, 'edit')) {
                this.accessDenied();

                return;
            }

            RadicacionEditMode.activateRadicarMode(id);

            var open = function () {
                if (!RadicacionFields.canEditRadicadoCase(self.getUser())) {
                    Espo.Ui.warning(self.translate('Access denied', 'messages'));
                    self.getRouter().navigate('#Case/view/' + id, {trigger: true});

                    return;
                }

                self.main(self.getRadicarViewName(), {
                    scope: self.name,
                    id: id,
                    returnUrl: options.returnUrl || ('#Case/view/' + id),
                    returnDispatchParams: options.returnDispatchParams,
                    isReturned: options.isReturned || (self.store && self.store.get('isReturned')),
                });
            };

            if (RadicacionFields.isProfileLoaded()) {
                open();

                return;
            }

            RadicacionFields.ensureProfile(this.getUser()).then(open);
        },

        openRadicarScreen: function (id, options) {
            this.loadRadicarView(id, options);
        },

        beforeCreate: function (options) {
            if (!this.canCreateCase()) {
                return;
            }

            Dep.prototype.beforeCreate.call(this, options);
        },

        actionCreate: function (options) {
            options = options || {};

            if (!this.canCreateCase()) {
                this.redirectCreateBlocked();

                return;
            }

            Dep.prototype.actionCreate.call(this, options);
        },

        actionEdit: function (options) {
            options = options || {};
            var self = this;

            if (!options.id) {
                Dep.prototype.actionEdit.call(this, options);

                return;
            }

            var proceed = function () {
                if (RadicacionFields.canEditRadicadoCase(self.getUser())) {
                    self.loadRadicarView(options.id, options);

                    return;
                }

                if (AsignadorEditMode.isPureAsignadorUser(self.getUser())) {
                    self.loadAsignarView(options.id, options);

                    return;
                }

                Dep.prototype.actionEdit.call(self, options);
            };

            if (RadicacionFields.isProfileLoaded()) {
                proceed();

                return;
            }

            RadicacionFields.ensureProfile(this.getUser()).then(proceed);
        },

        actionRadicar: function (options) {
            options = options || {};

            if (!options.id) {
                throw new Error('Case id required for radicar.');
            }

            this.loadRadicarView(options.id, options);
        },

        actionAsignar: function (options) {
            options = options || {};

            if (!options.id) {
                throw new Error('Case id required for asignar.');
            }

            this.loadAsignarView(options.id, options);
        },
    });
});

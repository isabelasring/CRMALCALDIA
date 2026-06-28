/**
 * Flujo Asignador: redirige Case/edit → Case/asignar y asegura campo Asignado a.
 */
(function () {

    var FLOW_VERSION = 'v1';
    var PROFILE_CACHE_KEY = 'alcaldiaCaseProfileCache';

    function getApp() {
        return window.Espo && Espo.App && Espo.App.instance;
    }

    function getHash() {
        return window.location.hash || '';
    }

    function getCaseIdFromHash(prefix) {
        var re = new RegExp('^#' + prefix + '/([^/?&#]+)', 'i');
        var match = getHash().match(re);

        return match ? match[1] : null;
    }

    function isCaseEditRoute() {
        return /^#Case\/edit\//i.test(getHash());
    }

    function isCaseAsignarRoute() {
        return /^#Case\/asignar\//i.test(getHash());
    }

    function getCaseAsignarUrl(caseId) {
        return '#Case/asignar/' + caseId;
    }

    function readCachedProfile(userId) {
        try {
            var raw = sessionStorage.getItem(PROFILE_CACHE_KEY);

            if (!raw) {
                return null;
            }

            var parsed = JSON.parse(raw);

            if (!parsed || parsed.userId !== userId || !parsed.data) {
                return null;
            }

            return parsed.data;
        } catch (error) {
            return null;
        }
    }

    function fetchProfile(app, callback) {
        app = app || getApp();

        if (!app || !app.getUser) {
            callback(null);

            return;
        }

        var user = app.getUser();
        var userId = user && user.id ? user.id : null;

        if (!userId) {
            callback(null);

            return;
        }

        var cached = readCachedProfile(userId);

        if (cached) {
            callback(cached);

            return;
        }

        if (!window.Espo || !Espo.Ajax) {
            callback(null);

            return;
        }

        Espo.Ajax.getRequest('Case/action/alcaldiaProfile').then(function (data) {
            callback(data || {});
        }).catch(function () {
            callback(null);
        });
    }

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function isAsignadorOperator(profile, app) {
        if (profile && (profile.homeProfile === 'asignador' || profile.canAssignCase || profile.isAsignador)) {
            if (profile.isRadicacion || profile.homeProfile === 'radicacion') {
                return false;
            }

            return true;
        }

        app = app || getApp();

        if (!app || !app.getUser) {
            return false;
        }

        var user = app.getUser();

        if (!user || user.isAdmin()) {
            return false;
        }

        var roles = user.get && user.get('rolesNames');

        if (!roles) {
            return false;
        }

        var names = Array.isArray(roles) ? roles : Object.values(roles);

        for (var i = 0; i < names.length; i++) {
            var normalized = normalize(names[i]);

            if (normalized === 'asignador' || normalized === 'asignacion') {
                return true;
            }
        }

        return false;
    }

    function enforceAsignarRoute(app, caseId) {
        if (!caseId) {
            return;
        }

        if (isCaseAsignarRoute()) {
            document.body.classList.add('alcaldia-asignador-asignar-page');

            return;
        }

        if (!isCaseEditRoute()) {
            return;
        }

        var router = app && app.getRouter && app.getRouter();

        if (router && typeof router.dispatch === 'function') {
            router.dispatch('Case', 'asignar', {
                id: caseId,
                returnUrl: '#Case/view/' + caseId,
                asignar: true,
            });

            return;
        }

        window.location.hash = getCaseAsignarUrl(caseId);
    }

    function mountAssignmentFallback() {
        if (!isCaseAsignarRoute()) {
            return;
        }

        if (!window.Espo || !Espo.loader || typeof Espo.loader.require !== 'function') {
            return;
        }

        Espo.loader.require('custom:helpers/asignacion-assignment-panel', function (AsignacionAssignmentPanel) {
            var app = getApp();
            var mainView = app && app.getMainView && app.getMainView();

            if (!mainView) {
                return;
            }

            var recordView = null;

            if (mainView.getView && typeof mainView.getView === 'function') {
                recordView = mainView.getView('record') || mainView.getView('edit');
            }

            if (!recordView && mainView.recordView) {
                recordView = mainView.recordView;
            }

            if (!recordView || !recordView.model || !recordView.model.id) {
                return;
            }

            recordView._asignarMode = true;
            AsignacionAssignmentPanel.mount(recordView, {force: true});
        });
    }

    function handleRoute(app) {
        fetchProfile(app, function (profile) {
            if (!isAsignadorOperator(profile, app)) {
                document.body.classList.remove('alcaldia-asignador-asignar-page');

                return;
            }

            if (isCaseEditRoute() || isCaseAsignarRoute()) {
                enforceAsignarRoute(
                    app,
                    getCaseIdFromHash('Case/edit') || getCaseIdFromHash('Case/asignar')
                );
            }

            if (isCaseAsignarRoute()) {
                document.body.classList.add('alcaldia-asignador-asignar-page');
                mountAssignmentFallback();
            }
        });
    }

    function bindApp(app) {
        if (!app || app.__caseAsignacionFlowBound) {
            return;
        }

        app.__caseAsignacionFlowBound = true;
        app.__caseAsignacionFlowVersion = FLOW_VERSION;

        handleRoute(app);

        if (app.getRouter && app.getRouter()) {
            app.getRouter().on('route', function () {
                window.setTimeout(function () {
                    handleRoute(app);
                    mountAssignmentFallback();
                }, 120);
            });
        }

        window.setInterval(function () {
            if (isCaseAsignarRoute()) {
                mountAssignmentFallback();
            }
        }, 900);
    }

    function bootstrap() {
        var app = getApp();

        if (app && app.isReady && app.isReady()) {
            bindApp(app);

            return;
        }

        if (window.Espo && Espo.loader) {
            Espo.loader.require('app', function (App) {
                if (App && App.instance) {
                    bindApp(App.instance);
                }
            });
        }
    }

    bootstrap();
    document.addEventListener('DOMContentLoaded', bootstrap);
    window.addEventListener('hashchange', function () {
        window.setTimeout(bootstrap, 50);
    });

})();

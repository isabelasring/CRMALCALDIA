/**
 * Refresca el perfil Alcaldía cuando cambia el usuario en sesión
 * (evita mezclar roles de Edwin/Juan tras cambiar de cuenta sin recargar).
 */
define('custom:loader/alcaldia-profile-sync', ['custom:helpers/radicacion-fields'], function (RadicacionFields) {

    const syncProfile = function (app) {
        if (!app || !app.getUser) {
            return;
        }

        const user = app.getUser();

        if (!user || !user.id) {
            return;
        }

        RadicacionFields.syncProfileForUser(user);
    };

    const bindApp = function (app) {
        if (!app || app.__alcaldiaProfileSyncBound) {
            return;
        }

        app.__alcaldiaProfileSyncBound = true;
        syncProfile(app);

        if (app.on) {
            app.on('route', function () {
                syncProfile(app);
            });
        }
    };

    const waitForApp = function () {
        const app = window.Espo && Espo.App && Espo.App.instance;

        if (app && app.getUser) {
            bindApp(app);

            return;
        }

        window.setTimeout(waitForApp, 150);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForApp);
    } else {
        waitForApp();
    }
});

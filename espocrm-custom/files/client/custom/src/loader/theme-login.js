/**
 * Login — marca body.login-page cuando el formulario está visible (solo CSS hook).
 */
(function () {
    var LOGO_SRC = 'client/custom/res/img/logo-envigado.png';

    function applyEnvigadoLogo() {
        document.querySelectorAll('#login .logo-container img.logo').forEach(function (img) {
            if (img.dataset.envigadoLogo === '1') {
                return;
            }

            img.src = LOGO_SRC;
            img.alt = 'Alcaldía de Envigado';
            img.dataset.envigadoLogo = '1';
        });
    }

    function syncLoginPageClass() {
        if (document.querySelector('#login')) {
            document.body.classList.add('login-page');
            applyEnvigadoLogo();
        } else {
            document.body.classList.remove('login-page');
        }
    }

    function startObserver() {
        var observer = new MutationObserver(syncLoginPageClass);

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    function boot() {
        syncLoginPageClass();
        startObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

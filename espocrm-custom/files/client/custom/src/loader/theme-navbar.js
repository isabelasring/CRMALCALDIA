/**
 * Navbar — minimizador arriba, logo Envigado abajo, reflow suave.
 */
(function () {
    var observerStarted = false;
    var LOGO_SRC = 'client/custom/res/img/logo-envigado.png';

    function setupMinimizerButton() {
        var minimizer = document.querySelector('#navbar a.minimizer');
        var header = document.querySelector('#navbar .navbar-header');

        if (!minimizer) {
            return;
        }

        if (header && minimizer.parentElement !== header) {
            header.insertBefore(minimizer, header.firstChild);
        }

        minimizer.classList.remove('hidden');
        minimizer.title = document.body.classList.contains('minimized')
            ? 'Expandir menú'
            : 'Colapsar menú';
    }

    function ensureSidebarLogo() {
        var nav = document.querySelector('#navbar > .navbar');

        if (!nav) {
            return;
        }

        var brand = nav.querySelector('.crm-sidebar-brand');

        if (!brand) {
            brand = document.createElement('div');
            brand.className = 'crm-sidebar-brand';

            var img = document.createElement('img');
            img.src = LOGO_SRC;
            img.alt = 'Alcaldía de Envigado';
            brand.appendChild(img);
            nav.appendChild(brand);
        } else if (brand.parentElement !== nav) {
            nav.appendChild(brand);
        }
    }

    function triggerReflow() {
        try {
            window.dispatchEvent(new Event('resize'));
        } catch (e) {
            /* ignore */
        }
    }

    function flattenMoreMenu() {
        var tabs = document.querySelector('#navbar ul.tabs');

        if (!tabs) {
            return;
        }

        tabs.querySelectorAll('li.show-more').forEach(function (el) {
            el.remove();
        });

        var moreLi = tabs.querySelector('li.dropdown.more, li.more');

        if (!moreLi) {
            return;
        }

        var toggle = moreLi.querySelector('a.dropdown-toggle');

        if (toggle) {
            toggle.remove();
        }

        var submenu = moreLi.querySelector('.more-dropdown-menu');

        if (!submenu) {
            moreLi.classList.add('hidden');
            return;
        }

        Array.prototype.slice.call(submenu.children).forEach(function (item) {
            if (item.classList.contains('show-more') ||
                item.classList.contains('after-show-more')) {
                item.classList.remove('hidden');
            }

            tabs.insertBefore(item, moreLi);
        });

        moreLi.remove();
    }

    function syncNavbar() {
        if (!document.querySelector('#navbar .navbar-left-container')) {
            return;
        }

        flattenMoreMenu();
        setupMinimizerButton();
        ensureSidebarLogo();
    }

    function init() {
        syncNavbar();
        triggerReflow();
        setTimeout(function () {
            syncNavbar();
            triggerReflow();
        }, 600);
        setTimeout(syncNavbar, 1500);
    }

    function startObserver() {
        if (observerStarted || !document.body) {
            return;
        }

        observerStarted = true;

        var observer = new MutationObserver(function () {
            syncNavbar();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    function boot() {
        init();
        startObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

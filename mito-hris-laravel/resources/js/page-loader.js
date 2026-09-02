// ============================================================
// page-loader.js — GLOBAL PAGE NAVIGATION LOADER
//
// Thin topbar progress bar shown during full-page navigation
// (real browser navigations only: <a href>, form submits that
// perform a real page load, browser Back/Forward, reload).
//
// This module is intentionally NOT a fetch/XHR interceptor and
// does not touch any existing AJAX/table/modal/form/spinner
// loading indicators used elsewhere in the app. It only reacts
// to actual document navigation lifecycle events.
// ============================================================

(function () {
    if (window.__mitoPageLoaderInit) {
        return;
    }
    window.__mitoPageLoaderInit = true;

    var MIN_VISIBLE_MS = 150;
    var bar = null;
    var activatedAt = 0;
    var progressTimer = null;
    var finishTimer = null;
    var isActive = false;

    function ensureBar() {
        if (bar && document.body.contains(bar)) {
            return bar;
        }
        bar = document.getElementById('mito-page-loader');
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'mito-page-loader';
            bar.setAttribute('aria-hidden', 'true');
            document.body.appendChild(bar);
        }
        return bar;
    }

    function setWidth(pct) {
        var el = ensureBar();
        el.style.width = pct + '%';
    }

    function start() {
        if (isActive) {
            return;
        }
        isActive = true;
        activatedAt = Date.now();

        var el = ensureBar();
        el.classList.remove('is-finishing');
        el.style.width = '0%';
        // Force reflow so the transition from 0% is applied.
        // eslint-disable-next-line no-unused-expressions
        el.offsetHeight;
        el.classList.add('is-active');

        window.requestAnimationFrame(function () {
            setWidth(25);
        });

        clearTimeout(progressTimer);
        var pct = 25;
        progressTimer = window.setInterval(function () {
            pct += (90 - pct) * 0.1;
            if (pct >= 88) {
                pct = 88;
                clearInterval(progressTimer);
            }
            setWidth(pct);
        }, 200);
    }

    function finish() {
        if (!isActive) {
            return;
        }

        var elapsed = Date.now() - activatedAt;
        var remaining = Math.max(0, MIN_VISIBLE_MS - elapsed);

        clearTimeout(finishTimer);
        finishTimer = window.setTimeout(function () {
            clearInterval(progressTimer);
            var el = ensureBar();
            setWidth(100);
            el.classList.add('is-finishing');
            window.setTimeout(function () {
                el.classList.remove('is-active', 'is-finishing');
                el.style.width = '0%';
                isActive = false;
            }, 300);
        }, remaining);
    }

    function isModifiedClick(event) {
        return (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        );
    }

    function isExcludedLink(anchor, href) {
        if (!href) {
            return true;
        }
        if (
            href.indexOf('javascript:') === 0 ||
            href.indexOf('mailto:') === 0 ||
            href.indexOf('tel:') === 0
        ) {
            return true;
        }
        if (anchor.hasAttribute('download')) {
            return true;
        }
        if (anchor.target && anchor.target !== '' && anchor.target !== '_self') {
            return true;
        }
        if (anchor.hasAttribute('data-bs-toggle') || anchor.hasAttribute('data-bs-dismiss')) {
            return true;
        }
        if (anchor.classList.contains('disabled') || anchor.getAttribute('aria-disabled') === 'true') {
            return true;
        }

        var url;
        try {
            url = new URL(anchor.href, window.location.href);
        } catch (e) {
            return true;
        }

        if (url.origin !== window.location.origin) {
            return true;
        }

        // Same-page hash navigation (including bare "#").
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
            return true;
        }
        if (href === '#') {
            return true;
        }

        return false;
    }

    document.addEventListener(
        'click',
        function (event) {
            if (isModifiedClick(event)) {
                return;
            }

            var anchor = event.target.closest('a[href]');
            if (!anchor) {
                return;
            }

            var href = anchor.getAttribute('href');
            if (isExcludedLink(anchor, href)) {
                return;
            }

            start();
        },
        true,
    );

    document.addEventListener(
        'submit',
        function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            if (event.defaultPrevented) {
                return;
            }
            if (form.target && form.target !== '' && form.target !== '_self') {
                return;
            }
            start();
        },
        true,
    );

    window.addEventListener('beforeunload', function () {
        start();
    });

    window.addEventListener('pageshow', function (event) {
        // Handles bfcache restores on Back/Forward so the bar never
        // stays stuck visible on a restored page.
        clearInterval(progressTimer);
        clearTimeout(finishTimer);
        isActive = false;
        var el = ensureBar();
        el.classList.remove('is-active', 'is-finishing');
        el.style.width = '0%';
        void event;
    });

    document.addEventListener('DOMContentLoaded', function () {
        ensureBar();
        finish();
    });

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        ensureBar();
        finish();
    }
})();

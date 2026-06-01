(function () {
    'use strict';

    var cfg = window.MatomoCampaignLinks;
    if (!cfg || !cfg.dialogEndpoint || !cfg.cID || !cfg.token) {
        return;
    }

    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
        } else {
            document.addEventListener('DOMContentLoaded', callback);
        }
    }

    function buildDialogUrl() {
        return cfg.dialogEndpoint + '?cID=' + encodeURIComponent(cfg.cID) + '&ccm_token=' + encodeURIComponent(cfg.token || '');
    }

    function installButton() {
        var button = document.getElementById('mcl-toolbar-button') || document.querySelector('[data-mcl-button="1"]');
        if (!button) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (window.jQuery && jQuery.fn && jQuery.fn.dialog && jQuery.fn.dialog.open) {
                jQuery.fn.dialog.open({
                    href: buildDialogUrl(),
                    title: cfg.buttonLabel || 'Campaign Links',
                    width: 980,
                    height: 520,
                    modal: true
                });
                return false;
            }

            window.location.href = buildDialogUrl();
            return false;
        });
    }

    function installCopyHandler() {
        document.addEventListener('click', function (event) {
            var button = event.target.closest ? event.target.closest('.mcl-copy') : null;
            if (!button) {
                return;
            }

            event.preventDefault();
            copyToClipboard(button.getAttribute('data-url') || '', button);
        });
    }

    function copyToClipboard(text, button) {
        function done() {
            var previous = button.innerHTML;
            var previousLabel = button.getAttribute('aria-label') || 'Copy link';
            var previousTitle = button.getAttribute('title') || 'Copy link';
            button.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
            button.setAttribute('aria-label', 'Copied');
            button.setAttribute('title', 'Copied');
            button.classList.add('mcl-copied');
            setTimeout(function () {
                button.innerHTML = previous;
                button.setAttribute('aria-label', previousLabel);
                button.setAttribute('title', previousTitle);
                button.classList.remove('mcl-copied');
            }, 1200);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done);
            return;
        }

        var temp = document.createElement('textarea');
        temp.value = text;
        temp.setAttribute('readonly', 'readonly');
        temp.style.position = 'fixed';
        temp.style.left = '-9999px';
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        done();
    }

    ready(function () {
        installButton();
        installCopyHandler();
    });
})();

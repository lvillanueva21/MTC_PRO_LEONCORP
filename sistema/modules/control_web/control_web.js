(function () {
    "use strict";

    var initialized = false;

    function init($) {
        if (initialized) {
            return;
        }
        initialized = true;

        $(function () {
            var cfg = window.CONTROL_WEB || {};
            var $workspace = $('#cw-workspace');
            var $feedback = $('#cw-feedback');
            var $buttons = $('.cw-action-btn');

            function setFeedback(msg, type) {
                if (!msg) {
                    $feedback.hide().empty();
                    return;
                }
                $feedback
                    .html('<div class="alert alert-' + type + ' mb-0">' + msg + '</div>')
                    .show();
            }

            function markActive(target) {
                $buttons.removeClass('is-active');
                $('.cw-action-btn[data-target="' + target + '"]').addClass('is-active');
            }

            function loadView(target) {
                var url = target === 'menu' ? cfg.menuUrl : cfg.cabeceraUrl;
                if (!url) {
                    setFeedback('No se encontró la configuración para cargar la vista.', 'danger');
                    return;
                }

                markActive(target);
                setFeedback('', '');
                $workspace.html(
                    '<div class="card-body text-muted">' +
                    '<i class="fas fa-spinner fa-spin mr-2"></i>Cargando interfaz...' +
                    '</div>'
                );

                $workspace.load(url, function (response, status, xhr) {
                    if (status === 'error') {
                        var msg = 'No se pudo cargar la interfaz solicitada.';
                        if (xhr && xhr.status) {
                            msg += ' Código: ' + xhr.status;
                        }
                        setFeedback(msg, 'danger');
                        $workspace.html(
                            '<div class="card-body text-muted">Intenta nuevamente en unos segundos.</div>'
                        );
                    }
                });
            }

            $(document).on('click', '.cw-action-btn', function () {
                var target = String($(this).data('target') || '');
                loadView(target);
            });

            loadView('cabecera');
        });
    }

    function tryBoot() {
        if (window.jQuery) {
            init(window.jQuery);
            return true;
        }
        return false;
    }

    if (!tryBoot()) {
        var attempts = 0;
        var maxAttempts = 200; // ~5s
        var timer = setInterval(function () {
            attempts += 1;
            if (tryBoot() || attempts >= maxAttempts) {
                clearInterval(timer);
            }
        }, 25);
    }
})();
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

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function alertIcon(type) {
                if (type === 'success') { return 'fa-check-circle'; }
                if (type === 'danger') { return 'fa-exclamation-triangle'; }
                if (type === 'warning') { return 'fa-exclamation-circle'; }
                return 'fa-info-circle';
            }

            function hideAlert($container) {
                if (!$container || !$container.length) {
                    return;
                }

                var timer = $container.data('cwTimer');
                if (timer) {
                    clearTimeout(timer);
                    $container.removeData('cwTimer');
                }

                $container.hide().empty();
            }

            function showAlert($container, htmlMessage, type) {
                if (!$container || !$container.length) {
                    return;
                }

                if (!htmlMessage) {
                    hideAlert($container);
                    return;
                }

                hideAlert($container);

                var html = ''
                    + '<div class="alert alert-' + type + ' alert-dismissible fade show mb-0" role="alert">'
                    + '  <i class="fas ' + alertIcon(type) + ' mr-1"></i>'
                    +      htmlMessage
                    + '  <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar" data-cw-close-alert="1">'
                    + '      <span aria-hidden="true">&times;</span>'
                    + '  </button>'
                    + '</div>';

                $container.html(html).show();

                var timer = setTimeout(function () {
                    hideAlert($container);
                }, 5000);
                $container.data('cwTimer', timer);
            }

            function setFeedback(msg, type) {
                if (!msg) {
                    hideAlert($feedback);
                    return;
                }

                showAlert($feedback, escapeHtml(msg), type || 'info');
            }

            function markActive(target) {
                $buttons.removeClass('is-active');
                $('.cw-action-btn[data-target="' + target + '"]').addClass('is-active');
            }

            function loadView(target) {
                var url = target === 'menu' ? cfg.menuUrl : cfg.cabeceraUrl;
                if (!url) {
                    setFeedback('No se encontro la configuracion para cargar la vista.', 'danger');
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
                            msg += ' Codigo: ' + xhr.status;
                        }
                        setFeedback(msg, 'danger');
                        $workspace.html(
                            '<div class="card-body text-muted">Intenta nuevamente en unos segundos.</div>'
                        );
                    }
                });
            }

            function buildErrorList(errors) {
                if (!Array.isArray(errors) || errors.length === 0) {
                    return '';
                }

                var html = '<ul class="mb-0 pl-3">';
                errors.forEach(function (item) {
                    html += '<li>' + escapeHtml(item) + '</li>';
                });
                html += '</ul>';
                return html;
            }

            $(document).on('click', '.cw-action-btn', function () {
                var target = String($(this).data('target') || '');
                loadView(target);
            });

            $(document).on('click', '[data-cw-close-alert]', function () {
                var $container = $(this).closest('.alert').parent();
                hideAlert($container);
            });

            $(document).on('submit', '#cw-topbar-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                var $alert = $('#cw-topbar-alert');
                var action = String($form.attr('action') || '');

                if (!action) {
                    showAlert($alert, 'No se encontro la ruta para guardar.', 'danger');
                    return;
                }

                var $submit = $form.find('#cw-topbar-submit');
                var originalText = $submit.data('originalText');
                if (!originalText) {
                    originalText = $submit.text();
                    $submit.data('originalText', originalText);
                }

                $submit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');

                $.ajax({
                    url: action,
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json'
                }).done(function (res) {
                    if (res && res.ok) {
                        var successMsg = escapeHtml(res.message || 'Cambios guardados correctamente.');
                        showAlert($alert, successMsg, 'success');
                        return;
                    }

                    var failMsg = escapeHtml((res && res.message) || 'No se pudo guardar la configuracion.');
                    var errorList = buildErrorList((res && res.errors) || []);
                    showAlert($alert, failMsg + (errorList ? '<div class="mt-2">' + errorList + '</div>' : ''), 'danger');
                }).fail(function (xhr) {
                    var msg = 'No se pudo completar la solicitud.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    showAlert($alert, escapeHtml(msg), 'danger');
                }).always(function () {
                    $submit.prop('disabled', false).text(String($submit.data('originalText') || 'Guardar cambios'));
                });
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
        var maxAttempts = 200;
        var timer = setInterval(function () {
            attempts += 1;
            if (tryBoot() || attempts >= maxAttempts) {
                clearInterval(timer);
            }
        }, 25);
    }
})();

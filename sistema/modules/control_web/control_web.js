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

            function markActive(target) {
                $buttons.removeClass('is-active');
                $('.cw-action-btn[data-target="' + target + '"]').addClass('is-active');
            }

            function loadView(target) {
                var routes = {
                    cabecera: cfg.cabeceraUrl,
                    menu: cfg.menuUrl,
                    caracteristicas: cfg.caracteristicasUrl
                };
                var url = routes[target] || '';
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
                        $workspace.html('<div class="card-body text-muted">Intenta nuevamente en unos segundos.</div>');
                        return;
                    }

                    if (target === 'menu') {
                        initMenuForm();
                        return;
                    }

                    if (target === 'caracteristicas') {
                        initFeaturesForm();
                    }
                });
            }

            function submitAjaxForm(options) {
                var $form = options.form;
                var $alert = options.alert;
                var $submit = options.submit;
                var ajaxConfig = options.ajaxConfig || {};
                var defaultButtonText = options.defaultButtonText || 'Guardar cambios';
                var defaultError = options.defaultError || 'No se pudo guardar la configuracion.';
                var onSuccess = (typeof options.onSuccess === 'function') ? options.onSuccess : null;

                var action = String($form.attr('action') || '');
                if (!action) {
                    showAlert($alert, 'No se encontro la ruta para guardar.', 'danger');
                    return;
                }

                var originalText = $submit.data('originalText');
                if (!originalText) {
                    originalText = $submit.text();
                    $submit.data('originalText', originalText);
                }

                $submit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');

                var config = {
                    url: action,
                    type: 'POST',
                    dataType: 'json'
                };
                Object.keys(ajaxConfig).forEach(function (k) {
                    config[k] = ajaxConfig[k];
                });

                $.ajax(config).done(function (res) {
                    if (res && res.ok) {
                        showAlert($alert, escapeHtml(res.message || 'Cambios guardados correctamente.'), 'success');
                        if (onSuccess) {
                            onSuccess(res);
                        }
                        return;
                    }
                    var failMsg = escapeHtml((res && res.message) || defaultError);
                    var errorList = buildErrorList((res && res.errors) || []);
                    showAlert($alert, failMsg + (errorList ? '<div class="mt-2">' + errorList + '</div>' : ''), 'danger');
                }).fail(function (xhr) {
                    var msg = defaultError;
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    showAlert($alert, escapeHtml(msg), 'danger');
                }).always(function () {
                    $submit.prop('disabled', false).text(String($submit.data('originalText') || defaultButtonText));
                });
            }

            function createSubmenuRow(data) {
                var d = data || {};
                var visibleChecked = d.visible ? 'checked' : '';
                var subId = 'cw_sub_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
                var html = ''
                    + '<div class="form-row align-items-center cw-submenu-row mb-2">'
                    + '  <div class="col-md-4 mb-2 mb-md-0">'
                    + '    <input type="text" class="form-control form-control-sm cw-submenu-text" maxlength="80" placeholder="Texto submenu" value="' + escapeHtml(d.texto || '') + '">'
                    + '  </div>'
                    + '  <div class="col-md-5 mb-2 mb-md-0">'
                    + '    <input type="text" class="form-control form-control-sm cw-submenu-url" maxlength="255" placeholder="#seccion o /ruta" value="' + escapeHtml(d.url || '') + '">'
                    + '  </div>'
                    + '  <div class="col-md-2 mb-2 mb-md-0">'
                    + '    <div class="custom-control custom-checkbox">'
                    + '      <input type="checkbox" class="custom-control-input cw-submenu-visible" id="' + subId + '" ' + visibleChecked + '>'
                    + '      <label class="custom-control-label small" for="' + subId + '">Visible</label>'
                    + '    </div>'
                    + '  </div>'
                    + '  <div class="col-md-1 text-right">'
                    + '    <button type="button" class="btn btn-sm btn-outline-danger cw-menu-remove-submenu" title="Quitar submenu">'
                    + '      <i class="fas fa-times"></i>'
                    + '    </button>'
                    + '  </div>'
                    + '</div>';
                return $(html);
            }

            function createItemCard(data) {
                var d = data || {};
                var visibleChecked = d.visible ? 'checked' : '';
                var itemId = 'cw_item_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
                var html = ''
                    + '<div class="card card-outline card-light mb-3 cw-menu-item">'
                    + '  <div class="card-header py-2 d-flex justify-content-between align-items-center">'
                    + '    <div>'
                    + '      <span class="font-weight-bold cw-menu-item-title">Opcion principal</span>'
                    + '      <span class="badge badge-info ml-2 cw-menu-main-required" style="display:none;">Obligatoria</span>'
                    + '    </div>'
                    + '    <button type="button" class="btn btn-sm btn-outline-danger cw-menu-remove-item">Quitar</button>'
                    + '  </div>'
                    + '  <div class="card-body py-3">'
                    + '    <div class="form-row">'
                    + '      <div class="form-group col-md-4">'
                    + '        <label class="mb-1">Texto</label>'
                    + '        <input type="text" class="form-control form-control-sm cw-menu-text" maxlength="80" placeholder="Ejemplo: Home" value="' + escapeHtml(d.texto || '') + '">'
                    + '      </div>'
                    + '      <div class="form-group col-md-6">'
                    + '        <label class="mb-1">Enlace</label>'
                    + '        <input type="text" class="form-control form-control-sm cw-menu-url" maxlength="255" placeholder="#seccion o /ruta" value="' + escapeHtml(d.url || '') + '">'
                    + '      </div>'
                    + '      <div class="form-group col-md-2">'
                    + '        <label class="mb-1 d-block">Visible</label>'
                    + '        <div class="custom-control custom-checkbox">'
                    + '          <input type="checkbox" class="custom-control-input cw-menu-visible" id="' + itemId + '" ' + visibleChecked + '>'
                    + '          <label class="custom-control-label" for="' + itemId + '">Si</label>'
                    + '        </div>'
                    + '      </div>'
                    + '    </div>'
                    + '    <div class="border rounded p-2 bg-light">'
                    + '      <div class="d-flex justify-content-between align-items-center mb-2">'
                    + '        <span class="font-weight-bold">Submenus</span>'
                    + '        <button type="button" class="btn btn-sm btn-outline-primary cw-menu-add-submenu"><i class="fas fa-plus mr-1"></i>Agregar submenu</button>'
                    + '      </div>'
                    + '      <div class="cw-submenu-list"></div>'
                    + '      <small class="text-muted">Puedes agregar todos los submenus que necesites.</small>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>';

                var $card = $(html);
                var submenus = Array.isArray(d.submenus) ? d.submenus : [];
                submenus.forEach(function (sub) {
                    $card.find('.cw-submenu-list').append(createSubmenuRow(sub));
                });

                return $card;
            }

            function refreshMenuItems() {
                var $items = $('#cw-menu-items .cw-menu-item');
                $items.each(function (idx) {
                    var $item = $(this);
                    $item.attr('data-index', idx);
                    $item.find('.cw-menu-item-title').text('Opcion principal ' + (idx + 1));

                    var isFirst = idx === 0;
                    $item.find('.cw-menu-main-required').toggle(isFirst);

                    var $remove = $item.find('.cw-menu-remove-item');
                    if (isFirst) {
                        $remove.prop('disabled', true).addClass('disabled');
                        $item.find('.cw-menu-visible').prop('checked', true).prop('disabled', true);
                    } else {
                        $remove.prop('disabled', false).removeClass('disabled');
                        $item.find('.cw-menu-visible').prop('disabled', false);
                    }
                });

                var count = $items.length;
                $('#cw-menu-add-item').prop('disabled', count >= 6);
            }

            function collectMenuItems() {
                var items = [];
                $('#cw-menu-items .cw-menu-item').each(function (idx) {
                    var $item = $(this);
                    var row = {
                        texto: $.trim(String($item.find('.cw-menu-text').val() || '')),
                        url: $.trim(String($item.find('.cw-menu-url').val() || '')),
                        visible: $item.find('.cw-menu-visible').is(':checked') ? 1 : 0,
                        submenus: []
                    };

                    if (idx === 0) {
                        row.visible = 1;
                    }

                    $item.find('.cw-submenu-row').each(function () {
                        var $sub = $(this);
                        row.submenus.push({
                            texto: $.trim(String($sub.find('.cw-submenu-text').val() || '')),
                            url: $.trim(String($sub.find('.cw-submenu-url').val() || '')),
                            visible: $sub.find('.cw-submenu-visible').is(':checked') ? 1 : 0
                        });
                    });

                    items.push(row);
                });
                return items;
            }

            function syncMenuItemsJson() {
                var items = collectMenuItems();
                $('#cw_menu_items_json').val(JSON.stringify(items));
                return items;
            }

            function initMenuLogoPreview() {
                var $form = $('#cw-menu-form');
                var $logoInput = $('#cw_logo_archivo');
                var $removeCheck = $('#cw_eliminar_logo');
                var $img = $('#cw-logo-preview-img');
                var $fallback = $('#cw-logo-preview-fallback');
                var $alert = $('#cw-menu-alert');

                if (
                    !$form.length ||
                    !$logoInput.length ||
                    !$removeCheck.length ||
                    !$img.length ||
                    !$fallback.length
                ) {
                    return;
                }

                var currentSrc = $.trim(String($img.attr('data-current-src') || $img.attr('src') || ''));
                var objectPreviewUrl = '';

                function revokeObjectPreview() {
                    if (objectPreviewUrl && window.URL && typeof window.URL.revokeObjectURL === 'function') {
                        window.URL.revokeObjectURL(objectPreviewUrl);
                    }
                    objectPreviewUrl = '';
                }

                function showFallback() {
                    $img.attr('src', '').addClass('d-none');
                    $fallback.removeClass('d-none');
                }

                function showImage(src) {
                    if (!src) {
                        showFallback();
                        return;
                    }
                    $img.attr('src', src).removeClass('d-none');
                    $fallback.addClass('d-none');
                }

                function showCurrent() {
                    if (currentSrc) {
                        showImage(currentSrc);
                        return;
                    }
                    showFallback();
                }

                function previewFile(file) {
                    if (!file) {
                        showCurrent();
                        return;
                    }

                    var typeOk = /^image\/(png|jpeg|webp)$/i.test(String(file.type || ''));
                    var nameOk = /\.(png|jpe?g|webp)$/i.test(String(file.name || ''));
                    if (!typeOk && !nameOk) {
                        showAlert($alert, 'Formato no permitido para previsualizacion. Usa PNG, WEBP o JPEG.', 'warning');
                        $logoInput.val('');
                        showCurrent();
                        return;
                    }

                    revokeObjectPreview();
                    if (window.URL && typeof window.URL.createObjectURL === 'function') {
                        objectPreviewUrl = window.URL.createObjectURL(file);
                        showImage(objectPreviewUrl);
                        return;
                    }

                    if (window.FileReader) {
                        var reader = new FileReader();
                        reader.onload = function (ev) {
                            showImage(String((ev && ev.target && ev.target.result) || ''));
                        };
                        reader.readAsDataURL(file);
                        return;
                    }

                    showCurrent();
                }

                showCurrent();

                $logoInput.off('change.cwLogoPreview').on('change.cwLogoPreview', function () {
                    var file = (this.files && this.files[0]) ? this.files[0] : null;
                    if (!file) {
                        if ($removeCheck.is(':checked')) {
                            showFallback();
                            return;
                        }
                        showCurrent();
                        return;
                    }
                    $removeCheck.prop('checked', false);
                    previewFile(file);
                });

                $removeCheck.off('change.cwLogoPreview').on('change.cwLogoPreview', function () {
                    if ($(this).is(':checked')) {
                        revokeObjectPreview();
                        $logoInput.val('');
                        showFallback();
                        return;
                    }
                    var file = ($logoInput[0].files && $logoInput[0].files[0]) ? $logoInput[0].files[0] : null;
                    if (file) {
                        previewFile(file);
                        return;
                    }
                    showCurrent();
                });

                $form.data('cwLogoPreview', {
                    setCurrent: function (src) {
                        currentSrc = $.trim(String(src || ''));
                        $img.attr('data-current-src', currentSrc);
                        revokeObjectPreview();
                        showCurrent();
                    }
                });
            }

            function initMenuForm() {
                var $form = $('#cw-menu-form');
                if (!$form.length || $form.data('cwReady')) {
                    return;
                }
                $form.data('cwReady', 1);

                var rawSeed = $('#cw-menu-items-seed').val() || '[]';
                var seed = [];
                try {
                    seed = JSON.parse(rawSeed);
                } catch (e) {
                    seed = [];
                }
                if (!Array.isArray(seed) || seed.length === 0) {
                    seed = [{ texto: 'Home', url: '/', visible: 1, submenus: [] }];
                }

                var $container = $('#cw-menu-items');
                $container.empty();
                seed.slice(0, 6).forEach(function (item) {
                    $container.append(createItemCard(item));
                });
                refreshMenuItems();
                syncMenuItemsJson();
                initMenuLogoPreview();
            }

            function initCharCounter($field) {
                var counterId = String($field.data('cwCounter') || '');
                if (!counterId) {
                    return;
                }

                var $counter = $('#' + counterId);
                if (!$counter.length) {
                    return;
                }

                function render() {
                    var max = parseInt($field.attr('maxlength'), 10);
                    if (!isFinite(max) || max < 0) {
                        max = 0;
                    }

                    var current = String($field.val() || '');
                    var len = current.length;
                    var rest = max - len;
                    if (rest < 0) {
                        rest = 0;
                    }
                    $counter.text(rest);
                }

                $field.off('input.cwCounter change.cwCounter')
                    .on('input.cwCounter change.cwCounter', render);
                render();
            }

            function initFeaturesImagePreview($form) {
                var $imageInput = $form.find('#cw_feat_imagen_archivo');
                var $removeCheck = $form.find('#cw_feat_eliminar_imagen');
                var $image = $form.find('#cw-features-preview-img');
                var $alert = $('#cw-features-alert');

                if (!$imageInput.length || !$removeCheck.length || !$image.length) {
                    return;
                }

                var defaultSrc = $.trim(String($image.attr('data-default-src') || ''));
                var currentSrc = $.trim(String($image.attr('data-current-src') || $image.attr('src') || defaultSrc));
                if (!currentSrc) {
                    currentSrc = defaultSrc;
                }

                var objectPreviewUrl = '';

                function revokeObjectPreview() {
                    if (objectPreviewUrl && window.URL && typeof window.URL.revokeObjectURL === 'function') {
                        window.URL.revokeObjectURL(objectPreviewUrl);
                    }
                    objectPreviewUrl = '';
                }

                function showImage(src) {
                    var target = $.trim(String(src || ''));
                    if (!target) {
                        target = defaultSrc;
                    }
                    $image.attr('src', target);
                }

                function showCurrent() {
                    showImage(currentSrc || defaultSrc);
                }

                function showDefault() {
                    showImage(defaultSrc);
                }

                function previewFile(file) {
                    if (!file) {
                        showCurrent();
                        return;
                    }

                    var typeOk = /^image\/(png|jpeg|webp)$/i.test(String(file.type || ''));
                    var nameOk = /\.(png|jpe?g|webp)$/i.test(String(file.name || ''));
                    if (!typeOk && !nameOk) {
                        showAlert($alert, 'Formato no permitido para previsualizacion. Usa PNG, WEBP o JPEG.', 'warning');
                        $imageInput.val('');
                        showCurrent();
                        return;
                    }

                    revokeObjectPreview();
                    if (window.URL && typeof window.URL.createObjectURL === 'function') {
                        objectPreviewUrl = window.URL.createObjectURL(file);
                        showImage(objectPreviewUrl);
                        return;
                    }

                    if (window.FileReader) {
                        var reader = new FileReader();
                        reader.onload = function (ev) {
                            showImage(String((ev && ev.target && ev.target.result) || defaultSrc));
                        };
                        reader.readAsDataURL(file);
                        return;
                    }

                    showCurrent();
                }

                showCurrent();

                $imageInput.off('change.cwFeaturesPreview').on('change.cwFeaturesPreview', function () {
                    var file = (this.files && this.files[0]) ? this.files[0] : null;
                    if (!file) {
                        if ($removeCheck.is(':checked')) {
                            showDefault();
                            return;
                        }
                        showCurrent();
                        return;
                    }

                    $removeCheck.prop('checked', false);
                    previewFile(file);
                });

                $removeCheck.off('change.cwFeaturesPreview').on('change.cwFeaturesPreview', function () {
                    if ($(this).is(':checked')) {
                        revokeObjectPreview();
                        $imageInput.val('');
                        showDefault();
                        return;
                    }

                    var file = ($imageInput[0].files && $imageInput[0].files[0]) ? $imageInput[0].files[0] : null;
                    if (file) {
                        previewFile(file);
                        return;
                    }
                    showCurrent();
                });

                $form.data('cwFeaturesPreview', {
                    setCurrent: function (src) {
                        currentSrc = $.trim(String(src || defaultSrc));
                        if (!currentSrc) {
                            currentSrc = defaultSrc;
                        }
                        $image.attr('data-current-src', currentSrc);
                        revokeObjectPreview();
                        showCurrent();
                    }
                });
            }

            function initFeaturesForm() {
                var $form = $('#cw-features-form');
                if (!$form.length || $form.data('cwReady')) {
                    return;
                }
                $form.data('cwReady', 1);

                $form.find('[data-cw-counter]').each(function () {
                    initCharCounter($(this));
                });

                initFeaturesImagePreview($form);
            }

            $(document).on('click', '.cw-action-btn', function () {
                var target = String($(this).data('target') || '');
                loadView(target);
            });

            $(document).on('click', '[data-cw-close-alert]', function () {
                var $container = $(this).closest('.alert').parent();
                hideAlert($container);
            });

            $(document).on('click', '#cw-menu-add-item', function () {
                var $container = $('#cw-menu-items');
                var count = $container.find('.cw-menu-item').length;
                if (count >= 6) {
                    showAlert($('#cw-menu-alert'), 'Solo se permiten hasta 6 opciones principales.', 'warning');
                    return;
                }
                $container.append(createItemCard({ texto: '', url: '#', visible: 1, submenus: [] }));
                refreshMenuItems();
                syncMenuItemsJson();
            });

            $(document).on('click', '.cw-menu-remove-item', function () {
                var $item = $(this).closest('.cw-menu-item');
                if ($item.index() === 0) {
                    return;
                }
                $item.remove();
                refreshMenuItems();
                syncMenuItemsJson();
            });

            $(document).on('click', '.cw-menu-add-submenu', function () {
                var $list = $(this).closest('.cw-menu-item').find('.cw-submenu-list').first();
                $list.append(createSubmenuRow({ texto: '', url: '#', visible: 1 }));
                syncMenuItemsJson();
            });

            $(document).on('click', '.cw-menu-remove-submenu', function () {
                $(this).closest('.cw-submenu-row').remove();
                syncMenuItemsJson();
            });

            $(document).on('input change', '#cw-menu-items .cw-menu-text, #cw-menu-items .cw-menu-url, #cw-menu-items .cw-menu-visible, #cw-menu-items .cw-submenu-text, #cw-menu-items .cw-submenu-url, #cw-menu-items .cw-submenu-visible', function () {
                syncMenuItemsJson();
            });

            $(document).on('submit', '#cw-topbar-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-topbar-alert'),
                    submit: $form.find('#cw-topbar-submit'),
                    ajaxConfig: {
                        data: $form.serialize()
                    },
                    defaultButtonText: 'Guardar cambios',
                    defaultError: 'No se pudo guardar la configuracion.'
                });
            });

            $(document).on('submit', '#cw-menu-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                var items = syncMenuItemsJson();
                if (!Array.isArray(items) || items.length < 1) {
                    showAlert($('#cw-menu-alert'), 'Debes registrar minimo una opcion principal.', 'warning');
                    return;
                }
                if (items.length > 6) {
                    showAlert($('#cw-menu-alert'), 'Solo se permiten hasta 6 opciones principales.', 'warning');
                    return;
                }

                var formData = new FormData($form[0]);

                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-menu-alert'),
                    submit: $form.find('#cw-menu-submit'),
                    ajaxConfig: {
                        data: formData,
                        processData: false,
                        contentType: false
                    },
                    defaultButtonText: 'Guardar menu',
                    defaultError: 'No se pudo guardar la configuracion de menu.',
                    onSuccess: function (res) {
                        var previewApi = $form.data('cwLogoPreview');
                        if (previewApi && typeof previewApi.setCurrent === 'function') {
                            previewApi.setCurrent(String((res && res.logo_url) || ''));
                        }
                        $('#cw_logo_archivo').val('');
                        $('#cw_eliminar_logo').prop('checked', false);
                    }
                });
            });

            $(document).on('submit', '#cw-features-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                var formData = new FormData($form[0]);

                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-features-alert'),
                    submit: $form.find('#cw-features-submit'),
                    ajaxConfig: {
                        data: formData,
                        processData: false,
                        contentType: false
                    },
                    defaultButtonText: 'Guardar caracteristicas',
                    defaultError: 'No se pudo guardar la configuracion de caracteristicas.',
                    onSuccess: function (res) {
                        var previewApi = $form.data('cwFeaturesPreview');
                        if (previewApi && typeof previewApi.setCurrent === 'function') {
                            previewApi.setCurrent(String((res && res.imagen_url) || ''));
                        }
                        $('#cw_feat_imagen_archivo').val('');
                        $('#cw_feat_eliminar_imagen').prop('checked', false);
                    }
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

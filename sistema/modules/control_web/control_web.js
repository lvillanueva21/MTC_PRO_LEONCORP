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
            var fcState = {
                page: 1,
                pages: 1,
                apiUrl: '',
                statusOptions: null
            };

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
                    caracteristicas: cfg.caracteristicasUrl,
                    nosotros: cfg.nosotrosUrl,
                    contadores: cfg.contadoresUrl,
                    servicios: cfg.serviciosUrl,
                    proceso: cfg.procesoUrl,
                    banner: cfg.bannerUrl,
                    formulario_carrusel: cfg.formularioCarruselUrl
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
                        return;
                    }

                    if (target === 'nosotros') {
                        initAboutForm();
                        return;
                    }

                    if (target === 'servicios') {
                        initServicesForm();
                        return;
                    }

                    if (target === 'proceso') {
                        initProcessForm();
                        return;
                    }

                    if (target === 'banner') {
                        initBannerForm();
                        return;
                    }

                    if (target === 'formulario_carrusel') {
                        initFormularioCarruselForm();
                        return;
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

            function initBannerImagePreview($form) {
                var $imageInput = $form.find('#cw_banner_imagen_archivo');
                var $removeCheck = $form.find('#cw_banner_eliminar_imagen');
                var $image = $form.find('#cw-banner-preview-img');
                var $alert = $('#cw-banner-alert');

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

                $imageInput.off('change.cwBannerPreview').on('change.cwBannerPreview', function () {
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

                $removeCheck.off('change.cwBannerPreview').on('change.cwBannerPreview', function () {
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

                $form.data('cwBannerPreview', {
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

            function initBannerForm() {
                var $form = $('#cw-banner-form');
                if (!$form.length || $form.data('cwReady')) {
                    return;
                }
                $form.data('cwReady', 1);

                $form.find('[data-cw-counter]').each(function () {
                    initCharCounter($(this));
                });

                initBannerImagePreview($form);
            }

            function initServicesForm() {
                var $form = $('#cw-services-form');
                if (!$form.length || $form.data('cwReady')) {
                    return;
                }
                $form.data('cwReady', 1);

                $form.find('[data-cw-counter]').each(function () {
                    initCharCounter($(this));
                });
            }

            function processCounterId(prefix) {
                return 'cw_process_' + String(prefix || 'field') + '_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
            }

            function formatProcessNumber(index) {
                var n = parseInt(index, 10);
                if (!isFinite(n) || n < 0) {
                    n = 0;
                }
                var num = n + 1;
                return (num < 10 ? '0' : '') + String(num) + '.';
            }

            function createProcessItemCard(data) {
                var d = data || {};
                var titleCounterId = processCounterId('titulo');
                var textCounterId = processCounterId('texto');
                var title = $.trim(String(d.titulo || ''));
                var text = $.trim(String(d.texto || ''));

                var html = ''
                    + '<div class="card card-outline card-light cw-process-item mb-3">'
                    + '  <div class="card-header py-2 d-flex justify-content-between align-items-center">'
                    + '    <div>'
                    + '      <strong class="cw-process-item-title">Bloque</strong>'
                    + '      <span class="badge badge-secondary ml-2">Numero <span class="cw-process-number">01.</span></span>'
                    + '    </div>'
                    + '    <button type="button" class="btn btn-sm btn-outline-danger cw-process-remove-item">Quitar</button>'
                    + '  </div>'
                    + '  <div class="card-body py-3">'
                    + '    <div class="form-row">'
                    + '      <div class="form-group col-md-5">'
                    + '        <div class="d-flex justify-content-between">'
                    + '          <label class="mb-1">Titulo</label>'
                    + '          <small class="text-muted cw-char-counter"><span id="' + titleCounterId + '">40</span> restantes</small>'
                    + '        </div>'
                    + '        <input type="text" class="form-control cw-process-item-title-input" name="item_titulo[]" maxlength="40" data-cw-counter="' + titleCounterId + '" value="' + escapeHtml(title) + '" placeholder="Step 04">'
                    + '      </div>'
                    + '      <div class="form-group col-md-7">'
                    + '        <div class="d-flex justify-content-between">'
                    + '          <label class="mb-1">Descripcion</label>'
                    + '          <small class="text-muted cw-char-counter"><span id="' + textCounterId + '">150</span> restantes</small>'
                    + '        </div>'
                    + '        <textarea class="form-control cw-process-item-text-input" name="item_texto[]" rows="3" maxlength="150" data-cw-counter="' + textCounterId + '" placeholder="Short description for this process step.">' + escapeHtml(text) + '</textarea>'
                    + '      </div>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>';

                return $(html);
            }

            function refreshProcessItems() {
                var $items = $('#cw-process-items .cw-process-item');
                var total = $items.length;

                $items.each(function (idx) {
                    var $item = $(this);
                    $item.attr('data-index', idx);
                    $item.find('.cw-process-item-title').text('Bloque ' + (idx + 1));
                    var numberText = formatProcessNumber(idx);
                    $item.find('.cw-process-number').text(numberText);
                    $item.find('.cw-process-item-title-input').attr('placeholder', 'Step ' + numberText.replace('.', ''));
                    $item.find('.cw-process-item-text-input').attr('placeholder', 'Short description for this process step.');
                });

                var disableRemove = total <= 3;
                $items.find('.cw-process-remove-item')
                    .prop('disabled', disableRemove)
                    .toggleClass('disabled', disableRemove);

                $('#cw-process-add-item').prop('disabled', total >= 9);
            }

            function initProcessForm() {
                var $form = $('#cw-process-form');
                if (!$form.length || $form.data('cwReady')) {
                    return;
                }
                $form.data('cwReady', 1);

                $form.find('[data-cw-counter]').each(function () {
                    initCharCounter($(this));
                });

                refreshProcessItems();
            }

            function fcCounterId(prefix) {
                return 'cw_fc_' + String(prefix || 'field') + '_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
            }

            function fcStatusOptionsDefault() {
                return {
                    en_espera: 'En espera',
                    contactado: 'Contactado',
                    venta_cerrada: 'Venta cerrada',
                    no_cerrada: 'No cerrada',
                    no_contesto: 'No contesto'
                };
            }

            function fcBadgeClass(status) {
                if (status === 'contactado') { return 'badge-info'; }
                if (status === 'venta_cerrada') { return 'badge-success'; }
                if (status === 'no_cerrada') { return 'badge-warning'; }
                if (status === 'no_contesto') { return 'badge-secondary'; }
                return 'badge-primary';
            }

            function createFcItemCard(data) {
                var d = data || {};
                var title = $.trim(String(d.titulo || ''));
                var text = $.trim(String(d.texto || ''));
                var defaultImage = $.trim(String(d.default_image || ''));
                var currentImage = $.trim(String(d.current_image || defaultImage));
                var titleCounterId = fcCounterId('titulo');
                var textCounterId = fcCounterId('texto');
                var removeId = fcCounterId('remove');

                var html = ''
                    + '<div class="card card-outline card-light cw-fc-item mb-3" data-default-image="' + escapeHtml(defaultImage) + '">'
                    + '  <div class="card-header py-2 d-flex justify-content-between align-items-center">'
                    + '    <div>'
                    + '      <strong class="cw-fc-item-title">Elemento</strong>'
                    + '      <span class="badge badge-light ml-2 cw-fc-item-slide-label">Slide 1</span>'
                    + '    </div>'
                    + '    <button type="button" class="btn btn-sm btn-outline-danger cw-fc-remove-item">Quitar</button>'
                    + '  </div>'
                    + '  <div class="card-body py-3">'
                    + '    <input type="hidden" class="cw-fc-item-id" value="0">'
                    + '    <div class="form-row">'
                    + '      <div class="form-group col-md-5">'
                    + '        <div class="d-flex justify-content-between">'
                    + '          <label class="mb-1">Titulo</label>'
                    + '          <small class="text-muted cw-char-counter"><span id="' + titleCounterId + '">140</span> restantes</small>'
                    + '        </div>'
                    + '        <input type="text" class="form-control cw-fc-item-titulo" maxlength="140" data-cw-counter="' + titleCounterId + '" value="' + escapeHtml(title) + '" placeholder="Titulo del slide">'
                    + '      </div>'
                    + '      <div class="form-group col-md-7">'
                    + '        <div class="d-flex justify-content-between">'
                    + '          <label class="mb-1">Texto</label>'
                    + '          <small class="text-muted cw-char-counter"><span id="' + textCounterId + '">260</span> restantes</small>'
                    + '        </div>'
                    + '        <textarea class="form-control cw-fc-item-texto" rows="3" maxlength="260" data-cw-counter="' + textCounterId + '" placeholder="Texto de apoyo del slide">' + escapeHtml(text) + '</textarea>'
                    + '      </div>'
                    + '    </div>'
                    + '    <div class="form-row">'
                    + '      <div class="form-group col-md-6">'
                    + '        <label class="mb-1">Imagen</label>'
                    + '        <input type="file" class="form-control-file cw-fc-item-imagen" accept=".png,.webp,.jpg,.jpeg,image/png,image/webp,image/jpeg">'
                    + '        <small class="form-text text-muted">Categoria: <strong>img_formulario_carrusel</strong>.</small>'
                    + '        <div class="custom-control custom-checkbox mt-2">'
                    + '          <input type="hidden" class="cw-fc-item-remove-hidden" value="0">'
                    + '          <input type="checkbox" class="custom-control-input cw-fc-item-remove-check" id="' + removeId + '" value="1">'
                    + '          <label class="custom-control-label" for="' + removeId + '">Quitar imagen personalizada</label>'
                    + '        </div>'
                    + '      </div>'
                    + '      <div class="form-group col-md-6">'
                    + '        <label class="d-block mb-1">Vista previa</label>'
                    + '        <div class="cw-fc-image-preview p-2 border rounded bg-light">'
                    + '          <img class="cw-fc-item-preview-img img-fluid" src="' + escapeHtml(currentImage) + '" data-current-src="' + escapeHtml(currentImage) + '" data-default-src="' + escapeHtml(defaultImage) + '" alt="Preview carrusel">'
                    + '        </div>'
                    + '      </div>'
                    + '    </div>'
                    + '  </div>'
                    + '</div>';

                return $(html);
            }

            function renumberFcItems() {
                var $items = $('#cw-fc-carousel-items .cw-fc-item');
                var total = $items.length;

                $items.each(function (idx) {
                    var index = idx + 1;
                    var $item = $(this);
                    var removeId = 'cw_fc_remove_' + index + '_' + Date.now();

                    $item.attr('data-index', idx);
                    $item.find('.cw-fc-item-title').text('Elemento ' + index);
                    $item.find('.cw-fc-item-slide-label').text('Slide ' + index);

                    $item.find('.cw-fc-item-id').attr('name', 'item_id[' + idx + ']');
                    $item.find('.cw-fc-item-titulo').attr('name', 'item_titulo[' + idx + ']');
                    $item.find('.cw-fc-item-texto').attr('name', 'item_texto[' + idx + ']');
                    $item.find('.cw-fc-item-imagen').attr('name', 'item_imagen_archivo[' + idx + ']');

                    var $removeHidden = $item.find('.cw-fc-item-remove-hidden');
                    var $removeCheck = $item.find('.cw-fc-item-remove-check');
                    var $removeLabel = $item.find('.custom-control-label').first();

                    $removeHidden.attr('name', 'item_eliminar_imagen[' + idx + ']');
                    $removeHidden.val($removeCheck.is(':checked') ? '1' : '0');
                    $removeCheck.attr('name', 'item_eliminar_imagen[' + idx + ']');
                    $removeCheck.attr('id', removeId);
                    $removeLabel.attr('for', removeId);
                });

                var disableRemove = total <= 1;
                $items.find('.cw-fc-remove-item')
                    .prop('disabled', disableRemove)
                    .toggleClass('disabled', disableRemove);

                $('#cw-fc-carousel-add-item').prop('disabled', total >= 5);
            }

            function initFcItemPreview($item) {
                if (!$item || !$item.length) {
                    return;
                }

                var $imageInput = $item.find('.cw-fc-item-imagen');
                var $removeCheck = $item.find('.cw-fc-item-remove-check');
                var $removeHidden = $item.find('.cw-fc-item-remove-hidden');
                var $image = $item.find('.cw-fc-item-preview-img');
                var $alert = $('#cw-fc-carousel-alert');

                if (!$imageInput.length || !$removeCheck.length || !$removeHidden.length || !$image.length) {
                    return;
                }

                var defaultSrc = $.trim(String($image.attr('data-default-src') || $item.data('defaultImage') || ''));
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

                $imageInput.off('change.cwFcPreview').on('change.cwFcPreview', function () {
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
                    $removeHidden.val('0');
                    previewFile(file);
                });

                $removeCheck.off('change.cwFcPreview').on('change.cwFcPreview', function () {
                    var checked = $(this).is(':checked');
                    $removeHidden.val(checked ? '1' : '0');

                    if (checked) {
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
            }

            function refreshFcItems() {
                renumberFcItems();

                $('#cw-fc-carousel-items .cw-fc-item').each(function () {
                    var $item = $(this);

                    $item.find('[data-cw-counter]').each(function () {
                        initCharCounter($(this));
                    });

                    initFcItemPreview($item);
                });
            }

            function applyFcSavedItems(items) {
                if (!Array.isArray(items) || items.length < 1) {
                    return false;
                }

                var normalized = items.slice(0, 5);
                if (normalized.length < 1) {
                    return false;
                }

                var $container = $('#cw-fc-carousel-items');
                if (!$container.length) {
                    return false;
                }

                $container.empty();
                normalized.forEach(function (row) {
                    var id = parseInt(row && row.id, 10);
                    if (!isFinite(id) || id < 0) {
                        id = 0;
                    }

                    var title = $.trim(String((row && row.titulo) || ''));
                    var text = $.trim(String((row && row.texto) || ''));
                    var defaultImage = $.trim(String((row && row.default_image_url) || '/web/img/carousel-1.jpg'));
                    var currentImage = $.trim(String((row && row.imagen_url) || defaultImage));
                    if (!currentImage) {
                        currentImage = defaultImage;
                    }

                    var $card = createFcItemCard({
                        titulo: title,
                        texto: text,
                        default_image: defaultImage,
                        current_image: currentImage
                    });

                    $card.find('.cw-fc-item-id').val(id);
                    $container.append($card);
                });

                refreshFcItems();
                return true;
            }

            function buildFcPagination(current, pages) {
                var page = parseInt(current, 10);
                var totalPages = parseInt(pages, 10);
                if (!isFinite(page) || page < 1) {
                    page = 1;
                }
                if (!isFinite(totalPages) || totalPages < 1) {
                    totalPages = 1;
                }

                if (totalPages <= 1) {
                    return '';
                }

                var html = '';
                var prevDisabled = (page <= 1) ? ' disabled' : '';
                html += '<li class="page-item' + prevDisabled + '"><a href="#" class="page-link" data-page="' + (page - 1) + '">Anterior</a></li>';

                var start = page - 2;
                var end = page + 2;
                if (start < 1) {
                    start = 1;
                    end = Math.min(totalPages, 5);
                }
                if (end > totalPages) {
                    end = totalPages;
                    start = Math.max(1, totalPages - 4);
                }

                for (var p = start; p <= end; p += 1) {
                    var active = (p === page) ? ' active' : '';
                    html += '<li class="page-item' + active + '"><a href="#" class="page-link" data-page="' + p + '">' + p + '</a></li>';
                }

                var nextDisabled = (page >= totalPages) ? ' disabled' : '';
                html += '<li class="page-item' + nextDisabled + '"><a href="#" class="page-link" data-page="' + (page + 1) + '">Siguiente</a></li>';

                return html;
            }

            function renderFcMessages(res) {
                var rows = (res && Array.isArray(res.rows)) ? res.rows : [];
                var pagination = (res && res.pagination) ? res.pagination : {};
                var statusOptions = (res && res.status_options && typeof res.status_options === 'object')
                    ? res.status_options
                    : fcStatusOptionsDefault();
                fcState.statusOptions = statusOptions;

                var page = parseInt(pagination.page, 10);
                var perPage = parseInt(pagination.per_page, 10);
                var total = parseInt(pagination.total, 10);
                var pages = parseInt(pagination.pages, 10);
                if (!isFinite(page) || page < 1) { page = 1; }
                if (!isFinite(perPage) || perPage < 1) { perPage = 10; }
                if (!isFinite(total) || total < 0) { total = 0; }
                if (!isFinite(pages) || pages < 1) { pages = 1; }

                fcState.page = page;
                fcState.pages = pages;

                var $body = $('#cw-fc-messages-body');
                var $summary = $('#cw-fc-messages-summary');
                var $pager = $('#cw-fc-messages-pagination');

                if (!$body.length || !$summary.length || !$pager.length) {
                    return;
                }

                if (rows.length === 0) {
                    $body.html('<tr><td colspan="8" class="text-center text-muted py-3">No hay mensajes registrados.</td></tr>');
                    $summary.text('Sin mensajes registrados.');
                    $pager.html('');
                    return;
                }

                var html = '';
                rows.forEach(function (row) {
                    var id = parseInt(row.id, 10);
                    if (!isFinite(id) || id < 1) {
                        return;
                    }

                    var type = String(row.tipo_solicitante || '');
                    var typeLabel = (type === 'empresa') ? 'Empresa' : 'Persona';
                    var interested = (type === 'empresa')
                        ? $.trim(String(row.razon_social || ''))
                        : $.trim(String(row.nombres_apellidos || ''));
                    if (!interested) {
                        interested = '-';
                    }

                    var city = $.trim(String(row.ciudad_nombre || ''));
                    var school = $.trim(String(row.escuela_nombre || ''));
                    var citySchool = city;
                    if (school) {
                        citySchool = citySchool ? (citySchool + ' / ' + school) : school;
                    }
                    if (!citySchool) {
                        citySchool = '-';
                    }

                    var status = $.trim(String(row.estado || 'en_espera'));
                    var statusLabel = $.trim(String(row.estado_label || statusOptions[status] || 'En espera'));
                    var statusBadge = $.trim(String(row.estado_badge_class || fcBadgeClass(status)));
                    var dateText = $.trim(String(row.fecha_registro || '-'));
                    var documentText = $.trim(String(row.documento || ''));
                    var phone = $.trim(String(row.celular || '-'));
                    var email = $.trim(String(row.correo || ''));
                    var schedule = $.trim(String(row.horario_nombre || ''));
                    var serviceName = $.trim(String(row.servicio_nombre || '-'));

                    var optionsHtml = '';
                    Object.keys(statusOptions).forEach(function (key) {
                        var selected = (key === status) ? ' selected' : '';
                        optionsHtml += '<option value="' + escapeHtml(key) + '"' + selected + '>' + escapeHtml(statusOptions[key]) + '</option>';
                    });

                    var detailHtml = '';
                    if (documentText) {
                        detailHtml += '<div class="small text-muted">Doc: ' + escapeHtml(documentText) + '</div>';
                    }
                    if (email) {
                        detailHtml += '<div class="small text-muted">Correo: ' + escapeHtml(email) + '</div>';
                    }
                    if (schedule) {
                        detailHtml += '<div class="small text-muted">Horario: ' + escapeHtml(schedule) + '</div>';
                    }

                    html += ''
                        + '<tr data-id="' + id + '">'
                        + '  <td>' + escapeHtml(dateText) + '</td>'
                        + '  <td><span class="badge badge-light border">' + escapeHtml(typeLabel) + '</span></td>'
                        + '  <td><strong>' + escapeHtml(interested) + '</strong>' + detailHtml + '</td>'
                        + '  <td>' + escapeHtml(serviceName) + '</td>'
                        + '  <td>' + escapeHtml(citySchool) + '</td>'
                        + '  <td>' + escapeHtml(phone) + '</td>'
                        + '  <td>'
                        + '    <div class="mb-1"><span class="badge ' + escapeHtml(statusBadge) + '">' + escapeHtml(statusLabel) + '</span></div>'
                        + '    <select class="form-control form-control-sm cw-fc-status-select" data-id="' + id + '">' + optionsHtml + '</select>'
                        + '  </td>'
                        + '  <td>'
                        + '    <button type="button" class="btn btn-sm btn-outline-primary cw-fc-status-save mb-1 w-100" data-id="' + id + '">Actualizar</button>'
                        + '    <button type="button" class="btn btn-sm btn-outline-danger cw-fc-delete-message w-100" data-id="' + id + '">Eliminar</button>'
                        + '  </td>'
                        + '</tr>';
                });

                if (!html) {
                    html = '<tr><td colspan="8" class="text-center text-muted py-3">No hay mensajes registrados.</td></tr>';
                }
                $body.html(html);

                if (total > 0) {
                    var from = ((page - 1) * perPage) + 1;
                    var to = Math.min(total, from + rows.length - 1);
                    $summary.text('Mostrando ' + from + ' - ' + to + ' de ' + total + ' mensajes.');
                } else {
                    $summary.text('Sin mensajes registrados.');
                }

                $pager.html(buildFcPagination(page, pages));
            }

            function loadFcMessages(page) {
                var targetPage = parseInt(page, 10);
                if (!isFinite(targetPage) || targetPage < 1) {
                    targetPage = 1;
                }

                if (!fcState.apiUrl) {
                    var apiFromDom = $.trim(String($('#cw-fc-scope').data('cwFcApiUrl') || ''));
                    fcState.apiUrl = apiFromDom;
                }

                if (!fcState.apiUrl) {
                    showAlert($('#cw-fc-messages-alert'), 'No se encontro la ruta para cargar mensajes.', 'danger');
                    return;
                }

                $('#cw-fc-messages-body').html('<tr><td colspan="8" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando mensajes...</td></tr>');

                $.ajax({
                    url: fcState.apiUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'list',
                        page: targetPage
                    }
                }).done(function (res) {
                    if (res && res.ok) {
                        renderFcMessages(res);
                        return;
                    }

                    $('#cw-fc-messages-body').html('<tr><td colspan="8" class="text-center text-muted py-3">No se pudo cargar el listado.</td></tr>');
                    showAlert($('#cw-fc-messages-alert'), escapeHtml((res && res.message) || 'No se pudo cargar el listado de mensajes.'), 'danger');
                }).fail(function () {
                    $('#cw-fc-messages-body').html('<tr><td colspan="8" class="text-center text-muted py-3">No se pudo cargar el listado.</td></tr>');
                    showAlert($('#cw-fc-messages-alert'), 'No se pudo cargar el listado de mensajes.', 'danger');
                });
            }

            function initFormularioCarruselForm() {
                var $scope = $('#cw-fc-scope');
                if (!$scope.length || $scope.data('cwReady')) {
                    return;
                }
                $scope.data('cwReady', 1);

                fcState.page = 1;
                fcState.pages = 1;
                fcState.statusOptions = null;
                fcState.apiUrl = $.trim(String($scope.data('cwFcApiUrl') || ''));

                refreshFcItems();
                loadFcMessages(1);
            }

            function initAboutImagePreview($form, config) {
                var opts = config || {};
                var key = String(opts.key || '');
                var inputSelector = String(opts.input || '');
                var removeSelector = String(opts.remove || '');
                var imageSelector = String(opts.img || '');

                if (!inputSelector || !removeSelector || !imageSelector) {
                    return null;
                }

                var $imageInput = $form.find(inputSelector);
                var $removeCheck = $form.find(removeSelector);
                var $image = $form.find(imageSelector);
                var $alert = $('#cw-about-alert');

                if (!$imageInput.length || !$removeCheck.length || !$image.length) {
                    return null;
                }

                var defaultSrc = $.trim(String($image.attr('data-default-src') || ''));
                var currentSrc = $.trim(String($image.attr('data-current-src') || $image.attr('src') || defaultSrc));
                if (!currentSrc) {
                    currentSrc = defaultSrc;
                }

                var objectPreviewUrl = '';
                var eventNs = '.cwAboutPreview' + key;

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

                $imageInput.off('change' + eventNs).on('change' + eventNs, function () {
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

                $removeCheck.off('change' + eventNs).on('change' + eventNs, function () {
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

                return {
                    setCurrent: function (src) {
                        currentSrc = $.trim(String(src || defaultSrc));
                        if (!currentSrc) {
                            currentSrc = defaultSrc;
                        }
                        $image.attr('data-current-src', currentSrc);
                        revokeObjectPreview();
                        showCurrent();
                    }
                };
            }

            function initAboutForm() {
                var $form = $('#cw-about-form');
                if (!$form.length || $form.data('cwReady')) {
                    return;
                }
                $form.data('cwReady', 1);

                $form.find('[data-cw-counter]').each(function () {
                    initCharCounter($(this));
                });

                var previews = {
                    icon1: initAboutImagePreview($form, {
                        key: 'Icon1',
                        input: '#cw_about_icono_archivo_1',
                        remove: '#cw_about_eliminar_icono_1',
                        img: '#cw-about-preview-icon-1'
                    }),
                    icon2: initAboutImagePreview($form, {
                        key: 'Icon2',
                        input: '#cw_about_icono_archivo_2',
                        remove: '#cw_about_eliminar_icono_2',
                        img: '#cw-about-preview-icon-2'
                    }),
                    fundador: initAboutImagePreview($form, {
                        key: 'Fundador',
                        input: '#cw_about_imagen_fundador_archivo',
                        remove: '#cw_about_eliminar_imagen_fundador',
                        img: '#cw-about-preview-fundador'
                    }),
                    principal: initAboutImagePreview($form, {
                        key: 'Principal',
                        input: '#cw_about_imagen_principal_archivo',
                        remove: '#cw_about_eliminar_imagen_principal',
                        img: '#cw-about-preview-principal'
                    }),
                    secundaria: initAboutImagePreview($form, {
                        key: 'Secundaria',
                        input: '#cw_about_imagen_secundaria_archivo',
                        remove: '#cw_about_eliminar_imagen_secundaria',
                        img: '#cw-about-preview-secundaria'
                    })
                };

                $form.data('cwAboutPreviews', previews);
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

            $(document).on('click', '#cw-process-add-item', function () {
                var $container = $('#cw-process-items');
                var count = $container.find('.cw-process-item').length;
                if (count >= 9) {
                    showAlert($('#cw-process-alert'), 'Solo se permiten hasta 9 bloques.', 'warning');
                    return;
                }

                var $card = createProcessItemCard({ titulo: '', texto: '' });
                $container.append($card);
                $card.find('[data-cw-counter]').each(function () {
                    initCharCounter($(this));
                });
                refreshProcessItems();
            });

            $(document).on('click', '#cw-process-items .cw-process-remove-item', function () {
                var $container = $('#cw-process-items');
                var count = $container.find('.cw-process-item').length;
                if (count <= 3) {
                    showAlert($('#cw-process-alert'), 'Debes mantener al menos 3 bloques.', 'warning');
                    return;
                }

                $(this).closest('.cw-process-item').remove();
                refreshProcessItems();
            });

            $(document).on('click', '#cw-fc-carousel-add-item', function () {
                var $container = $('#cw-fc-carousel-items');
                var count = $container.find('.cw-fc-item').length;
                if (count >= 5) {
                    showAlert($('#cw-fc-carousel-alert'), 'Solo se permiten hasta 5 elementos en el carrusel.', 'warning');
                    return;
                }

                var defaultImage = '';
                var $sourceImage = $container.find('.cw-fc-item-preview-img').first();
                if ($sourceImage.length) {
                    defaultImage = $.trim(String($sourceImage.attr('data-default-src') || ''));
                }
                if (!defaultImage) {
                    defaultImage = '/web/img/carousel-1.jpg';
                }

                var $card = createFcItemCard({
                    titulo: '',
                    texto: '',
                    default_image: defaultImage,
                    current_image: defaultImage
                });
                $container.append($card);
                refreshFcItems();
            });

            $(document).on('click', '#cw-fc-carousel-items .cw-fc-remove-item', function () {
                var $container = $('#cw-fc-carousel-items');
                var count = $container.find('.cw-fc-item').length;
                if (count <= 1) {
                    showAlert($('#cw-fc-carousel-alert'), 'Debes mantener al menos 1 elemento en el carrusel.', 'warning');
                    return;
                }

                $(this).closest('.cw-fc-item').remove();
                refreshFcItems();
            });

            $(document).on('click', '#cw-fc-messages-pagination [data-page]', function (e) {
                e.preventDefault();

                var $item = $(this).closest('.page-item');
                if ($item.hasClass('disabled') || $item.hasClass('active')) {
                    return;
                }

                var page = parseInt($(this).data('page'), 10);
                if (!isFinite(page) || page < 1) {
                    return;
                }

                loadFcMessages(page);
            });

            $(document).on('click', '#cw-fc-messages-body .cw-fc-status-save', function () {
                var $btn = $(this);
                var id = parseInt($btn.data('id'), 10);
                if (!isFinite(id) || id < 1) {
                    showAlert($('#cw-fc-messages-alert'), 'Mensaje invalido.', 'danger');
                    return;
                }

                if (!fcState.apiUrl) {
                    showAlert($('#cw-fc-messages-alert'), 'No se encontro la ruta para actualizar mensajes.', 'danger');
                    return;
                }

                var $row = $btn.closest('tr');
                var status = $.trim(String($row.find('.cw-fc-status-select').val() || ''));
                if (!status) {
                    showAlert($('#cw-fc-messages-alert'), 'Selecciona un estado valido.', 'warning');
                    return;
                }

                var original = $btn.text();
                $btn.prop('disabled', true).text('Guardando...');

                $.ajax({
                    url: fcState.apiUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'update_status',
                        id: id,
                        estado: status
                    }
                }).done(function (res) {
                    if (res && res.ok) {
                        showAlert($('#cw-fc-messages-alert'), escapeHtml(res.message || 'Estado actualizado correctamente.'), 'success');
                        loadFcMessages(fcState.page);
                        return;
                    }

                    showAlert($('#cw-fc-messages-alert'), escapeHtml((res && res.message) || 'No se pudo actualizar el estado.'), 'danger');
                }).fail(function () {
                    showAlert($('#cw-fc-messages-alert'), 'No se pudo actualizar el estado.', 'danger');
                }).always(function () {
                    $btn.prop('disabled', false).text(original);
                });
            });

            $(document).on('click', '#cw-fc-messages-body .cw-fc-delete-message', function () {
                var $btn = $(this);
                var id = parseInt($btn.data('id'), 10);
                if (!isFinite(id) || id < 1) {
                    showAlert($('#cw-fc-messages-alert'), 'Mensaje invalido.', 'danger');
                    return;
                }

                if (!fcState.apiUrl) {
                    showAlert($('#cw-fc-messages-alert'), 'No se encontro la ruta para eliminar mensajes.', 'danger');
                    return;
                }

                if (!window.confirm('Se eliminara este mensaje de forma permanente. Continuar?')) {
                    return;
                }

                var original = $btn.text();
                $btn.prop('disabled', true).text('Eliminando...');

                $.ajax({
                    url: fcState.apiUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'delete',
                        id: id
                    }
                }).done(function (res) {
                    if (res && res.ok) {
                        showAlert($('#cw-fc-messages-alert'), escapeHtml(res.message || 'Mensaje eliminado correctamente.'), 'success');
                        loadFcMessages(fcState.page);
                        return;
                    }

                    showAlert($('#cw-fc-messages-alert'), escapeHtml((res && res.message) || 'No se pudo eliminar el mensaje.'), 'danger');
                }).fail(function () {
                    showAlert($('#cw-fc-messages-alert'), 'No se pudo eliminar el mensaje.', 'danger');
                }).always(function () {
                    $btn.prop('disabled', false).text(original);
                });
            });

            $(document).on('submit', '#cw-fc-carousel-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                refreshFcItems();

                var count = $('#cw-fc-carousel-items .cw-fc-item').length;
                if (count < 1) {
                    showAlert($('#cw-fc-carousel-alert'), 'Debes registrar al menos 1 elemento.', 'warning');
                    return;
                }
                if (count > 5) {
                    showAlert($('#cw-fc-carousel-alert'), 'Solo se permiten hasta 5 elementos.', 'warning');
                    return;
                }

                var formData = new FormData($form[0]);

                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-fc-carousel-alert'),
                    submit: $form.find('#cw-fc-carousel-submit'),
                    ajaxConfig: {
                        data: formData,
                        processData: false,
                        contentType: false
                    },
                    defaultButtonText: 'Guardar carrusel',
                    defaultError: 'No se pudo guardar la configuracion de carrusel.',
                    onSuccess: function (res) {
                        var applied = applyFcSavedItems((res && res.items) || []);
                        if (!applied) {
                            loadView('formulario_carrusel');
                        }
                    }
                });
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

            $(document).on('submit', '#cw-counter-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-counter-alert'),
                    submit: $form.find('#cw-counter-submit'),
                    ajaxConfig: {
                        data: $form.serialize()
                    },
                    defaultButtonText: 'Guardar contadores',
                    defaultError: 'No se pudo guardar la configuracion de contadores.'
                });
            });

            $(document).on('submit', '#cw-services-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-services-alert'),
                    submit: $form.find('#cw-services-submit'),
                    ajaxConfig: {
                        data: $form.serialize()
                    },
                    defaultButtonText: 'Guardar servicios',
                    defaultError: 'No se pudo guardar la configuracion de servicios.'
                });
            });

            $(document).on('submit', '#cw-banner-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                var formData = new FormData($form[0]);

                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-banner-alert'),
                    submit: $form.find('#cw-banner-submit'),
                    ajaxConfig: {
                        data: formData,
                        processData: false,
                        contentType: false
                    },
                    defaultButtonText: 'Guardar banner',
                    defaultError: 'No se pudo guardar la configuracion de banner.',
                    onSuccess: function (res) {
                        var previewApi = $form.data('cwBannerPreview');
                        if (previewApi && typeof previewApi.setCurrent === 'function') {
                            previewApi.setCurrent(String((res && res.imagen_url) || ''));
                        }
                        $('#cw_banner_imagen_archivo').val('');
                        $('#cw_banner_eliminar_imagen').prop('checked', false);
                    }
                });
            });

            $(document).on('submit', '#cw-process-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                var count = $('#cw-process-items .cw-process-item').length;
                if (count < 3) {
                    showAlert($('#cw-process-alert'), 'Debes registrar minimo 3 bloques.', 'warning');
                    return;
                }
                if (count > 9) {
                    showAlert($('#cw-process-alert'), 'Solo se permiten hasta 9 bloques.', 'warning');
                    return;
                }

                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-process-alert'),
                    submit: $form.find('#cw-process-submit'),
                    ajaxConfig: {
                        data: $form.serialize()
                    },
                    defaultButtonText: 'Guardar proceso',
                    defaultError: 'No se pudo guardar la configuracion de proceso.'
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

            $(document).on('submit', '#cw-about-form', function (e) {
                e.preventDefault();

                var $form = $(this);
                var formData = new FormData($form[0]);

                submitAjaxForm({
                    form: $form,
                    alert: $('#cw-about-alert'),
                    submit: $form.find('#cw-about-submit'),
                    ajaxConfig: {
                        data: formData,
                        processData: false,
                        contentType: false
                    },
                    defaultButtonText: 'Guardar nosotros',
                    defaultError: 'No se pudo guardar la seccion Nosotros.',
                    onSuccess: function (res) {
                        var previews = $form.data('cwAboutPreviews') || {};

                        if (previews.icon1 && typeof previews.icon1.setCurrent === 'function') {
                            previews.icon1.setCurrent(String((res && res.icono_1_url) || ''));
                        }
                        if (previews.icon2 && typeof previews.icon2.setCurrent === 'function') {
                            previews.icon2.setCurrent(String((res && res.icono_2_url) || ''));
                        }
                        if (previews.fundador && typeof previews.fundador.setCurrent === 'function') {
                            previews.fundador.setCurrent(String((res && res.imagen_fundador_url) || ''));
                        }
                        if (previews.principal && typeof previews.principal.setCurrent === 'function') {
                            previews.principal.setCurrent(String((res && res.imagen_principal_url) || ''));
                        }
                        if (previews.secundaria && typeof previews.secundaria.setCurrent === 'function') {
                            previews.secundaria.setCurrent(String((res && res.imagen_secundaria_url) || ''));
                        }

                        $('#cw_about_icono_archivo_1, #cw_about_icono_archivo_2, #cw_about_imagen_fundador_archivo, #cw_about_imagen_principal_archivo, #cw_about_imagen_secundaria_archivo').val('');
                        $('#cw_about_eliminar_icono_1, #cw_about_eliminar_icono_2, #cw_about_eliminar_imagen_fundador, #cw_about_eliminar_imagen_principal, #cw_about_eliminar_imagen_secundaria').prop('checked', false);
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

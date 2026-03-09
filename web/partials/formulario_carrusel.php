<?php
// web/partials/formulario_carrusel.php
require_once __DIR__ . '/../../sistema/includes/conexion.php';
require_once __DIR__ . '/formulario_carrusel_model.php';

if (!function_exists('cw_fc_h')) {
    function cw_fc_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$data = cw_fc_defaults();
$carouselItems = cw_fc_normalize_carousel_items($data['carousel_items'] ?? []);
if (function_exists('db')) {
    $cn = db();
    if ($cn instanceof mysqli) {
        $carouselItems = cw_fc_fetch_carousel_items($cn);
    }
}

$formConfig = $data['formulario'] ?? [];
$serviceOptions = cw_fc_service_options();
$cityOptions = cw_fc_city_options();
$scheduleOptions = cw_fc_schedule_options();
$submitUrl = cw_fc_default_asset_url('web/partials/formulario_carrusel_submit.php');
$carouselId = 'carouselFormularioCarrusel';
?>
<style>
.cw-fc-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.cw-fc-type-card {
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.06);
    padding: 10px;
    cursor: pointer;
    transition: border-color .2s ease, background-color .2s ease;
}

.cw-fc-type-card.active {
    border-color: var(--bs-primary);
    background: rgba(13, 110, 253, 0.16);
}

.cw-fc-type-card strong,
.cw-fc-type-card small {
    display: block;
}

.cw-fc-type-card small {
    opacity: .9;
    line-height: 1.3;
}

.cw-fc-help {
    font-size: .83rem;
    color: rgba(255, 255, 255, 0.8);
}

.cw-fc-fields-persona,
.cw-fc-fields-empresa {
    margin-top: 10px;
}

.cw-fc-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

@media (max-width: 575.98px) {
    .cw-fc-type-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div id="inicio" class="header-carousel">
    <div id="<?php echo cw_fc_h($carouselId); ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="false">
        <ol class="carousel-indicators">
            <?php foreach ($carouselItems as $idx => $item): ?>
                <li
                    data-bs-target="#<?php echo cw_fc_h($carouselId); ?>"
                    data-bs-slide-to="<?php echo cw_fc_h((string)$idx); ?>"
                    class="<?php echo $idx === 0 ? 'active' : ''; ?>"
                    <?php echo $idx === 0 ? 'aria-current="true"' : ''; ?>
                    aria-label="Slide <?php echo cw_fc_h((string)($idx + 1)); ?>"
                ></li>
            <?php endforeach; ?>
        </ol>

        <div class="carousel-inner" role="listbox">
            <?php foreach ($carouselItems as $idx => $item): ?>
                <?php
                $slidePrefix = 'cw_fc_' . ($idx + 1);
                $slideImageUrl = cw_fc_resolve_slide_image_url($item, $idx);
                ?>
                <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo cw_fc_h($slideImageUrl); ?>" class="img-fluid w-100" alt="Carrusel principal <?php echo cw_fc_h((string)($idx + 1)); ?>"/>
                    <div class="carousel-caption">
                        <div class="container py-4">
                            <div class="row g-5">
                                <div class="col-lg-6 fadeInLeft animated" data-animation="fadeInLeft" data-delay="1s" style="animation-delay: 1s;">
                                    <div class="bg-secondary rounded p-4 p-md-5">
                                        <h4 class="text-white mb-3"><?php echo cw_fc_h((string)($formConfig['titulo'] ?? '')); ?></h4>
                                        <p class="text-white mb-3"><?php echo cw_fc_h((string)($formConfig['descripcion'] ?? '')); ?></p>

                                        <form class="cw-fc-public-form" method="post" action="<?php echo cw_fc_h($submitUrl); ?>" novalidate>
                                            <input type="hidden" name="contexto_slide" value="<?php echo cw_fc_h((string)($idx + 1)); ?>">

                                            <div class="mb-2 text-white">
                                                <strong>Tipo de solicitante</strong>
                                                <div class="cw-fc-type-grid mt-2" data-cw-fc-type-grid="1">
                                                    <label class="cw-fc-type-card" for="<?php echo cw_fc_h($slidePrefix . '_tipo_empresa'); ?>">
                                                        <input class="cw-fc-radio cw-fc-tipo" type="radio" name="tipo_solicitante" id="<?php echo cw_fc_h($slidePrefix . '_tipo_empresa'); ?>" value="empresa" required>
                                                        <strong>Soy Empresa</strong>
                                                        <small>Hacemos convenios con empresas.</small>
                                                    </label>
                                                    <label class="cw-fc-type-card" for="<?php echo cw_fc_h($slidePrefix . '_tipo_persona'); ?>">
                                                        <input class="cw-fc-radio cw-fc-tipo" type="radio" name="tipo_solicitante" id="<?php echo cw_fc_h($slidePrefix . '_tipo_persona'); ?>" value="persona" required>
                                                        <strong>Soy Persona</strong>
                                                        <small>Brindamos orientacion personalizada.</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-12 col-md-6">
                                                    <label class="text-white mb-1" for="<?php echo cw_fc_h($slidePrefix . '_servicio'); ?>">Servicio de interés</label>
                                                    <select class="form-select" id="<?php echo cw_fc_h($slidePrefix . '_servicio'); ?>" name="servicio_interes" required>
                                                        <option value="">Selecciona un servicio</option>
                                                        <?php foreach ($serviceOptions as $service): ?>
                                                            <option value="<?php echo cw_fc_h((string)$service['code']); ?>"><?php echo cw_fc_h((string)$service['label']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="text-white mb-1" for="<?php echo cw_fc_h($slidePrefix . '_ciudad'); ?>">Escuelas por Ciudad</label>
                                                    <select class="form-select" id="<?php echo cw_fc_h($slidePrefix . '_ciudad'); ?>" name="ciudad_escuela" required>
                                                        <option value="">Selecciona una ciudad y escuela</option>
                                                        <?php foreach ($cityOptions as $city): ?>
                                                            <option value="<?php echo cw_fc_h((string)$city['code']); ?>"><?php echo cw_fc_h((string)$city['label']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="cw-fc-fields-persona d-none" data-cw-fc-persona="1">
                                                <div class="row g-2">
                                                    <div class="col-12 col-md-6">
                                                        <input class="form-control" type="text" name="documento_persona" maxlength="20" pattern="(?:\d{8}|[A-Za-z0-9]{9,12})" placeholder="DNI o CE">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <input class="form-control" type="text" name="nombres_persona" maxlength="140" placeholder="Nombres y apellidos">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <input class="form-control" type="text" name="celular_persona" maxlength="20" pattern="[0-9+\s\-]{9,20}" inputmode="tel" placeholder="Celular / WhatsApp">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <input class="form-control" type="email" name="correo_persona" maxlength="150" placeholder="Correo electronico (opcional)">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="cw-fc-fields-empresa d-none" data-cw-fc-empresa="1">
                                                <div class="row g-2">
                                                    <div class="col-12 col-md-6">
                                                        <input class="form-control" type="text" name="ruc_empresa" maxlength="20" pattern="(?:10|15|16|17|20)\d{9}" inputmode="numeric" placeholder="RUC">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <input class="form-control" type="text" name="razon_social_empresa" maxlength="160" placeholder="Razon social">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <input class="form-control" type="text" name="celular_empresa" maxlength="20" pattern="[0-9+\s\-]{9,20}" inputmode="tel" placeholder="Celular / WhatsApp">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <input class="form-control" type="email" name="correo_empresa" maxlength="150" placeholder="Correo electronico (opcional)">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-2 mb-3">
                                                <label class="text-white mb-1" for="<?php echo cw_fc_h($slidePrefix . '_horario'); ?>">Horario en el que te podemos llamar</label>
                                                <select class="form-select" id="<?php echo cw_fc_h($slidePrefix . '_horario'); ?>" name="horario_contacto" required>
                                                    <?php foreach ($scheduleOptions as $schedule): ?>
                                                        <?php $isAny = ((string)$schedule['code'] === 'any'); ?>
                                                        <option value="<?php echo cw_fc_h((string)$schedule['code']); ?>" <?php echo $isAny ? 'selected' : ''; ?>>
                                                            <?php echo cw_fc_h((string)$schedule['label']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <button class="btn btn-light w-100 py-2" type="submit" data-cw-fc-submit="1">
                                                <?php echo cw_fc_h((string)($formConfig['texto_boton'] ?? 'Enviar solicitud')); ?>
                                            </button>
                                            <small class="cw-fc-help d-block mt-2">
                                                <?php echo cw_fc_h((string)($formConfig['texto_ayuda'] ?? '')); ?>
                                            </small>
                                        </form>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-none d-lg-flex fadeInRight animated" data-animation="fadeInRight" data-delay="1s" style="animation-delay: 1s;">
                                    <div class="text-start">
                                        <h1 class="display-5 text-white"><?php echo cw_fc_h((string)($item['titulo'] ?? '')); ?></h1>
                                        <p><?php echo cw_fc_h((string)($item['texto'] ?? '')); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
(function () {
    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function stripHtml(value) {
        var div = document.createElement('div');
        div.innerHTML = String(value || '');
        return div.textContent || div.innerText || '';
    }

    function forEachNode(nodeList, fn) {
        if (!nodeList || typeof fn !== 'function') {
            return;
        }
        for (var i = 0; i < nodeList.length; i += 1) {
            fn(nodeList[i], i);
        }
    }

    var carouselEl = document.getElementById('<?php echo cw_fc_h($carouselId); ?>');
    var carouselLockedByForm = false;

    function getCarouselApi() {
        if (!carouselEl || !window.bootstrap || !window.bootstrap.Carousel) {
            return null;
        }

        try {
            var CarouselCtor = window.bootstrap.Carousel;
            var options = {
                interval: 5000,
                ride: 'carousel',
                pause: false
            };

            if (typeof CarouselCtor.getOrCreateInstance === 'function') {
                return CarouselCtor.getOrCreateInstance(carouselEl, options);
            }

            var instance = null;
            if (typeof CarouselCtor.getInstance === 'function') {
                instance = CarouselCtor.getInstance(carouselEl);
            }
            if (!instance) {
                instance = new CarouselCtor(carouselEl, options);
            }
            return instance;
        } catch (e) {
            return null;
        }
    }

    function ensureCarouselAutoPlay() {
        if (!carouselEl || carouselLockedByForm) {
            return;
        }

        carouselEl.setAttribute('data-bs-interval', '5000');
        carouselEl.setAttribute('data-bs-ride', 'carousel');
        var api = getCarouselApi();
        if (api && typeof api.cycle === 'function') {
            api.cycle();
        }
    }

    function lockCarouselWhileFilling() {
        if (!carouselEl || carouselLockedByForm) {
            return;
        }

        carouselLockedByForm = true;
        carouselEl.setAttribute('data-bs-interval', 'false');
        carouselEl.setAttribute('data-bs-ride', 'false');

        var api = getCarouselApi();
        if (api && typeof api.pause === 'function') {
            api.pause();
        }
    }

    function ensureModal() {
        var modal = document.getElementById('cwFcFeedbackModal');
        if (modal) {
            return modal;
        }

        var wrapper = document.createElement('div');
        wrapper.innerHTML = ''
            + '<div class="modal fade" id="cwFcFeedbackModal" tabindex="-1" aria-hidden="true">'
            + '  <div class="modal-dialog modal-dialog-centered">'
            + '    <div class="modal-content">'
            + '      <div class="modal-header">'
            + '        <h5 class="modal-title">Formulario</h5>'
            + '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
            + '      </div>'
            + '      <div class="modal-body"></div>'
            + '      <div class="modal-footer">'
            + '        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        document.body.appendChild(wrapper.firstElementChild);
        return document.getElementById('cwFcFeedbackModal');
    }

    function showModal(title, htmlMessage, type) {
        var modal;
        try {
            modal = ensureModal();
        } catch (e) {
            modal = null;
        }

        if (!modal) {
            window.alert(stripHtml(title + '\n' + htmlMessage));
            return;
        }

        var header = modal.querySelector('.modal-header');
        var titleEl = modal.querySelector('.modal-title');
        var bodyEl = modal.querySelector('.modal-body');
        if (titleEl) {
            titleEl.textContent = String(title || 'Formulario');
        }
        if (bodyEl) {
            bodyEl.innerHTML = String(htmlMessage || '');
        }

        if (header) {
            header.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
            header.classList.add('bg-primary', 'text-white');
        }

        if (type === 'success') {
            if (header) {
                header.classList.remove('bg-primary');
                header.classList.add('bg-success');
            }
        } else if (type === 'danger') {
            if (header) {
                header.classList.remove('bg-primary');
                header.classList.add('bg-danger');
            }
        } else if (type === 'warning') {
            if (header) {
                header.classList.remove('bg-primary');
                header.classList.add('bg-warning');
                header.classList.remove('text-white');
            }
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            try {
                var ModalCtor = window.bootstrap.Modal;
                if (typeof ModalCtor.getOrCreateInstance === 'function') {
                    ModalCtor.getOrCreateInstance(modal).show();
                    return;
                }

                var instance = null;
                if (typeof ModalCtor.getInstance === 'function') {
                    instance = ModalCtor.getInstance(modal);
                }
                if (!instance) {
                    instance = new ModalCtor(modal);
                }
                if (instance && typeof instance.show === 'function') {
                    instance.show();
                    return;
                }
            } catch (e) {
                // Fallback to alert below.
            }
        }

        window.alert(stripHtml(title + '\n' + htmlMessage));
    }

    function setType(form, type) {
        var isPersona = type === 'persona';
        var isEmpresa = type === 'empresa';

        var persona = form.querySelector('[data-cw-fc-persona]');
        var empresa = form.querySelector('[data-cw-fc-empresa]');

        if (persona) {
            persona.classList.toggle('d-none', !isPersona);
            forEachNode(persona.querySelectorAll('input, select, textarea'), function (el) {
                el.disabled = !isPersona;
            });
        }
        if (empresa) {
            empresa.classList.toggle('d-none', !isEmpresa);
            forEachNode(empresa.querySelectorAll('input, select, textarea'), function (el) {
                el.disabled = !isEmpresa;
            });
        }

        var personaDoc = form.querySelector('input[name="documento_persona"]');
        var personaName = form.querySelector('input[name="nombres_persona"]');
        var personaPhone = form.querySelector('input[name="celular_persona"]');
        var empresaRuc = form.querySelector('input[name="ruc_empresa"]');
        var empresaRaz = form.querySelector('input[name="razon_social_empresa"]');
        var empresaPhone = form.querySelector('input[name="celular_empresa"]');

        if (personaDoc) { personaDoc.required = isPersona; }
        if (personaName) { personaName.required = isPersona; }
        if (personaPhone) { personaPhone.required = isPersona; }
        if (empresaRuc) { empresaRuc.required = isEmpresa; }
        if (empresaRaz) { empresaRaz.required = isEmpresa; }
        if (empresaPhone) { empresaPhone.required = isEmpresa; }

        var cards = form.querySelectorAll('.cw-fc-type-card');
        forEachNode(cards, function (card) {
            var radio = card.querySelector('.cw-fc-tipo');
            var active = !!(radio && radio.checked);
            card.classList.toggle('active', active);
        });
    }

    function buildErrorList(errors) {
        if (!Array.isArray(errors) || errors.length === 0) {
            return '';
        }

        var html = '<ul class="mb-0 ps-3">';
        errors.forEach(function (item) {
            html += '<li>' + escapeHtml(item) + '</li>';
        });
        html += '</ul>';
        return html;
    }

    function addErrorIfInvalid(form, selector, label, errors, required) {
        var el = form.querySelector(selector);
        if (!el || el.disabled) {
            return;
        }

        var value = String(el.value || '').trim();
        var isRequired = !!required;
        if (isRequired && value === '') {
            errors.push('Completa el campo ' + label + '.');
            return;
        }

        if (value !== '' && typeof el.checkValidity === 'function' && !el.checkValidity()) {
            errors.push('Verifica el formato del campo ' + label + '.');
        }
    }

    function onlyDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function normalizePeruMobile(value) {
        var digits = onlyDigits(value);
        if (digits.indexOf('51') === 0 && digits.length === 11) {
            digits = digits.slice(2);
        }
        return digits;
    }

    function isValidPeruMobile(value) {
        return /^9\d{8}$/.test(normalizePeruMobile(value));
    }

    function isValidDniOrCe(value) {
        var clean = String(value || '').trim().toUpperCase();
        return /^(?:\d{8}|[A-Z0-9]{9,12})$/.test(clean);
    }

    function isValidRuc(value) {
        return /^(10|15|16|17|20)\d{9}$/.test(onlyDigits(value));
    }

    function collectClientErrors(form, type) {
        var errors = [];
        if (type !== 'persona' && type !== 'empresa') {
            errors.push('Selecciona si eres Persona o Empresa.');
        }

        addErrorIfInvalid(form, 'select[name="servicio_interes"]', 'Servicio de interes', errors, true);
        addErrorIfInvalid(form, 'select[name="ciudad_escuela"]', 'Ciudad', errors, true);
        addErrorIfInvalid(form, 'select[name="horario_contacto"]', 'Horario en el que te podemos llamar', errors, true);

        if (type === 'persona') {
            addErrorIfInvalid(form, 'input[name="nombres_persona"]', 'Nombres y apellidos', errors, true);
            addErrorIfInvalid(form, 'input[name="correo_persona"]', 'Correo electronico', errors, false);

            var docPersona = String((form.querySelector('input[name="documento_persona"]') || {}).value || '').trim();
            if (docPersona === '') {
                errors.push('Completa el campo DNI o CE.');
            } else if (!isValidDniOrCe(docPersona)) {
                errors.push('El DNI o CE es invalido. DNI: 8 digitos. CE: 9 a 12 caracteres alfanumericos.');
            }

            var celularPersona = String((form.querySelector('input[name="celular_persona"]') || {}).value || '').trim();
            if (celularPersona === '') {
                errors.push('Completa el campo Celular / WhatsApp.');
            } else if (!isValidPeruMobile(celularPersona)) {
                errors.push('El celular debe tener 9 digitos y comenzar con 9. Tambien se permite +51 9XXXXXXXX.');
            }
        } else if (type === 'empresa') {
            addErrorIfInvalid(form, 'input[name="razon_social_empresa"]', 'Razon social', errors, true);
            addErrorIfInvalid(form, 'input[name="correo_empresa"]', 'Correo electronico', errors, false);

            var rucEmpresa = String((form.querySelector('input[name="ruc_empresa"]') || {}).value || '').trim();
            if (rucEmpresa === '') {
                errors.push('Completa el campo RUC.');
            } else if (!isValidRuc(rucEmpresa)) {
                errors.push('El RUC es invalido. Debe tener 11 digitos y un prefijo valido (10, 15, 16, 17 o 20).');
            }

            var celularEmpresa = String((form.querySelector('input[name="celular_empresa"]') || {}).value || '').trim();
            if (celularEmpresa === '') {
                errors.push('Completa el campo Celular / WhatsApp.');
            } else if (!isValidPeruMobile(celularEmpresa)) {
                errors.push('El celular debe tener 9 digitos y comenzar con 9. Tambien se permite +51 9XXXXXXXX.');
            }
        }

        return errors;
    }

    function parseJsonSafe(rawText) {
        if (typeof rawText !== 'string') {
            return null;
        }
        try {
            var parsed = JSON.parse(rawText);
            if (parsed && typeof parsed === 'object') {
                return parsed;
            }
        } catch (e) {
            return null;
        }
        return null;
    }

    function sendWithFetch(url, body, timeoutMs) {
        var controller = null;
        var timer = null;
        if (typeof window.AbortController === 'function') {
            controller = new window.AbortController();
        }

        var options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body
        };
        if (controller) {
            options.signal = controller.signal;
        }

        if (controller && timeoutMs > 0) {
            timer = window.setTimeout(function () {
                try {
                    controller.abort();
                } catch (e) {
                }
            }, timeoutMs);
        }

        return window.fetch(url, options).then(function (res) {
            return res.text();
        }).then(function (text) {
            if (timer) {
                window.clearTimeout(timer);
            }
            return parseJsonSafe(text) || { ok: false, message: 'Respuesta invalida del servidor.' };
        }).catch(function (error) {
            if (timer) {
                window.clearTimeout(timer);
            }
            if (error && error.name === 'AbortError') {
                var timeoutError = new Error('timeout');
                timeoutError.code = 'timeout';
                throw timeoutError;
            }
            throw error;
        });
    }

    function sendWithXhr(url, body, timeoutMs) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (timeoutMs > 0) {
                xhr.timeout = timeoutMs;
            }

            xhr.onload = function () {
                resolve(parseJsonSafe(String(xhr.responseText || '')) || { ok: false, message: 'Respuesta invalida del servidor.' });
            };
            xhr.onerror = function () {
                var networkError = new Error('network');
                networkError.code = 'network';
                reject(networkError);
            };
            xhr.ontimeout = function () {
                var timeoutError = new Error('timeout');
                timeoutError.code = 'timeout';
                reject(timeoutError);
            };

            xhr.send(body);
        });
    }

    function sendRequest(url, body, timeoutMs) {
        if (typeof window.fetch === 'function') {
            return sendWithFetch(url, body, timeoutMs);
        }
        return sendWithXhr(url, body, timeoutMs);
    }

    function initForm(form) {
        if (!form || form.getAttribute('data-cw-fc-ready') === '1') {
            return;
        }
        form.setAttribute('data-cw-fc-ready', '1');

        setType(form, '');

        var radios = form.querySelectorAll('.cw-fc-tipo');
        forEachNode(radios, function (radio) {
            radio.addEventListener('change', function () {
                setType(form, radio.value);
            });

            var card = radio.closest('.cw-fc-type-card');
            if (card) {
                card.addEventListener('keydown', function (evt) {
                    if (evt.key === ' ' || evt.key === 'Enter') {
                        evt.preventDefault();
                        radio.checked = true;
                        setType(form, radio.value);
                    }
                });
            }
        });

        var pauseEvents = ['focusin', 'input', 'change'];
        forEachNode(pauseEvents, function (eventName) {
            form.addEventListener(eventName, function (evt) {
                var target = evt && evt.target ? evt.target : null;
                if (!target) {
                    return;
                }
                var tag = String(target.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'select' || tag === 'textarea' || tag === 'button') {
                    lockCarouselWhileFilling();
                }
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            lockCarouselWhileFilling();

            if (form.getAttribute('data-cw-fc-submitting') === '1') {
                return;
            }

            var submitBtn = form.querySelector('[data-cw-fc-submit]');
            var originalText = submitBtn ? (submitBtn.dataset.originalText || submitBtn.textContent || 'Enviar') : 'Enviar';
            var submitFinished = false;

            function setSubmittingState(isSubmitting) {
                if (!submitBtn) {
                    return;
                }
                submitBtn.dataset.originalText = originalText;
                submitBtn.disabled = !!isSubmitting;
                submitBtn.textContent = isSubmitting ? 'Enviando...' : originalText;
            }

            function finishSubmit() {
                if (submitFinished) {
                    return;
                }
                submitFinished = true;
                form.setAttribute('data-cw-fc-submitting', '0');
                setSubmittingState(false);
            }

            form.setAttribute('data-cw-fc-submitting', '1');
            setSubmittingState(true);

            try {
                var checked = form.querySelector('.cw-fc-tipo:checked');
                var type = checked ? checked.value : '';
                setType(form, type);

                var clientErrors = collectClientErrors(form, type);
                if (clientErrors.length > 0) {
                    if (type && typeof form.reportValidity === 'function') {
                        form.reportValidity();
                    }
                    var validationList = buildErrorList(clientErrors);
                    showModal('Formulario incompleto', '<p>Revisa los datos requeridos:</p>' + validationList, 'warning');
                    finishSubmit();
                    return;
                }

                var action = String(form.getAttribute('action') || '').trim();
                if (!action) {
                    var noRouteMsg = 'No se encontro la ruta de envio del formulario.';
                    showModal('Error de configuracion', '<p class="mb-0">' + escapeHtml(noRouteMsg) + '</p>', 'danger');
                    finishSubmit();
                    return;
                }

                var body = new URLSearchParams(new FormData(form)).toString();
                sendRequest(action, body, 15000)
                    .then(function (res) {
                        if (res && res.ok) {
                            var successMsg = escapeHtml(res.message || 'Solicitud enviada correctamente.');
                            showModal('Solicitud enviada', '<p class="mb-0">' + successMsg + '</p>', 'success');
                            form.reset();
                            setType(form, '');
                            return;
                        }

                        var failMsg = escapeHtml((res && res.message) || 'No se pudo registrar tu solicitud.');
                        var errorList = buildErrorList((res && res.errors) || []);
                        showModal('No se pudo enviar', '<p>' + failMsg + '</p>' + errorList, 'danger');
                    })
                    .catch(function (error) {
                        var msg = 'No se pudo enviar la solicitud. Intenta nuevamente.';
                        if (error && error.code === 'timeout') {
                            msg = 'La solicitud tardo demasiado. Revisa tu conexion e intenta nuevamente.';
                        }
                        showModal('Error de envio', '<p class="mb-0">' + escapeHtml(msg) + '</p>', 'danger');
                    })
                    .then(function () {
                        finishSubmit();
                    });
            } catch (error) {
                var unexpectedMsg = 'Ocurrio un error al validar o enviar el formulario.';
                showModal('Error inesperado', '<p class="mb-0">' + escapeHtml(unexpectedMsg) + '</p>', 'danger');
                finishSubmit();
            }
        });
    }

    function boot() {
        ensureCarouselAutoPlay();

        var forms = document.querySelectorAll('.cw-fc-public-form');
        forEachNode(forms, function (form) {
            initForm(form);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>

<?php
// /modules/egresos/index.php

require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/permisos.php';
require_once __DIR__ . '/../../includes/conexion.php';

acl_require_ids([3, 4]);
verificarPermiso(['Recepción', 'Administración']);

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$u      = currentUser();
$usrNom = trim(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');
$empNom = (string) ($u['empresa']['nombre'] ?? '—');

// Logo empresa (ruta relativa a este archivo) – si falla, usa logo genérico
$empresaId   = (int) ($u['empresa']['id'] ?? 0);
$empLogoRel  = '';

try {
    $logoFromSession = isset($u['empresa']['logo_path']) ? trim((string) $u['empresa']['logo_path']) : '';

    if ($logoFromSession === '' && $empresaId > 0) {
        $st = db()->prepare('SELECT logo_path FROM mtp_empresas WHERE id=? LIMIT 1');
        $st->bind_param('i', $empresaId);
        $st->execute();

        if ($r = $st->get_result()->fetch_assoc()) {
            $logoFromSession = trim((string) ($r['logo_path'] ?? ''));
        }

        $st->close();
    }

    if ($logoFromSession !== '') {
        $rel = '../../' . ltrim($logoFromSession, '/');

        if (is_file(__DIR__ . '/../../' . ltrim($logoFromSession, '/'))) {
            $empLogoRel = $rel;
        }
    }

    if ($empLogoRel === '') {
        $fallback = '../../dist/img/AdminLTELogo.png';

        if (is_file(__DIR__ . '/../../dist/img/AdminLTELogo.png')) {
            $empLogoRel = $fallback;
        }
    }
} catch (Throwable $e) {
    // silencioso
}

include __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/modules/egresos/style.css?v=2">

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="eg-bar shadow-sm">
                <div class="eg-bar-left">
                    <div class="eg-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>

                    <div class="eg-titles">
                        <div class="eg-title">Módulo de egresos</div>

                        <div class="eg-subtitle">
                            Empresa: <strong>"<?= h($empNom) ?>"</strong> &nbsp;•&nbsp;
                            Usuario: <strong><?= h($usrNom) ?></strong>
                        </div>

                        <div class="eg-subtitle small">
                            Registra facturas, boletas y recibos vinculados a la caja diaria.
                        </div>
                    </div>
                </div>

                <div class="eg-bar-right">
                    <div class="eg-caja-pill">
                        <span class="label">Caja diaria</span>
                        <span class="badge badge-pill badge-success" id="egCajaBadge">Simulada: abierta</span>
                    </div>

                    <div class="eg-bar-meta small text-right">
                        <div>
                            <i class="far fa-calendar-alt mr-1"></i><span id="egFechaHoy"></span>
                        </div>
                        <div class="text-muted">Modo demo: no valida la caja real.</div>
                    </div>
                </div>
            </div>

            <div id="egCajaMsg" class="alert eg-caja-alert mt-3 mb-0" role="alert"></div>
        </div>
    </div>

    <section class="content pb-3">
        <div class="container-fluid">
            <div class="row g-3">
                <!-- Columna izquierda: formulario -->
                <div class="col-12 col-lg-5">
                    <div class="card eg-card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h5 class="card-title mb-0">Nuevo egreso</h5>
                                    <div class="text-muted small">Maqueta demo, sin guardar en base de datos.</div>
                                </div>
                                <span class="badge badge-light" id="egModoDemo">Modo demo</span>
                            </div>

                            <div id="egFormAlert"></div>

                            <form id="egForm" autocomplete="off">
                                <!-- Tipo de comprobante -->
                                <div class="form-group mb-3">
                                    <label class="mb-1">Tipo de comprobante</label>

                                    <div class="eg-chip-group" id="egTipoChipGroup">
                                        <button type="button" class="eg-chip active" data-tipo="RECIBO">Recibo interno</button>
                                        <button type="button" class="eg-chip" data-tipo="BOLETA">Boleta</button>
                                        <button type="button" class="eg-chip" data-tipo="FACTURA">Factura</button>
                                    </div>

                                    <input type="hidden" name="tipo" id="egTipo" value="RECIBO">

                                    <small class="form-text text-muted">
                                        El tipo define si se requiere serie/número o solo una referencia.
                                    </small>
                                </div>

                                <!-- Serie / número / referencia -->
                                <div class="form-row" id="egSerieNumeroGroup">
                                    <div class="form-group col-4">
                                        <label for="egSerie" class="mb-1">Serie<span class="text-danger">*</span></label>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            id="egSerie"
                                            name="serie"
                                            maxlength="10"
                                            placeholder="F001"
                                        >
                                    </div>

                                    <div class="form-group col-8">
                                        <label for="egNumero" class="mb-1">Número<span class="text-danger">*</span></label>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            id="egNumero"
                                            name="numero"
                                            maxlength="20"
                                            placeholder="00012345"
                                        >
                                    </div>
                                </div>

                                <div class="form-group mb-3 d-none" id="egReciboRefGroup">
                                    <label for="egReferencia" class="mb-1">Referencia (opcional)</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        id="egReferencia"
                                        name="referencia"
                                        maxlength="120"
                                        placeholder="Ej: Recibo manual 001316"
                                    >
                                </div>

                                <!-- Monto y fecha -->
                                <div class="form-row">
                                    <div class="form-group col-6">
                                        <label for="egMonto" class="mb-1">Monto (S/)<span class="text-danger">*</span></label>

                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">S/</span>
                                            </div>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="form-control"
                                                id="egMonto"
                                                name="monto"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="form-group col-6">
                                        <label for="egFecha" class="mb-1">Fecha y hora<span class="text-danger">*</span></label>
                                        <input
                                            type="datetime-local"
                                            class="form-control form-control-sm"
                                            id="egFecha"
                                            name="fecha"
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Beneficiario -->
                                <div class="form-row">
                                    <div class="form-group col-6">
                                        <label for="egBeneficiario" class="mb-1">Beneficiario / Proveedor</label>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            id="egBeneficiario"
                                            name="beneficiario"
                                            maxlength="160"
                                            placeholder="Nombre completo o razón social"
                                        >
                                    </div>

                                    <div class="form-group col-6">
                                        <label for="egDocumento" class="mb-1">Documento</label>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            id="egDocumento"
                                            name="documento"
                                            maxlength="20"
                                            placeholder="DNI / RUC"
                                        >
                                    </div>
                                </div>

                                <!-- Concepto -->
                                <div class="form-group mb-3">
                                    <label for="egConcepto" class="mb-1">Concepto detallado<span class="text-danger">*</span></label>
                                    <textarea
                                        id="egConcepto"
                                        name="concepto"
                                        rows="5"
                                        class="form-control form-control-sm"
                                        placeholder="Describe el motivo del egreso. Puedes detallar varias compras o servicios."
                                        required
                                    ></textarea>

                                    <div class="d-flex justify-content-between mt-1 small text-muted">
                                        <span>Este texto se imprimirá en el recibo.</span>
                                        <span id="egConceptoCount">0 / 1000</span>
                                    </div>
                                </div>

                                <!-- Observaciones -->
                                <div class="form-group mb-3">
                                    <label for="egObs" class="mb-1">
                                        Observaciones internas <span class="text-muted small">(opcional)</span>
                                    </label>
                                    <textarea
                                        id="egObs"
                                        name="observaciones"
                                        rows="2"
                                        class="form-control form-control-sm"
                                        placeholder="Notas solo para el sistema (no aparecen en el recibo)."
                                    ></textarea>
                                </div>

                                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
                                    <div class="small text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        En la versión real, el egreso se vinculará a la caja diaria abierta.
                                    </div>

                                    <div class="eg-form-actions">
                                        <button type="button" class="btn btn-outline-secondary btn-sm mr-1" id="egBtnLimpiar">
                                            <i class="fas fa-eraser mr-1"></i>Limpiar
                                        </button>

                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-save mr-1"></i>Guardar egreso
                                        </button>

                                        <button type="button" class="btn btn-outline-primary btn-sm ml-1" id="egBtnVistaPrevia">
                                            <i class="fas fa-print mr-1"></i>Vista previa recibo
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: listado -->
                <div class="col-12 col-lg-7">
                    <div class="card eg-card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <h5 class="card-title mb-0">Egresos registrados (demo)</h5>
                                    <div class="text-muted small">Solo datos simulados para ver el diseño.</div>
                                </div>

                                <div class="eg-filters d-flex flex-wrap gap-2">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white border-right-0"><i class="fas fa-search"></i></span>
                                        </div>
                                        <input id="egQ" class="form-control border-left-0" placeholder="Buscar por concepto o beneficiario…">
                                    </div>

                                    <select id="egFiltroTipo" class="form-control form-control-sm">
                                        <option value="todos">Todos</option>
                                        <option value="FACTURA">Facturas</option>
                                        <option value="BOLETA">Boletas</option>
                                        <option value="RECIBO">Recibos</option>
                                    </select>

                                    <select id="egFiltroEstado" class="form-control form-control-sm">
                                        <option value="todos">Activos y anulados</option>
                                        <option value="ACTIVO">Solo activos</option>
                                        <option value="ANULADO">Solo anulados</option>
                                    </select>
                                </div>
                            </div>

                            <div class="small text-muted mb-2" id="egResumenListado"></div>

                            <div class="table-responsive flex-grow-1">
                                <table class="table table-sm table-hover mb-2" id="egTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-nowrap">Fecha</th>
                                            <th class="text-nowrap">Tipo</th>
                                            <th class="text-nowrap">Comp.</th>
                                            <th>Beneficiario</th>
                                            <th>Concepto</th>
                                            <th class="text-right text-nowrap">Monto</th>
                                            <th class="text-nowrap">Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="egTableBody">
                                        <tr>
                                            <td colspan="8" class="text-muted small">Cargando datos de ejemplo…</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="small text-muted" id="egTotalesDia"></div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0" id="egPager"></ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: vista previa de egreso / recibo -->
            <div
                class="modal fade"
                id="egresoPrintModal"
                tabindex="-1"
                role="dialog"
                aria-labelledby="egresoPrintModalTitle"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header py-2 bg-dark text-white">
                            <h5 class="modal-title" id="egresoPrintModalTitle">
                                <i class="fas fa-receipt mr-1"></i>Vista previa de recibo de egreso
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div id="egVoucher" class="eg-voucher-wrapper">
                                <!-- contenido generado por JS -->
                            </div>
                        </div>

                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary btn-sm" id="egBtnPrintFake">
                                <i class="fas fa-print mr-1"></i>Imprimir (demo)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
(function () {
    const BASE_URL = '<?= BASE_URL ?>';
    const EMPRESA_NOMBRE = '<?= h($empNom) ?>';
    const USUARIO_NOMBRE = '<?= h($usrNom) ?>';
    const EMPRESA_LOGO = '<?= h($empLogoRel ?? "") ?>';

    const qs = (s, ctx = document) => ctx.querySelector(s);
    const qsa = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));

    const money = (v) => 'S/ ' + Number(v || 0).toFixed(2);

    const state = {
        page: 1,
        perPage: 7,
        filtroTexto: '',
        filtroTipo: 'todos',
        filtroEstado: 'todos',
        cajaAbierta: true, // SIEMPRE true en modo demo
    };

    // ===== Datos DEMO =====
    const DEMO_EGRESOS = [
        {
            id: 1,
            fecha: '2025-05-12T15:00:00',
            tipo: 'RECIBO',
            serie: '',
            numero: '',
            referencia: 'Recibo manual 001316',
            beneficiario: 'Luigi Abad',
            documento: '70379752',
            concepto: 'Comisión de alumno Abad Lopez Luis Angel',
            monto: 30.00,
            estado: 'ACTIVO',
        },
        {
            id: 2,
            fecha: '2025-05-12T09:30:00',
            tipo: 'FACTURA',
            serie: 'F001',
            numero: '000234',
            referencia: '',
            beneficiario: 'Ferretería Central SAC',
            documento: '20123456789',
            concepto: 'Compra de materiales varios para mantenimiento de vehículos.',
            monto: 450.70,
            estado: 'ACTIVO',
        },
        {
            id: 3,
            fecha: '2025-05-11T18:10:00',
            tipo: 'BOLETA',
            serie: 'B003',
            numero: '005678',
            referencia: '',
            beneficiario: 'Taller El Amigo',
            documento: '10456789123',
            concepto: 'Servicio de alineamiento y balanceo de unidades.',
            monto: 180.00,
            estado: 'ANULADO',
        },
        {
            id: 4,
            fecha: '2025-05-10T11:05:00',
            tipo: 'RECIBO',
            serie: '',
            numero: '',
            referencia: 'Recibo caja chica 0005',
            beneficiario: 'Caja chica',
            documento: '',
            concepto: 'Pago de movilidad local y pequeños consumos del personal.',
            monto: 95.50,
            estado: 'ACTIVO',
        },
        {
            id: 5,
            fecha: '2025-05-09T16:20:00',
            tipo: 'FACTURA',
            serie: 'F002',
            numero: '000045',
            referencia: '',
            beneficiario: 'GLOBAL CAR PIURA S.A.C.',
            documento: '20609446863',
            concepto: 'Servicios administrativos internos entre sedes.',
            monto: 320.00,
            estado: 'ACTIVO',
        },
    ];

    // ===== Utilidades =====
    function fmtFechaHora(iso) {
        if (!iso) return '—';

        const d = new Date(iso);
        if (isNaN(d.getTime())) return '—';

        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yy = d.getFullYear();
        const hh = String(d.getHours()).padStart(2, '0');
        const mi = String(d.getMinutes()).padStart(2, '0');

        return `${dd}/${mm}/${yy} ${hh}:${mi}`;
    }

    function showFormAlert(type, html) {
        const cont = qs('#egFormAlert');
        if (!cont) return;

        if (!html) {
            cont.innerHTML = '';
            return;
        }

        const icon =
            type === 'danger'
                ? 'fa-exclamation-triangle'
                : type === 'warning'
                    ? 'fa-exclamation-circle'
                    : 'fa-check-circle';

        cont.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show mb-2" role="alert">
                <i class="fas ${icon} mr-1"></i>${html}
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
    }

    // ===== Tipo de comprobante =====
    function setTipo(tipo) {
        const hidden = qs('#egTipo');
        if (hidden) hidden.value = tipo;

        qsa('#egTipoChipGroup .eg-chip').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.tipo === tipo);
        });

        const serieGroup = qs('#egSerieNumeroGroup');
        const refGroup = qs('#egReciboRefGroup');
        const serieInput = qs('#egSerie');
        const numInput = qs('#egNumero');
        const refInput = qs('#egReferencia');

        const esComp = tipo === 'FACTURA' || tipo === 'BOLETA';
        const esRec = tipo === 'RECIBO';

        if (serieGroup) serieGroup.classList.toggle('d-none', !esComp);
        if (refGroup) refGroup.classList.toggle('d-none', !esRec);

        if (serieInput) serieInput.required = esComp;
        if (numInput) numInput.required = esComp;
        if (refInput) refInput.required = false;
    }

    function actualizarContadorConcepto() {
        const txt = qs('#egConcepto');
        const out = qs('#egConceptoCount');
        if (!txt || !out) return;

        const len = txt.value.length;
        out.textContent = len + ' / 1000';
    }

    // ===== Listado / filtros =====
    function filtrarEgresos() {
        const q = state.filtroTexto.toLowerCase().trim();
        const tipo = state.filtroTipo;
        const est = state.filtroEstado;

        return DEMO_EGRESOS
            .filter((e) => {
                if (tipo !== 'todos' && e.tipo !== tipo) return false;
                if (est !== 'todos' && e.estado !== est) return false;

                if (q) {
                    const blob = (e.concepto + ' ' + (e.beneficiario || '')).toLowerCase();
                    if (!blob.includes(q)) return false;
                }

                return true;
            })
            .sort((a, b) => (a.fecha < b.fecha ? 1 : -1));
    }

    function renderTabla() {
        const tbody = qs('#egTableBody');
        if (!tbody) return;

        const lista = filtrarEgresos();
        const total = lista.length;

        const pages = Math.max(1, Math.ceil(total / state.perPage));
        if (state.page > pages) state.page = pages;

        const start = (state.page - 1) * state.perPage;
        const pageRows = lista.slice(start, start + state.perPage);

        if (!pageRows.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-muted small">No hay egresos que coincidan con el filtro.</td>
                </tr>
            `;
        } else {
            tbody.innerHTML = pageRows
                .map((e) => {
                    const comp =
                        e.tipo === 'RECIBO'
                            ? (e.referencia || '—')
                            : `${e.serie || ''}-${e.numero || ''}`;

                    const claseEstado = e.estado === 'ANULADO' ? 'badge-danger' : 'badge-success';
                    const labelEstado = e.estado === 'ANULADO' ? 'Anulado' : 'Activo';
                    const claseRow = e.estado === 'ANULADO' ? 'eg-row-anulado' : '';

                    const tipoBadge =
                        e.tipo === 'FACTURA'
                            ? 'badge-info'
                            : e.tipo === 'BOLETA'
                                ? 'badge-primary'
                                : 'badge-secondary';

                    const tipoLabel =
                        e.tipo === 'FACTURA'
                            ? 'Factura'
                            : e.tipo === 'BOLETA'
                                ? 'Boleta'
                                : 'Recibo';

                    const conceptoCorto =
                        (e.concepto || '').length > 80 ? (e.concepto.slice(0, 77) + '…') : (e.concepto || '');

                    return `
                        <tr class="${claseRow}">
                            <td class="text-nowrap align-middle">${fmtFechaHora(e.fecha)}</td>
                            <td class="align-middle"><span class="badge ${tipoBadge}">${tipoLabel}</span></td>
                            <td class="align-middle text-nowrap">${comp || '—'}</td>
                            <td class="align-middle">${e.beneficiario || '—'}</td>
                            <td class="align-middle small">${conceptoCorto}</td>
                            <td class="align-middle text-right font-weight-bold">${money(e.monto)}</td>
                            <td class="align-middle"><span class="badge ${claseEstado}">${labelEstado}</span></td>
                            <td class="align-middle text-center text-nowrap">
                                <button class="btn btn-xs btn-outline-primary mr-1" data-action="preview" data-id="${e.id}" title="Ver / imprimir">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn btn-xs btn-outline-danger" data-action="anular" data-id="${e.id}" ${e.estado === 'ANULADO' ? 'disabled' : ''} title="Anular egreso (demo)">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                })
                .join('');
        }

        renderPager(pages, total);
        renderResumen(lista, total);
    }

    function renderPager(pages, total) {
        const ul = qs('#egPager');
        if (!ul) return;

        if (total === 0) {
            ul.innerHTML = '';
            return;
        }

        const cur = state.page;
        const items = [];

        const add = (p, label, disabled = false, active = false) => {
            items.push(`
                <li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
                    <a href="#" class="page-link" data-page="${p}">${label}</a>
                </li>
            `);
        };

        add(cur - 1, '«', cur <= 1);
        let start = Math.max(1, cur - 2);
        let end = Math.min(pages, start + 4);
        start = Math.max(1, end - 4);

        for (let p = start; p <= end; p++) add(p, p, false, p === cur);

        add(cur + 1, '»', cur >= pages);

        ul.innerHTML = items.join('');
    }

    function renderResumen(lista, total) {
        const resumen = qs('#egResumenListado');
        const totales = qs('#egTotalesDia');

        if (resumen) {
            resumen.textContent = total
                ? `${total} egreso(s) coinciden con el filtro seleccionado.`
                : 'Sin egresos para los filtros actuales.';
        }

        if (totales) {
            const suma = lista.reduce((acc, e) => acc + (e.monto || 0), 0);
            totales.textContent = total ? `Total filtrado: ${money(suma)}` : '';
        }
    }

    // ===== "Estado" de caja – 100% simulado =====
    function cargarEstadoCajaDemo() {
        const lbl = qs('#egCajaMsg');
        const fechaHoy = qs('#egFechaHoy');

        if (fechaHoy) {
            const d = new Date();
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yy = d.getFullYear();
            fechaHoy.textContent = `${dd}/${mm}/${yy}`;
        }

        if (lbl) {
            lbl.classList.remove('alert-secondary', 'alert-danger', 'alert-warning');
            lbl.classList.add('alert-success');
            lbl.innerHTML = `
                <i class="fas fa-check-circle mr-1"></i>
                Modo demo: se asume que la caja diaria está abierta. El formulario está completamente habilitado.
            `;
        }

        const badge = qs('#egCajaBadge');
        if (badge) {
            badge.textContent = 'Simulada: abierta';
            badge.classList.remove('badge-danger', 'badge-secondary');
            badge.classList.add('badge-success');
        }

        state.cajaAbierta = true;
    }

    // ===== Formulario =====
    function limpiarFormulario() {
        const form = qs('#egForm');
        if (!form) return;

        form.reset();
        setTipo(qs('#egTipo').value || 'RECIBO');

        const now = new Date();
        const iso = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 16);

        const fechaInput = qs('#egFecha');
        if (fechaInput) fechaInput.value = iso;

        actualizarContadorConcepto();
        showFormAlert('info', 'Formulario limpio. Puedes registrar un nuevo egreso de prueba.');
    }

    function tomarDatosFormulario() {
        const tipo = (qs('#egTipo')?.value || 'RECIBO').toUpperCase();
        const serie = (qs('#egSerie')?.value || '').trim();
        const numero = (qs('#egNumero')?.value || '').trim();
        const referencia = (qs('#egReferencia')?.value || '').trim();
        const monto = parseFloat(qs('#egMonto')?.value || '0');
        const fecha = qs('#egFecha')?.value || null;
        const benef = (qs('#egBeneficiario')?.value || '').trim();
        const doc = (qs('#egDocumento')?.value || '').trim();
        const concepto = (qs('#egConcepto')?.value || '').trim();
        const obs = (qs('#egObs')?.value || '').trim();

        return { tipo, serie, numero, referencia, monto, fecha, benef, doc, concepto, obs };
    }

    function validarDatos(datos) {
        if (!datos.concepto) {
            showFormAlert('danger', 'Escribe un concepto para el egreso.');
            return false;
        }

        if (!(datos.monto > 0)) {
            showFormAlert('danger', 'El monto debe ser mayor a cero.');
            return false;
        }

        if (!datos.fecha) {
            showFormAlert('danger', 'Selecciona fecha y hora del egreso.');
            return false;
        }

        if (datos.tipo === 'FACTURA' || datos.tipo === 'BOLETA') {
            if (!datos.serie || !datos.numero) {
                showFormAlert('danger', 'Para facturas y boletas la serie y el número son obligatorios.');
                return false;
            }
        }

        return true;
    }

    function agregarEgresoDemo(datos) {
        const nuevo = {
            id: Date.now(),
            fecha: datos.fecha,
            tipo: datos.tipo,
            serie: datos.serie,
            numero: datos.numero,
            referencia: datos.tipo === 'RECIBO' ? datos.referencia : '',
            beneficiario: datos.benef || '',
            documento: datos.doc || '',
            concepto: datos.concepto,
            monto: datos.monto,
            estado: 'ACTIVO',
        };

        DEMO_EGRESOS.push(nuevo);
        return nuevo;
    }

    // ===== Voucher =====
    function construirVoucherHTML(egreso) {
        const comp =
            egreso.tipo === 'RECIBO'
                ? (egreso.referencia || 'Recibo interno')
                : `${egreso.serie || ''}-${egreso.numero || ''}`;

        const fecha = fmtFechaHora(egreso.fecha);
        const firmaResponsable = USUARIO_NOMBRE || 'Responsable';

        const logoHtml = EMPRESA_LOGO
            ? `<div class="eg-voucher-logo"><img src="${EMPRESA_LOGO}" alt="Logo"></div>`
            : `<div class="eg-voucher-logo eg-voucher-logo-placeholder"></div>`;

        return `
            <div class="eg-voucher">
                <div class="eg-voucher-head">
                    ${logoHtml}

                    <div class="eg-voucher-head-main">
                        <div class="eg-voucher-empresa">${EMPRESA_NOMBRE}</div>
                        <div class="eg-voucher-sub text-muted small">Recibo de egreso (demo)</div>
                    </div>

                    <div class="eg-voucher-amount-box">
                        <div class="label">S/.</div>
                        <div class="value">${money(egreso.monto).replace('S/ ', '')}</div>
                    </div>
                </div>

                <div class="eg-voucher-row mt-2">
                    <div class="eg-voucher-block">
                        <div class="label">Fecha y hora</div>
                        <div class="value">${fecha}</div>
                    </div>

                    <div class="eg-voucher-block">
                        <div class="label">Comprobante</div>
                        <div class="value">${egreso.tipo} ${comp}</div>
                    </div>

                    <div class="eg-voucher-block">
                        <div class="label">Estado</div>
                        <div class="value">${egreso.estado === 'ANULADO' ? 'ANULADO' : 'EMITIDO'}</div>
                    </div>
                </div>

                <div class="eg-voucher-row mt-2">
                    <div class="eg-voucher-block eg-voucher-block-wide">
                        <div class="label">Beneficiario</div>
                        <div class="value">${egreso.beneficiario || '—'}</div>
                    </div>

                    <div class="eg-voucher-block">
                        <div class="label">Documento</div>
                        <div class="value">${egreso.documento || '—'}</div>
                    </div>
                </div>

                <div class="eg-voucher-section mt-3">
                    <div class="label">Concepto</div>
                    <div class="eg-voucher-concepto">${egreso.concepto || ''}</div>
                </div>

                <div class="eg-voucher-footer">
                    <div class="eg-voucher-firma">
                        <div class="line"></div>
                        <div class="caption">Responsable<br><strong>${firmaResponsable}</strong></div>
                    </div>
                </div>
            </div>
        `;
    }

    function abrirVoucher(egreso) {
        const cont = qs('#egVoucher');
        if (!cont || !egreso) return;

        cont.innerHTML = construirVoucherHTML(egreso);

        if (window.jQuery) {
            jQuery('#egresoPrintModal').modal('show');
        }
    }

    // ===== Eventos globales =====
    document.addEventListener('click', function (e) {
        const chip = e.target.closest('#egTipoChipGroup .eg-chip');
        if (chip) {
            e.preventDefault();
            setTipo(chip.dataset.tipo || 'RECIBO');
            return;
        }

        const pagerLink = e.target.closest('#egPager a[data-page]');
        if (pagerLink) {
            e.preventDefault();
            const p = parseInt(pagerLink.dataset.page, 10);
            if (!isNaN(p)) {
                state.page = p;
                renderTabla();
            }
            return;
        }

        const actionBtn = e.target.closest('[data-action][data-id]');
        if (actionBtn) {
            const id = parseInt(actionBtn.dataset.id, 10);
            const acc = actionBtn.dataset.action;
            const eg = DEMO_EGRESOS.find((x) => x.id === id);
            if (!eg) return;

            if (acc === 'preview') {
                abrirVoucher(eg);
            } else if (acc === 'anular') {
                if (eg.estado === 'ANULADO') return;
                if (!confirm('¿Seguro que deseas anular este egreso (solo demo)?')) return;
                eg.estado = 'ANULADO';
                renderTabla();
            }
        }
    });

    const form = qs('#egForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const datos = tomarDatosFormulario();
            if (!validarDatos(datos)) return;

            const egreso = agregarEgresoDemo(datos);

            showFormAlert(
                'success',
                'Egreso guardado en modo demostración. No se ha registrado en la base de datos.'
            );

            renderTabla();
            abrirVoucher(egreso);
        });
    }

    const btnLimpiar = qs('#egBtnLimpiar');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function () {
            limpiarFormulario();
        });
    }

    const btnVistaPrevia = qs('#egBtnVistaPrevia');
    if (btnVistaPrevia) {
        btnVistaPrevia.addEventListener('click', function () {
            const datos = tomarDatosFormulario();

            if (!datos.concepto) {
                showFormAlert('warning', 'Completa al menos el concepto y el monto para ver una vista previa.');
                return;
            }

            const egDemo = {
                id: 0,
                fecha: datos.fecha || new Date().toISOString(),
                tipo: datos.tipo || 'RECIBO',
                serie: datos.serie,
                numero: datos.numero,
                referencia: datos.referencia,
                beneficiario: datos.benef || '',
                documento: datos.doc || '',
                concepto: datos.concepto,
                monto: datos.monto || 0,
                estado: 'ACTIVO',
            };

            abrirVoucher(egDemo);
        });
    }

    const btnPrintFake = qs('#egBtnPrintFake');
    if (btnPrintFake) {
        btnPrintFake.addEventListener('click', function () {
            alert('Demo: aquí se generaría el PDF real del recibo de egreso.');
        });
    }

    const qInput = qs('#egQ');
    if (qInput) {
        let t;
        qInput.addEventListener('input', function () {
            clearTimeout(t);
            t = setTimeout(function () {
                state.filtroTexto = qInput.value || '';
                state.page = 1;
                renderTabla();
            }, 250);
        });
    }

    const filtroTipo = qs('#egFiltroTipo');
    if (filtroTipo) {
        filtroTipo.addEventListener('change', function () {
            state.filtroTipo = filtroTipo.value || 'todos';
            state.page = 1;
            renderTabla();
        });
    }

    const filtroEstado = qs('#egFiltroEstado');
    if (filtroEstado) {
        filtroEstado.addEventListener('change', function () {
            state.filtroEstado = filtroEstado.value || 'todos';
            state.page = 1;
            renderTabla();
        });
    }

    const concepto = qs('#egConcepto');
    if (concepto) {
        concepto.setAttribute('maxlength', '1000');
        concepto.addEventListener('input', actualizarContadorConcepto);
    }

    // ===== Init (modo demo) =====
    function initEgresosDemo() {
        setTipo('RECIBO');
        limpiarFormulario();
        renderTabla();
        cargarEstadoCajaDemo();
    }

    // El script está al final del body, así que el DOM ya está listo
    initEgresosDemo();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

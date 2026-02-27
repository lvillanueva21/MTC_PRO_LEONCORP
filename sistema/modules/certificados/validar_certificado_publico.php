<?php
// /modules/certificados/validar_certificado_publico.php
//
// Página pública de validación de certificado.
// No requiere login, no incluye el header de AdminLTE.
// Muestra los datos principales del certificado a partir de codigo_qr (token).

require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/funciones_formulario.php';

header('Content-Type: text/html; charset=utf-8');

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

$db   = db();
$cert = null;

if ($token !== '') {
    $sql = "SELECT 
                c.*,
                e.nombre       AS empresa_nombre,
                e.logo_path    AS empresa_logo_path,
                u.nombres      AS usuario_nombres,
                u.apellidos    AS usuario_apellidos,
                cur.nombre     AS curso_nombre,
                cat.codigo     AS categoria_codigo,
                td.codigo      AS tipo_doc_codigo
            FROM cq_certificados c
            LEFT JOIN mtp_empresas e            ON e.id  = c.id_empresa
            LEFT JOIN mtp_usuarios u            ON u.id  = c.id_usuario_emisor
            LEFT JOIN cr_cursos cur             ON cur.id = c.id_curso
            LEFT JOIN cq_categorias_licencia cat ON cat.id = c.id_categoria_licencia
            LEFT JOIN cq_tipos_documento td     ON td.id = c.id_tipo_doc
            WHERE c.codigo_qr = ?
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $cert = $res->fetch_assoc();
    $stmt->close();
}

$existe = is_array($cert);

$tituloPagina   = 'Validación de certificado';
$estadoMostrar  = 'No encontrado';
$empresaNombre  = '';
$codigoCert     = '';
$nombreCli      = '';
$docLabel       = '';
$cursoNombre    = '';
$categoriaTxt   = '';
$fechaEmision   = '';
$fechaInicio    = '';
$fechaFin       = '';
$horasTeor      = '';
$horasPrac      = '';
$creado         = '';
$actualizado    = '';
$emisorNombre   = '';
// URL del logo de la empresa (por defecto uno genérico)
$empresaLogoUrl = defined('BASE_URL')
    ? BASE_URL . '/dist/img/AdminLTELogo.png'
    : '/dist/img/AdminLTELogo.png';

if ($existe) {
    $empresaNombre   = isset($cert['empresa_nombre']) ? (string)$cert['empresa_nombre'] : '';
    $codigoCert      = isset($cert['codigo_certificado']) ? (string)$cert['codigo_certificado'] : '';
    $nombreCli       = trim((string)($cert['nombres_cliente'] ?? '') . ' ' . (string)($cert['apellidos_cliente'] ?? ''));
    $cursoNombre     = isset($cert['curso_nombre']) ? (string)$cert['curso_nombre'] : '';
    $categoriaCodigo = isset($cert['categoria_codigo']) ? (string)$cert['categoria_codigo'] : '';
    $tipoDocCodigo   = isset($cert['tipo_doc_codigo']) ? (string)$cert['tipo_doc_codigo'] : '';
    $docCliente      = isset($cert['documento_cliente']) ? (string)$cert['documento_cliente'] : '';
    $fechaEmisionSql = isset($cert['fecha_emision']) ? (string)$cert['fecha_emision'] : '';
    $fechaInicioSql  = isset($cert['fecha_inicio']) ? (string)$cert['fecha_inicio'] : '';
    $fechaFinSql     = isset($cert['fecha_fin']) ? (string)$cert['fecha_fin'] : '';
    $horasTeor       = (string)($cert['horas_teoricas'] ?? '');
    $horasPrac       = (string)($cert['horas_practicas'] ?? '');
    $creadoSql       = isset($cert['creado']) ? (string)$cert['creado'] : '';
    $actualizadoSql  = isset($cert['actualizado']) ? (string)$cert['actualizado'] : '';
    $estadoBd        = isset($cert['estado']) ? (string)$cert['estado'] : 'Activo';
    $emisorNombre    = trim((string)($cert['usuario_nombres'] ?? '') . ' ' . (string)($cert['usuario_apellidos'] ?? ''));

    // Logo de empresa (mismo criterio que sidebar)
    $empresaLogoPath = isset($cert['empresa_logo_path']) ? trim((string)$cert['empresa_logo_path']) : '';
    if ($empresaLogoPath !== '' && defined('BASE_URL')) {
        $empresaLogoUrl = BASE_URL . '/' . ltrim($empresaLogoPath, '/');
    }

    // Fechas display d/m/Y
    $dtEmision = null;
    if ($fechaEmisionSql !== '') {
        $dtEmision = DateTime::createFromFormat('Y-m-d', $fechaEmisionSql);
        if ($dtEmision instanceof DateTime) {
            $fechaEmision = cf_formatear_fecha_esp($dtEmision);
        }
    }
    if ($fechaInicioSql !== '') {
        $dtIni = DateTime::createFromFormat('Y-m-d', $fechaInicioSql);
        if ($dtIni instanceof DateTime) {
            $fechaInicio = cf_formatear_fecha_esp($dtIni);
        }
    }
    if ($fechaFinSql !== '') {
        $dtFin = DateTime::createFromFormat('Y-m-d', $fechaFinSql);
        if ($dtFin instanceof DateTime) {
            $fechaFin = cf_formatear_fecha_esp($dtFin);
        }
    }

    // Fechas de auditoría
    if ($creadoSql !== '') {
        $dtC = new DateTime($creadoSql);
        $creado = $dtC->format('d/m/Y H:i:s');
    }
    if ($actualizadoSql !== '') {
        $dtA = new DateTime($actualizadoSql);
        $actualizado = $dtA->format('d/m/Y H:i:s');
    }

    // Estado (Activo / Inactivo / Vencido) usando misma lógica
    $estadoMostrar = $estadoBd;
    if ($estadoBd !== 'Inactivo') {
        if ($estadoBd === 'Vencido') {
            $estadoMostrar = 'Vencido';
        } else {
            if ($dtEmision instanceof DateTime) {
                $limite = clone $dtEmision;
                $limite->modify('+1 year');
                $hoy = new DateTime('now');
                if ($hoy >= $limite) {
                    $estadoMostrar = 'Vencido';
                } else {
                    $estadoMostrar = 'Activo';
                }
            } else {
                if ($estadoBd === '') {
                    $estadoMostrar = 'Activo';
                }
            }
        }
    }

    // Documento label
    if ($tipoDocCodigo !== '' && $docCliente !== '') {
        $docLabel = $tipoDocCodigo . ': ' . $docCliente;
    } else {
        $docLabel = $docCliente;
    }

    // Categoría texto
    if ($categoriaCodigo !== '') {
        $categoriaTxt = $categoriaCodigo;
    }

    $tituloPagina = 'Certificado ' . $codigoCert;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?php echo h($tituloPagina); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      margin: 0;
      padding: 0;
      background: #f3f4f6;
      color: #111827;
    }
    .cert-public-wrapper {
      max-width: 760px;
      margin: 24px auto;
      padding: 16px;
    }
    .cert-public-card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(15,23,42,0.12);
      padding: 20px 20px 24px 20px;
    }
    .cert-public-header {
      border-bottom: 1px solid #e5e7eb;
      padding-bottom: 12px;
      margin-bottom: 16px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
    }
    .cert-public-header-main {
      flex: 1 1 auto;
      min-width: 0;
    }
    .cert-public-title {
      font-size: 20px;
      font-weight: 700;
      margin: 0 0 4px 0;
      color: #111827;
    }
    .cert-public-subtitle {
      font-size: 13px;
      color: #6b7280;
    }
    .cert-public-subtitle strong {
      color: #374151;
    }
    .cert-public-status {
      margin-top: 8px;
    }
    .cert-public-company-logo {
      flex: 0 0 auto;
      width: 72px;
      height: 72px;
      border-radius: 50%;
      overflow: hidden;
      background: #f9fafb;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 0 1px rgba(156,163,175,.4);
    }
    .cert-public-company-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
      border: 0;
    }
    .badge-status {
      display: inline-block;
      padding: 3px 10px;
      font-size: 12px;
      border-radius: 999px;
      font-weight: 600;
    }
    .status-activo {
      background: #dcfce7;
      color: #166534;
    }
    .status-inactivo {
      background: #e5e7eb;
      color: #374151;
    }
    .status-vencido {
      background: #fee2e2;
      color: #b91c1c;
    }
    .cert-public-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px 24px;
      font-size: 14px;
      margin-top: 8px;
    }
    .cert-public-block {
      margin-bottom: 4px;
    }
    .cert-public-label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .04em;
      color: #6b7280;
      margin-bottom: 2px;
    }
    .cert-public-value {
      font-size: 14px;
      font-weight: 500;
      color: #111827;
    }
    .cert-public-footer {
      margin-top: 16px;
      font-size: 12px;
      color: #6b7280;
      border-top: 1px solid #e5e7eb;
      padding-top: 8px;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
    }
    .cert-public-error {
      background: #fef2f2;
      border-radius: 12px;
      padding: 16px;
      text-align: center;
      color: #b91c1c;
      box-shadow: 0 8px 24px rgba(15,23,42,0.12);
    }
    .cert-public-error h1 {
      font-size: 18px;
      margin: 0 0 8px 0;
    }
    .cert-public-error p {
      margin: 0;
      font-size: 13px;
    }
    @media (max-width: 640px) {
      .cert-public-card {
        padding: 16px 14px 18px 14px;
      }
      .cert-public-grid {
        grid-template-columns: minmax(0, 1fr);
      }
      .cert-public-header {
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>
<div class="cert-public-wrapper">
<?php if (!$existe): ?>
  <div class="cert-public-error">
    <h1>Certificado no encontrado</h1>
    <p>El código escaneado no corresponde a un certificado válido o ha sido retirado del sistema.</p>
  </div>
<?php else: ?>
  <div class="cert-public-card">
    <div class="cert-public-header">
      <div class="cert-public-header-main">
        <h1 class="cert-public-title">
          Certificado <?php echo h($codigoCert); ?>
        </h1>
        <div class="cert-public-subtitle">
          <?php if ($empresaNombre !== ''): ?>
            <span><strong>Empresa:</strong> <?php echo h($empresaNombre); ?></span>
            &nbsp;•&nbsp;
          <?php endif; ?>
          <span><strong>Emitido a:</strong> <?php echo h($nombreCli); ?></span>
        </div>
        <div class="cert-public-status">
          <?php
            $estadoClass = 'status-activo';
            $estadoUpper = strtoupper($estadoMostrar);
            if ($estadoUpper === 'INACTIVO') {
                $estadoClass = 'status-inactivo';
            } elseif ($estadoUpper === 'VENCIDO') {
                $estadoClass = 'status-vencido';
            }
          ?>
          <span class="badge-status <?php echo $estadoClass; ?>">
            <?php echo h($estadoMostrar); ?>
          </span>
        </div>
      </div>

      <?php if (!empty($empresaLogoUrl)): ?>
        <div class="cert-public-company-logo">
          <img src="<?php echo h($empresaLogoUrl); ?>" alt="Logo de la empresa">
        </div>
      <?php endif; ?>
    </div>

    <div class="cert-public-grid">
      <div class="cert-public-block">
        <div class="cert-public-label">Curso</div>
        <div class="cert-public-value"><?php echo h($cursoNombre); ?></div>
      </div>

      <div class="cert-public-block">
        <div class="cert-public-label">Documento</div>
        <div class="cert-public-value"><?php echo h($docLabel); ?></div>
      </div>

      <div class="cert-public-block">
        <div class="cert-public-label">Categoría</div>
        <div class="cert-public-value">
          <?php echo $categoriaTxt !== '' ? h($categoriaTxt) : '—'; ?>
        </div>
      </div>

      <div class="cert-public-block">
        <div class="cert-public-label">Fecha de emisión</div>
        <div class="cert-public-value">
          <?php echo $fechaEmision !== '' ? h($fechaEmision) : '—'; ?>
        </div>
      </div>

      <div class="cert-public-block">
        <div class="cert-public-label">Fecha inicio</div>
        <div class="cert-public-value">
          <?php echo $fechaInicio !== '' ? h($fechaInicio) : '—'; ?>
        </div>
      </div>

      <div class="cert-public-block">
        <div class="cert-public-label">Fecha fin</div>
        <div class="cert-public-value">
          <?php echo $fechaFin !== '' ? h($fechaFin) : '—'; ?>
        </div>
      </div>

      <div class="cert-public-block">
        <div class="cert-public-label">Horas teóricas</div>
        <div class="cert-public-value">
          <?php echo $horasTeor !== '' ? h($horasTeor) : '0'; ?>
        </div>
      </div>

      <div class="cert-public-block">
        <div class="cert-public-label">Horas prácticas</div>
        <div class="cert-public-value">
          <?php echo $horasPrac !== '' ? h($horasPrac) : '0'; ?>
        </div>
      </div>
    </div>

    <div class="cert-public-footer">
      <div>
        <?php if ($creado !== ''): ?>
          <span><strong>Creado:</strong> <?php echo h($creado); ?></span>
        <?php endif; ?>
        <?php if ($actualizado !== ''): ?>
          &nbsp;•&nbsp;
          <span><strong>Actualizado:</strong> <?php echo h($actualizado); ?></span>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($emisorNombre !== ''): ?>
          <span><strong>Emitido por:</strong> <?php echo h($emisorNombre); ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>
</body>
</html>

<?php
// Ver 08-03-26
// modules/aula_virtual/form_fast.php
date_default_timezone_set('America/Lima');

$code = trim((string)($_GET['c'] ?? ''));
if ($code === '') {
  http_response_code(400);
  exit('Parametro c requerido.');
}

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim($scriptDir, '/');
if ($scriptDir === '' || $scriptDir === '.') $scriptDir = '/modules/aula_virtual';

$cfg = [
  'mode' => 'FAST',
  'code' => $code,
  'apiUrl' => $scriptDir . '/api_formularios_fast.php',
  'pdfUrl' => $scriptDir . '/pdf_fast.php',
  'storageKey' => 'av_exam_fast_token_' . $code,
];
$cfgJson = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($cfgJson === false) {
  $cfgJson = '{"mode":"FAST","code":"","apiUrl":"","pdfUrl":"","storageKey":"av_exam_fast_token_default"}';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Formulario FAST - Aula Virtual</title>
  <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="./aula_virtual_formularios_resolver.css?v=1">
</head>
<body class="hold-transition layout-top-nav">
  <div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
      <div class="container">
        <a href="#" class="navbar-brand">
          <span class="brand-text font-weight-light">Aula Virtual - FAST</span>
        </a>
      </div>
    </nav>

    <div class="content-wrapper">
      <section class="content">
        <div class="container">
          <div id="avExamRoot">
            <div id="avExamNotice" class="alert d-none" role="alert"></div>

            <div class="card" id="avExamLanding">
              <div class="card-header">
                <div class="avex-head">
                  <h3 class="card-title m-0" id="avExamTitle">Cargando examen...</h3>
                </div>
              </div>
              <div class="card-body">
                <p class="mb-2" id="avExamDesc"></p>
                <div class="avex-meta mb-3" id="avExamRules"></div>

                <div id="avExamFastFields" class="avex-hidden">
                  <div class="row">
                    <div class="col-12 col-md-4 mb-2">
                      <label class="form-label mb-1" for="avExamTipoDoc">Tipo de documento</label>
                      <select class="form-control" id="avExamTipoDoc"></select>
                    </div>
                    <div class="col-12 col-md-8 mb-2">
                      <label class="form-label mb-1" for="avExamNroDoc">Numero de documento (obligatorio)</label>
                      <input type="text" class="form-control" id="avExamNroDoc" maxlength="20">
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12 col-md-6 mb-2 avex-hidden" id="avExamNombresWrap">
                      <label class="form-label mb-1" for="avExamNombres">Nombres</label>
                      <input type="text" class="form-control" id="avExamNombres" maxlength="120">
                    </div>
                    <div class="col-12 col-md-6 mb-2 avex-hidden" id="avExamApellidosWrap">
                      <label class="form-label mb-1" for="avExamApellidos">Apellidos</label>
                      <input type="text" class="form-control" id="avExamApellidos" maxlength="120">
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12 col-md-6 mb-2 avex-hidden" id="avExamCelularWrap">
                      <label class="form-label mb-1" for="avExamCelular">Celular</label>
                      <input type="text" class="form-control" id="avExamCelular" maxlength="20">
                    </div>
                  </div>

                  <div class="mb-2 avex-hidden" id="avExamCategoriasWrap">
                    <label class="form-label mb-1">Categorias de conductor</label>
                    <div class="avex-cats" id="avExamCategoriasList"></div>
                  </div>
                </div>

                <button type="button" class="btn btn-primary" id="avExamStartBtn">Comenzar</button>
              </div>
            </div>

            <div class="card avex-hidden" id="avExamRun">
              <div class="card-header">
                <div class="avex-head">
                  <div class="avex-time" id="avExamTimer">Tiempo: --:--</div>
                  <div class="avex-meta" id="avExamStatusText">Intento en progreso</div>
                </div>
              </div>
              <div class="card-body">
                <div id="avExamQuestions"></div>
                <div class="avex-actions">
                  <button type="button" class="btn btn-outline-secondary" id="avExamSaveBtn">Guardar</button>
                  <button type="button" class="btn btn-success" id="avExamSubmitBtn">Enviar examen</button>
                </div>
                <div class="avex-saved mt-2" id="avExamLastSaved">Guardado: -</div>
              </div>
            </div>

            <div class="card avex-hidden" id="avExamFinal">
              <div class="card-body">
                <h4 class="mb-2">Resultado</h4>
                <p class="mb-2" id="avExamFinalMsg"></p>
                <div class="avex-final-score mb-3" id="avExamFinalScore"></div>
                <div class="avex-actions">
                  <a href="#" target="_blank" class="btn btn-outline-primary avex-hidden" id="avExamPdfBtn">Descargar PDF</a>
                  <button type="button" class="btn btn-outline-secondary" id="avExamBackBtn">Volver al inicio</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <script>
    window.avExamResolverConfig = <?= $cfgJson ?>;
  </script>
  <script src="../../plugins/jquery/jquery.min.js"></script>
  <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="./examen_resolver.js?v=1"></script>
  <script src="./form_fast.js?v=1"></script>
</body>
</html>

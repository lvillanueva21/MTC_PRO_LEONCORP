<?php
require __DIR__ . '/includes/auth.php';

if (isAuthenticated()) {
    header('Location: ' . BASE_URL . '/inicio.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = (string)($_POST['clave'] ?? '');

    if ($usuario === '' || $clave === '') {
        $err = 'Ingresa usuario y contraseña.';
    } else {
        $r = login($usuario, $clave);
        if ($r['ok']) {
            header('Location: ' . BASE_URL . '/inicio.php');
            exit;
        } else {
            $err = $r['error'] ?? 'No se pudo iniciar sesión.';
        }
    }
}

$notice = '';
if (isset($_GET['m']) && $_GET['m'] === 'sesion') {
    $notice = 'Inicia sesión para continuar.';
}
?>
<?php
// ----- Saludo aleatorio estilo "siempre algo nuevo" -----
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
date_default_timezone_set('America/Lima');

// Saludo contextual por hora
$h = (int)date('G');
$saludo = ($h < 12) ? '¡Buenos días!' : (($h < 19) ? '¡Buenas tardes!' : '¡Buenas noches!');

// Pool de emojis y plantillas
$emojis = ['🌟','🚀','💡','✨','👋','🧠','⚡️','🔐','📄','✅'];
$plantillas = [
    // Profesional / institucional
    '{saludo} {emoji} Tu compromiso hace la diferencia cada día.',
    '{saludo} {emoji} Sigamos trabajando por un mejor servicio.',
    'Bienvenid@ {emoji} Gracias por contribuir con la calidad y eficiencia del sistema.',
    '{saludo} {emoji} Cada acción cuenta para lograr resultados.',
    '¡Excelente jornada por delante! {emoji}',
    '{emoji} Recuerda: la precisión también es parte del progreso.',

    // Cercano / amigable
    '{saludo} {emoji} Qué gusto verte de nuevo.',
    '¡Hola! {emoji} Esperamos que hoy tengas un gran día.',
    '{saludo} {emoji} Siempre es bueno verte por aquí.',
    'Bienvenid@ de nuevo {emoji} ¡Vamos con todo hoy!',
    '{emoji} Gracias por seguir confiando en nosotros.',
    '{saludo} {emoji} Tu trabajo impulsa grandes resultados.',

    // Inspiracional / energético
    '{saludo} {emoji} Cada día es una oportunidad para mejorar.',
    '{emoji} Hoy es un buen día para avanzar un paso más.',
    '{saludo} {emoji} El éxito comienza con un inicio de sesión.',
    '{emoji} ¡Activa tu potencial y haz que cuente!',
    '{saludo} {emoji} Grandes cosas comienzan con pequeños clics.',
    '{emoji} Inspira, mejora, impacta. ¡Vamos con todo!',

    // Seguridad / responsabilidad (manteniendo solo las que sí te gustaron)
    '{emoji} Recuerda: la seguridad comienza contigo.',
    '{saludo} {emoji} Verifica tus credenciales antes de continuar.',

    // Neutras que ya tenías y funcionan bien
    '{saludo} 👋 ¿Listo para continuar?',
    '¡Hola! {emoji} Gracias por usar el sistema.',
    '¡Qué gusto verte por aquí! {emoji}',
    '{saludo} {emoji} Hoy es un buen día para avanzar.',
];


// (Opcional) Evitar repetir el último mensaje en la misma sesión
$idx = array_rand($plantillas);
if (isset($_SESSION['last_welcome_idx']) && count($plantillas) > 1) {
    $tries = 0;
    while ($idx === $_SESSION['last_welcome_idx'] && $tries < 5) {
        $idx = array_rand($plantillas);
        $tries++;
    }
}
$_SESSION['last_welcome_idx'] = $idx;

// Arma el mensaje final
$mensajeBienvenida = str_replace(
    ['{saludo}','{emoji}'],
    [$saludo, $emojis[array_rand($emojis)]],
    $plantillas[$idx]
);
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <title>Login | Sistema</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Tipografía + Iconos -->
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos del login anterior -->
    <link rel="stylesheet" href="css/style.css">

    <!-- Overrides: desactivar labels flotantes + estilos tooltip/info/eye -->
    <style>
      /* Sin labels flotantes */
      .form-group { position: relative; margin-bottom: 1.25rem; }
      .form-control-placeholder { display: none !important; }

      /* Label fijo */
      .form-label-fixed {
        display: flex; align-items: center; gap: .4rem;
        font-weight: 600; margin-bottom: .4rem;
      }
      /* Icono info */
      .info-icon {
        cursor: help; font-size: .95rem; line-height: 1;
        color: #6c757d;
      }
      .info-icon:hover, .info-icon:focus { color: #495057; }

      /* Ojo de contraseña */
.field-icon {
  position: absolute; top: 50%; right: .75rem; transform: translateY(-50%);
  z-index: 2; cursor: pointer; user-select: none; color: #6c757d;
}
      #password-field { padding-right: 2.25rem; }
.text-decoration-none:hover { text-decoration: underline !important; }
    </style>
  </head>
  <body>
    <section class="ftco-section">
      <div class="container">
        <div class="row justify-content-center">
            <div class="row justify-content-center">
  <div class="col-md-6 text-center mb-5">
    <h2 class="heading-section"><?= htmlspecialchars($mensajeBienvenida) ?></h2>
  </div>
</div>
        </div>

        <div class="row justify-content-center">
          <div class="col-md-7 col-lg-5">
            <div class="wrap">
              <!-- Panel de imagen lateral -->
              <div class="img" style="background-image: url(assets/img/MTC_PRO_inline.webp);"></div>

              <!-- Tarjeta de login -->
              <div class="login-wrap p-4 p-md-5">
                <div class="d-flex align-items-center mb-2">
                  <div class="w-100">
                    <h4 class="mb-0">Iniciar sesión</h4>
                  </div>
                  <div class="w-100">
                    <p class="social-media d-flex justify-content-end m-0">  <a href="https://wa.me/51964881841"      class="social-icon d-flex align-items-center justify-content-center"      title="WhatsApp de soporte"      target="_blank"      rel="noopener noreferrer">    <span class="fa fa-whatsapp"></span>  </a>  <a href="https://sso.mtc.gob.pe/MTC.STS/Login?ReturnUrl=%2fMTC.STS%2f%3fwa%3dwsignin1.0%26wtrealm%3dhttps%253a%252f%252fsso.mtc.gob.pe%252fManager%252f%26wfresh%3d0%26wctx%3drm%253d0%2526id%253dpassive%2526ru%253d%25252fManager%25252fHome%25252fIndex%26wct%3d2025-10-07T15%253a09%253a45Z%26wreply%3dhttps%253a%252f%252fsso.mtc.gob.pe%252fManager%252f&wa=wsignin1.0&wtrealm=https%3a%2f%2fsso.mtc.gob.pe%2fManager%2f&wfresh=0&wctx=rm%3d0%26id%3dpassive%26ru%3d%252fManager%252fHome%252fIndex&wct=2025-10-07T15%3a09%3a45Z&wreply=https%3a%2f%2fsso.mtc.gob.pe%2fManager%2f"      class="social-icon d-flex align-items-center justify-content-center"      title="Sistema MTC"      target="_blank"      rel="noopener noreferrer">    <span class="fa fa-car"></span>  </a></p></span></a></span></a></p>
                  </div>
                </div>

                <?php if ($notice): ?>
                  <div class="alert alert-info py-2 mb-3"><?= htmlspecialchars($notice) ?></div>
                <?php endif; ?>

                <?php if ($err): ?>
                  <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>

                <!-- Mismos names/validaciones; solo presentación -->
                <form method="post" class="signin-form" autocomplete="off" novalidate>
                  <div class="form-group mt-3">
                    <label for="usuario" class="form-label-fixed">
                      Usuario (DNI/CE)
                      <span
                        class="fa fa-info-circle info-icon"
                        tabindex="0"
                        role="button"
                        data-bs-toggle="tooltip"
                        data-bs-placement="right"
                        data-bs-title="Tu usuario es tu documento de Identidad."
                        aria-label="Más información sobre el campo Usuario"
                      ></span>
                    </label>
                    <input
                      id="usuario"
                      type="text"
                      name="usuario"
                      class="form-control"
                      maxlength="11"
                      pattern="\d{8,11}"
                      autocomplete="username"
                      required
                      autofocus
                    >
                  </div>
<div class="form-group">
  <label for="password-field" class="form-label-fixed">
    Contraseña
    <span
      class="fa fa-info-circle info-icon"
      tabindex="0"
      role="button"
      data-bs-toggle="tooltip"
      data-bs-placement="right"
      data-bs-title="No compartas tu contraseña."
      aria-label="Más información sobre el campo Contraseña"
    ></span>
  </label>

  <div class="position-relative">
    <input
      id="password-field"
      type="password"
      name="clave"
      class="form-control"
      minlength="6"
      autocomplete="current-password"
      required
    >
    <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password" title="Mostrar/Ocultar"></span>
  </div>
</div>
                  <div class="form-group">
                    <button class="form-control btn btn-primary rounded submit px-3" type="submit">
                      Ingresar
                    </button>
                  </div>
<!-- Accesos de soporte (texto con links) -->
<div class="form-group d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-2 small">

  <a
    class="text-success text-decoration-none d-inline-flex align-items-center"
    href="https://wa.me/51964881841?text=Hola%2C%20necesito%20apoyo%20del%20%C3%81rea%20de%20Soporte."
    target="_blank"
    rel="noopener noreferrer"
    title="Contactar a soporte por WhatsApp"
  >
    <span class="fa fa-whatsapp me-1" aria-hidden="true"></span>
    Contactar a soporte
  </a>

  <a
    class="text-secondary text-decoration-none d-inline-flex align-items-center"
    href="https://wa.me/51964881841?text=Hola%2C%20quiero%20recuperar%20mi%20contrase%C3%B1a%2C%20mi%20DNI%20y%2Fo%20nombre%20completo%20es:"
    target="_blank"
    rel="noopener noreferrer"
    title="Solicitar recuperación de contraseña por WhatsApp"
  >
    <span class="fa fa-unlock-alt me-1" aria-hidden="true"></span>
    Recuperar contraseña
  </a>

</div>

                </form>
                <p class="text-center text-muted mt-3 mb-0 small">
                  © <?= date('Y') ?> — LuigiSistemas - Todos los derechos reservados.
                </p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- Bootstrap JS (incluye Popper para tooltips) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JS: toggle de contraseña + activación de tooltips -->
    <script>
      // Toggle contraseña
      (function () {
        var toggler = document.querySelector('.toggle-password');
        if (!toggler) return;
        toggler.addEventListener('click', function () {
          var target = document.querySelector(this.getAttribute('toggle'));
          if (!target) return;
          var isPass = target.getAttribute('type') === 'password';
          target.setAttribute('type', isPass ? 'text' : 'password');
          this.classList.toggle('fa-eye');
          this.classList.toggle('fa-eye-slash');
        });
      })();

      // Tooltips Bootstrap (hover y focus)
      (function () {
        var triggers = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        triggers.forEach(function (el) {
          new bootstrap.Tooltip(el, { trigger: 'hover focus' });
        });
      })();
    </script>
  </body>
</html>

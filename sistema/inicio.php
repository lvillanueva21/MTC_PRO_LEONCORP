<?php
require __DIR__ . '/includes/auth.php';
requireAuth();

// Cambio de rol activo
if (isset($_GET['rol'])) {
    if (switchRole($_GET['rol'])) {
        header('Location: ' . BASE_URL . '/inicio.php');
        exit;
    }
}

$u         = currentUser();              // debe traer id, usuario, id_empresa, rol_activo
$rolActivo = $u['rol_activo'] ?? 'Invitado';
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="content-wrapper">
  <!-- Contenido principal -->
  <section class="content">
    <div class="container-fluid">

      <?php
      // Router por rol (cada rol tendrá su propio include)
      switch ($rolActivo) {
        case 'Administración':
          include __DIR__ . '/dashboard/administracion/comunicados.php';
          break;

        case 'Recepción':
          echo '<div class="alert alert-secondary">Accesos rápidos de Recepción…</div>';
          break;

        case 'COTI':
          echo '<div class="alert alert-secondary">Módulos de COTI…</div>';
          break;

        case 'Central':
          echo '<div class="alert alert-secondary">Vista Central…</div>';
          break;

        case 'Gerente':
          echo '<div class="alert alert-secondary">Resumen Gerencial…</div>';
          break;
          
        case 'Cliente':
          include __DIR__ . '/dashboard/cliente/publicidades.php';
          break;

        default:
          echo '<div class="alert alert-secondary">Rol avanzado: acceso total a módulos y configuración.</div>';
          break;
      }
      ?>

    </div>
  </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

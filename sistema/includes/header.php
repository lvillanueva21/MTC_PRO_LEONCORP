<?php
/* /includes/header.php - AdminLTE header */
require_once __DIR__ . '/auth.php';

$u         = currentUser();
$roles     = $u['roles'] ?? [];
$rolActivo = $u['rol_activo'] ?? '';
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MTC Pro</title>
    <!-- Fonts & Styles -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet"
          href="<?= BASE_URL ?>/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet"
          href="<?= BASE_URL ?>/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet"
          href="<?= BASE_URL ?>/dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Orbitron:wght@500&family=Raleway:wght@600&display=swap"
          rel="stylesheet">
    <link rel="icon" href="<?= BASE_URL ?>/includes/logo_mouse.ico">
</head>

<body class="hold-transition light-mode sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__wobble"
             src="<?= BASE_URL ?>/includes/logo_mouse.png"
             alt="Logo" height="60" width="60">
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark">
        <!-- Left navbar -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?= BASE_URL ?>/inicio.php" class="nav-link">Inicio</a>
            </li>
        </ul>

        <!-- Right navbar -->
        <ul class="navbar-nav ml-auto">
            <!-- Selector de roles -->
            <li class="nav-item d-none d-md-block">
                <?php if ($roles && count($roles) > 1): ?>
                    <form method="get" action="<?= BASE_URL ?>/inicio.php"
                          class="d-flex align-items-center gap-2 m-0">
                        <label class="form-label m-0 small text-light me-2">Rol:</label>
                        <select class="form-select form-select-sm bg-dark text-white border-0"
                                name="rol" onchange="this.form.submit()">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= htmlspecialchars($r) ?>"
                                        <?= $r === $rolActivo ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php else: ?>
                    <span class="badge bg-primary me-2">
                        <?= htmlspecialchars($rolActivo) ?>
                    </span>
                <?php endif; ?>
            </li>

            <!-- Usuario -->
            <li class="nav-item">
                <a class="nav-link" href="#" title="Usuario">
                    <i class="far fa-user"></i>
                    <span class="d-none d-sm-inline">
                        <?= htmlspecialchars($u['usuario'] ?? '') ?>
                    </span>
                </a>
            </li>

            <!-- Logout -->
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php" title="Salir">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>
        </ul>
    </nav>

    <?php include __DIR__ . '/sidebar.php'; ?>

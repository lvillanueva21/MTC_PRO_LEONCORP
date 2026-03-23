<?php
// /includes/control_especial_catalog.php
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(403);
  exit('Acceso directo no permitido.');
}

/**
 * Catalogo manual (lista blanca) de modulos clasicos elegibles
 * para permiso especial de usuarios con rol Control.
 */
function control_especial_catalog_modulos(): array {
  return [
    'reporte_ventas' => [
      'slug' => 'reporte_ventas',
      'path' => 'modules/reporte_ventas/',
      'label' => 'Ventas',
    ],
    'reporte_abonos' => [
      'slug' => 'reporte_abonos',
      'path' => 'modules/reporte_abonos/',
      'label' => 'Abonos',
    ],
    'reporte_clientes' => [
      'slug' => 'reporte_clientes',
      'path' => 'modules/reporte_clientes/',
      'label' => 'Clientes',
    ],
    'caja' => [
      'slug' => 'caja',
      'path' => 'modules/caja/',
      'label' => 'Caja',
    ],
    'egresos' => [
      'slug' => 'egresos',
      'path' => 'modules/egresos/',
      'label' => 'Egresos',
    ],
  ];
}

function control_especial_slug_is_valid(string $slug): bool {
  return (bool)preg_match('/^[a-z0-9_]+$/', $slug);
}

function control_especial_catalog_has_slug(string $slug): bool {
  $slug = trim($slug);
  if ($slug === '' || !control_especial_slug_is_valid($slug)) {
    return false;
  }
  $catalog = control_especial_catalog_modulos();
  return isset($catalog[$slug]);
}

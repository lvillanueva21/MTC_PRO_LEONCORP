<?php
/**
 * /includes/menu_matrix.php
 * Matriz de menú por ID de rol (mtp_roles.id). No toca BD.
 * roles: '*' (todos) o array de IDs [1,4,...]
 * when: callable opcional fn($u)=>bool para condiciones extra (ej. solo usuario id=1)
 *
 * IDs actuales:
 * 1 Desarrollo, 2 Control, 3 Recepción, 4 Administración, 5 COTI, 6 Gerente, 7 Cliente
 */

// Alias para evitar "magic numbers" (solo aquí dentro)
$R = [
  'DES' => 1, // Desarrollo
  'CON' => 2, // Control
  'REC' => 3, // Recepción
  'ADM' => 4, // Administración
  'COT' => 5, // COTI
  'GER' => 6, // Gerente
  'CLI' => 7, // Cliente
];

$RA = [$R['REC'], $R['ADM']];

return [

  // ==========================
  // ÍTEMS COMUNES (para todos)
  // ==========================
  'common' => [
    ['path'=>'/inicio.php','icon'=>'fas fa-tachometer-alt','label'=>'Dashboard','roles'=>'*'],
  ],
  
  // ======================================
  // ÍTEMS POR ROL / MULTIROL (segmentados)
  // ======================================
  'items' => [

    // ---------- Desarrollo (1) y Gerente (6) ----------
    // 👉 Cambio: Concedemos acceso a Consola a GERENTE también.
    ['path'=>'/modules/consola/','icon'=>'fas fa-cog','label'=>'Consola','roles'=>[$R['DES'], $R['GER']]],

    // Módulos de Desarrollo
    ['path'=>'/modules/usuarios/','icon'=>'fas fa-user-cog','label'=>'Usuarios','roles'=>[$R['DES']]],
    ['path'=>'/modules/certificados/','icon'=>'fas fa-file-signature','label'=>'Certificados','roles'=>[$R['DES']]],

    // Exclusivos de Desarrollo para usuario ID=1
    ['path'=>'/modules/inventario/','icon'=>'fas fa-boxes','label'=>'Inventario','roles'=>[$R['DES']], 'when'=>fn($u)=>($u['id']??0)===1],
    ['path'=>'/modules/camaras/','icon'=>'fas fa-video','label'=>'Cámaras','roles'=>[$R['DES']], 'when'=>fn($u)=>($u['id']??0)===1],

    // ---------- Control (2) ----------
    ['path'=>'/modules/reportes/','icon'=>'fas fa-clipboard-check','label'=>'Reportes','roles'=>[$R['CON']]],
    // ['path'=>'/modules/certificados/','icon'=>'fas fa-file-signature','label'=>'Certificados','roles'=>[$R['CON']]],

    // ---------- Recepción (3) ----------
    ['path'=>'modules/caja/','icon'=>'fas fa-cash-register','label'=>'Caja','roles'=>[$R['REC']]],

    // ---------- Administración (4) ----------
    ['path'=>'modules/caja/','icon'=>'fas fa-cash-register','label'=>'Caja','roles'=>[$R['ADM']]],

    // ---------- COTI (5) ----------
    ['path'=>'/modules/hardware/','icon'=>'fas fa-tools','label'=>'Hardware','roles'=>[$R['COT']]],

    // ---------- Gerente (6) ----------
    ['path'=>'/modules/reportes/','icon'=>'fas fa-chart-bar','label'=>'Reportes','roles'=>[$R['GER']]],

    // ---------- Cliente (7) ----------
    //['path'=>'/modules/cursos/','icon'=>'fas fa-book-open','label'=>'Cursos','roles'=>[$R['CLI']]],
    
    // ---------- Multirol (Cliente, Desarrollo, Administración, Gerente) ----------
    ['path'=>'/modules/aula_virtual/','icon'=>'fas fa-graduation-cap','label'=>'Aula Virtual','roles'=>[$R['CLI'],$R['DES'],$R['GER']]],
    ['path'=>'/modules/examen/','icon'=>'fas fa-graduation-cap','label'=>'Examen','roles'=>[$R['DES'],$R['GER']]],
    ['path'=>'/modules/ventas/','icon'=>'fas fa-cash-register','label'=>'Ventas','roles'=>[$R['DES'],$R['GER']]],
    
    // ---------- Multirol (Recepción y Administración) ----------
['label'=>'FINANZAS','icon'=>'fas fa-coins','roles'=>$RA,'children'=>[['path'=>'modules/ventas/','icon'=>'fas fa-shopping-cart','label'=>'Ventas'],
['path'=>'modules/egresos/','icon'=>'fas fa-wallet','label'=>'Egresos'],['path'=>'modules/clientes/','icon'=>'fas fa-users','label'=>'Clientes'],['path'=>'modules/alerta/','icon'=>'fas fa-bell','label'=>'Alertas']]],
['label'=>'MTC','icon'=>'fas fa-landmark','roles'=>$RA,'children'=>[['path'=>'modules/conductores/','icon'=>'fas fa-id-card','label'=>'Conductores'],['path'=>'modules/usuarios_mtc/','icon'=>'fas fa-user-cog','label'=>'Usuarios MTC'],['path'=>'modules/inventario/','icon'=>'fas fa-boxes','label'=>'Inventario']]],
['label'=>'AULA VIRTUAL','icon'=>'fas fa-graduation-cap','roles'=>$RA,'children'=>[['path'=>'modules/certificados/','icon'=>'fas fa-file-signature','label'=>'Certificados'],['path'=>'modules/cursos/','icon'=>'fas fa-book-open','label'=>'Cursos']]],

    // ==== PLANTILLAS (copia/pega y descomenta) ====
    // ['path'=>'/modules/finanzas/','icon'=>'fas fa-coins','label'=>'Finanzas','roles'=>[$R['ADM']]],       // Administración
    // ['path'=>'/modules/tickets/','icon'=>'fas fa-headset','label'=>'Tickets','roles'=>[$R['COT']]],       // COTI
    // ['path'=>'/modules/auditorias/','icon'=>'fas fa-search-plus','label'=>'Auditorías','roles'=>[$R['CON']]], // Control
    // ['path'=>'/modules/mis_datos/','icon'=>'fas fa-id-card','label'=>'Mis Datos','roles'=>[$R['CLI']]],   // Cliente
    // ['path'=>'/modules/mi_modulo/','icon'=>'fas fa-cubes','label'=>'Mi Módulo','roles'=>[$R['DES'],$R['COT']]],   // Múltiples
    // ['path'=>'/modules/privado/','icon'=>'fas fa-user-shield','label'=>'Área Privada','roles'=>[$R['DES']], 'when'=>fn($u)=>($u['id']??0)===1],
  ],
];

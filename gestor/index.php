<?php
// gestor/index.php
include 'db.php'; // Conexión a la base de datos

// Obtener todas las tablas
$sql = "SHOW TABLES";
$resultado = $conexion->query($sql);
$tablas = [];

if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_array()) {
        $tablas[] = $fila[0]; // Nombre de la tabla
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Explorador de Tablas</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
        }
        .sidebar {
    width: 250px;
    min-width: 250px;
    max-width: 250px;
    flex-shrink: 0;
    background-color: #1e272e;
    color: white;
    padding: 20px;
    overflow-y: auto;
}
        .sidebar h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .tabla:hover {
            background-color: #808e9b;
        }
        .contenido {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
.divSuperior {
    flex: 1;
    padding: 20px;
    border-bottom: 1px solid #ccc;
    background-color: #f1f2f6;
    overflow: hidden; /* Evita scroll externo */
    display: flex;
    flex-direction: column;
}

#dataTableResult {
    flex: 1;
    overflow: auto; /* Scroll vertical/horizontal solo aquí */
    max-height: 100%;
}
        .boton-flotante {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #0984e3;
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .boton-flotante:hover {
            background-color: #74b9ff;
        }
        /* === NUEVO: resaltar la tabla seleccionada en la barra === */
.tabla.activa {
    background-color: #2ecc71 !important;
    color: #fff;
    font-weight: bold;
    outline: 2px solid rgba(255,255,255,0.2);
}
.tabla.activa:hover {
    background-color: #27ae60;
}

/* === NUEVO: botón flotante secundario para "Ver todos los create table." === */
.boton-flotante-secundario {
    position: fixed;
    bottom: 95px; /* encima del botón copiar */
    right: 30px;
    background-color: #6c5ce7;
    color: white;
    border: none;
    padding: 15px 20px;
    border-radius: 50px;
    cursor: pointer;
    font-size: 16px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}
.boton-flotante-secundario:hover {
    background-color: #a29bfe;
}

/* === NUEVO: modal para listar todos los CREATE TABLE === */
.modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 1000;
}
.modal.mostrar {
    display: flex;
}
.modal-contenido {
    background: #fff;
    width: 90vw;
    max-width: 1000px;
    max-height: 80vh;
    overflow: auto;
    border-radius: 8px;
    padding: 20px;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.modal-cerrar {
    background: none;
    border: none;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
}
.lista-create-tables {
    padding-left: 20px;
}
.lista-create-tables li {
    margin-bottom: 16px;
}
.create-sql {
    background:#1e1e1e;
    color:#00ff9f;
    padding:12px;
    border-radius:8px;
    white-space: pre-wrap;
}
/* Sidebar: cada item ahora es flex para mostrar nombre + badge a la derecha */
.tabla {
    margin: 5px 0;
    padding: 8px;
    background-color: #485460;
    border-radius: 4px;
    cursor: pointer;
    display: flex;                /* NUEVO */
    align-items: center;          /* NUEVO */
    justify-content: space-between;/* NUEVO */
    gap: 8px;                     /* NUEVO */
}

/* Contenedor inferior: que no haga scroll global y deje a los hijos manejar el suyo */
.divInferior {
    flex: 1;
    display: flex;
    padding: 20px;
    gap: 20px;
    background-color: #f5f6fa;
    overflow: hidden;   /* NUEVO */
    min-height: 0;      /* NUEVO: clave para scroll interno */
}

/* Paneles inferiores: scroll vertical y horizontal propios */
.div1, .div2 {
    flex: 1;
    overflow: auto;     /* NUEVO: x e y */
    min-width: 0;       /* NUEVO: permite que el flex-item pueda contraerse y mostrar scroll */
    min-height: 0;      /* NUEVO */
}

/* Panel superior: ya usa #dataTableResult como área de scroll; añadimos min-height:0 */
.divSuperior {
    flex: 1;
    padding: 20px;
    border-bottom: 1px solid #ccc;
    background-color: #f1f2f6;
    overflow: hidden;       /* se mantiene */
    display: flex;
    flex-direction: column;
    min-height: 0;          /* NUEVO */
}

/* Área que muestra registros: habilitamos scroll en ambos ejes */
#dataTableResult {
    flex: 1;
    overflow: auto;     /* y */
    max-height: 100%;
    min-width: 0;       /* NUEVO */
    min-height: 0;      /* NUEVO */
}

/* Código SQL: sin envolver líneas para permitir scroll horizontal */
.div2 pre {
    background-color: #1e1e1e;
    color: #00ff9f;
    padding: 15px;
    border-radius: 8px;
    font-family: 'Courier New', Courier, monospace;
    white-space: pre;   /* CAMBIO: de pre-wrap -> pre */
    overflow: auto;     /* NUEVO: x e y */
}

/* Badge (etiqueta de conteo) */
.badge {
    display: inline-block;
    font-size: 12px;
    line-height: 1;
    padding: 4px 8px;
    border-radius: 999px;
    min-width: 22px;
    text-align: center;
    font-weight: bold;
}

/* Estados del badge según cantidad */
.badge-pendiente { background: #636e72; color: #fff; }  /* mientras carga */
.badge-rojo      { background: #e74c3c; color: #fff; }  /* 0 */
.badge-gris      { background: #95a5a6; color: #fff; }  /* 1-5 */
.badge-azul      { background: #3498db; color: #fff; }  /* 6-10 */
.badge-amarillo  { background: #f1c40f; color: #000; }  /* 11-50 */
.badge-verde     { background: #2ecc71; color: #fff; }  /* 51+ */
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Tablas en la BD</h2>
<?php foreach ($tablas as $tabla): ?>
    <div
        class="tabla"
        data-tabla="<?= htmlspecialchars($tabla) ?>"
        onclick="mostrarCreateTable('<?= $tabla ?>', this)"
    >
        <span class="tabla-nombre"><?= htmlspecialchars($tabla) ?></span>
        <span class="badge badge-pendiente" data-badge-para="<?= htmlspecialchars($tabla) ?>">…</span>
    </div>
<?php endforeach; ?>
    </div>

    <div class="contenido">
        <div class="divSuperior" id="divSuperior">
            <div style="margin-bottom:10px;">
                <label for="limitSelect">Mostrar:</label>
                <select id="limitSelect" onchange="recargarDatosTabla()">
                    <option value="5">5 registros</option>
                    <option value="10">10 registros</option>
                    <option value="0">Todos</option>
                </select>
            </div>
            <div id="dataTableResult">
                <p>Selecciona una tabla para ver sus registros.</p>
            </div>
        </div>

        <div class="divInferior">
            <div class="div1" id="div1">
                <h3>Detalles adicionales</h3>
            </div>

            <div class="div2" id="div2">
                <h3>CREATE TABLE</h3>
                <pre id="sqlCode">Seleccione una tabla para ver el SQL...</pre>
            </div>
        </div>
    </div>

    <button class="boton-flotante" onclick="copiarCodigo()">Copiar SQL</button>
    <!-- NUEVO: Botón flotante para ver todos los CREATE TABLE -->
<button class="boton-flotante-secundario" onclick="verTodosCreateTables()">Ver todos los create table.</button>

<!-- NUEVO: Modal para listar todos los CREATE TABLE -->
<div id="modalCreateAll" class="modal" aria-hidden="true">
  <div class="modal-contenido">
    <div class="modal-header">
      <h3>Todos los CREATE TABLE (solo tablas)</h3>
      <button class="modal-cerrar" onclick="cerrarModal()" aria-label="Cerrar">×</button>
    </div>
    <div id="modalBody">Cargando...</div>
  </div>
</div>


    <script>
    let tablaSeleccionada = '';
function mostrarCreateTable(nombreTabla) {
    tablaSeleccionada = nombreTabla;

    // Cargar el SQL CREATE TABLE
    fetch('get_create_table.php?tabla=' + encodeURIComponent(nombreTabla))
        .then(response => response.text())
        .then(data => {
            document.getElementById('sqlCode').textContent = data;
        });

    // Cargar estructura de columnas
    fetch('get_table_structure.php?tabla=' + encodeURIComponent(nombreTabla))
        .then(response => response.text())
        .then(data => {
            document.getElementById('div1').innerHTML = '<h3>Estructura de columnas</h3>' + data;
        });

    // Cargar registros con paginación
    recargarDatosTabla();
}

    function recargarDatosTabla() {
        const limit = document.getElementById('limitSelect').value;

        fetch(`get_table_data.php?tabla=${encodeURIComponent(tablaSeleccionada)}&limit=${limit}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('dataTableResult').innerHTML = data;
            });
    }

    function copiarCodigo() {
        const texto = document.getElementById('sqlCode').textContent;
        navigator.clipboard.writeText(texto).then(() => {
            alert("Código SQL copiado al portapapeles");
        });
    }
    function mostrarCreateTable(nombreTabla, el) {
    tablaSeleccionada = nombreTabla;

    // 1) Marcar elemento activo en la barra izquierda
    const items = document.querySelectorAll('.sidebar .tabla');
    items.forEach(n => n.classList.remove('activa'));
    if (el) {
        el.classList.add('activa');
    } else {
        // Fallback si se llama sin "el"
        const candidato = Array.from(items).find(n => n.dataset.tabla === nombreTabla);
        if (candidato) candidato.classList.add('activa');
    }

    // 2) Cargar el SQL CREATE TABLE
    fetch('get_create_table.php?tabla=' + encodeURIComponent(nombreTabla))
        .then(response => response.text())
        .then(data => {
            document.getElementById('sqlCode').textContent = data;
        });

    // 3) Cargar estructura de columnas
    fetch('get_table_structure.php?tabla=' + encodeURIComponent(nombreTabla))
        .then(response => response.text())
        .then(data => {
            document.getElementById('div1').innerHTML = '<h3>Estructura de columnas</h3>' + data;
        });

    // 4) Cargar registros con el límite seleccionado
    recargarDatosTabla();
}

// === NUEVO: abrir modal y traer todos los CREATE TABLE (excluye VIEWS) ===
function verTodosCreateTables() {
    const modal = document.getElementById('modalCreateAll');
    const body = document.getElementById('modalBody');
    body.innerHTML = 'Cargando...';
    modal.classList.add('mostrar');
    modal.setAttribute('aria-hidden', 'false');

    fetch('get_all_create_tables.php')
        .then(r => r.text())
        .then(html => {
            body.innerHTML = html;
        })
        .catch(() => {
            body.innerHTML = '<p>Error al cargar los CREATE TABLE.</p>';
        });
}

// === NUEVO: cerrar modal (clic en backdrop o botón cerrar) ===
function cerrarModal() {
    const modal = document.getElementById('modalCreateAll');
    modal.classList.remove('mostrar');
    modal.setAttribute('aria-hidden', 'true');
}

// Cerrar modal al hacer clic fuera del contenido
document.addEventListener('click', function (e) {
    const modal = document.getElementById('modalCreateAll');
    if (!modal) return;
    if (e.target === modal) cerrarModal();
});

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') cerrarModal();
});
// === NUEVO: obtiene clase de color por cantidad ===
function claseBadgePorCantidad(n) {
    if (!Number.isFinite(n) || n < 0) return 'badge-gris';
    if (n === 0) return 'badge-rojo';
    if (n >= 1 && n <= 5) return 'badge-gris';
    if (n >= 6 && n <= 10) return 'badge-azul';
    if (n >= 11 && n <= 50) return 'badge-amarillo';
    return 'badge-verde'; // 51+
}

// === NUEVO: carga los contadores de todas las tablas del sidebar ===
function cargarContadoresTablas() {
    const items = document.querySelectorAll('.sidebar .tabla');
    items.forEach(item => {
        const nombre = item.dataset.tabla;
        const badge = item.querySelector('.badge');
        if (!nombre || !badge) return;

        fetch('get_table_count.php?tabla=' + encodeURIComponent(nombre))
            .then(r => r.json())
            .then(json => {
                const total = Number(json.count);
                badge.textContent = Number.isFinite(total) ? total : '?';

                // limpiar clases previas de color
                badge.classList.remove('badge-pendiente','badge-rojo','badge-gris','badge-azul','badge-amarillo','badge-verde');
                badge.classList.add(claseBadgePorCantidad(total));
            })
            .catch(() => {
                badge.textContent = '!';
                badge.classList.remove('badge-pendiente');
                badge.classList.add('badge-rojo');
            });
    });
}

// === NUEVO: al cargar la página, traer contadores ===
document.addEventListener('DOMContentLoaded', cargarContadoresTablas);
    </script>
</body>
</html>

<?php
// Lab rápido: mapa de sedes (sin API key) usando Leaflet + OpenStreetMap.
// IMPORTANTE: NO pongas usuarios/contraseñas en el frontend.

// Puedes ajustar/añadir sedes aquí:
$sedes = [
  [
    "empresa" => "ALLAIN PROST",
    "sede"    => "Trujillo",
    "lat"     => -8.1116,
    "lng"     => -79.0289,
    "url"     => "http://190.117.99.126:90"
  ],
  [
    "empresa" => "ALLAIN PROST",
    "sede"    => "Trujillo (2)",
    "lat"     => -8.1116,
    "lng"     => -79.0289,
    "url"     => "http://190.117.239.144:90"
  ],
  [
    "empresa" => "VIAS SEGURAS",
    "sede"    => "Chiclayo",
    "lat"     => -6.7714,
    "lng"     => -79.8409,
    "url"     => "http://190.117.188.206:8082"
  ],
  [
    "empresa" => "GUIA MIS RUTAS",
    "sede"    => "Huaraz",
    "lat"     => -9.5289,
    "lng"     => -77.5289,
    "url"     => "http://38.250.181.177:2010"
  ],
  [
    "empresa" => "ALLAIN PROST",
    "sede"    => "La Merced (Chanchamayo)",
    "lat"     => -11.0565,
    "lng"     => -75.3326,
    "url"     => "http://45.177.23.93:700"
  ],
  [
    "empresa" => "GUIA MIS RUTAS",
    "sede"    => "Pasco (Cerro de Pasco)",
    "lat"     => -10.6675,
    "lng"     => -76.2572,
    "url"     => "http://181.66.253.83"
  ],
  [
    "empresa" => "ALLAIN PROST",
    "sede"    => "Piura",
    "lat"     => -5.1945,
    "lng"     => -80.6328,
    "url"     => "http://190.117.115.142:900"
  ],
  [
    "empresa" => "ALLAIN PROST",
    "sede"    => "Lima",
    "lat"     => -12.0464,
    "lng"     => -77.0428,
    "url"     => "http://132.251.134.90"
  ],
  [
    "empresa" => "ALLAIN PROST",
    "sede"    => "Chocope (La Libertad)",
    "lat"     => -7.7922,
    "lng"     => -79.2217,
    "url"     => "http://190.117.242.96:2000"
  ],
  [
    "empresa" => "GUIA MIS RUTAS",
    "sede"    => "Huancayo",
    "lat"     => -12.0651,
    "lng"     => -75.2049,
    "url"     => "http://190.117.48.187"
  ],
  [
    "empresa" => "ALLAIN PROST",
    "sede"    => "Huancayo (2)",
    "lat"     => -12.0651,
    "lng"     => -75.2049,
    "url"     => "http://190.117.48.150:90"
  ],
];

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Escuela de Manejo - Presencia en todo el Perú</title>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <style>
    :root{--card:#ffffff;--text:#111827;--muted:#6b7280;--border:#e5e7eb;}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;color:var(--text);background:#f6f7fb;}
    .wrap{max-width:1100px;margin:24px auto;padding:0 16px;}
    .hero{
      background:var(--card); border:1px solid var(--border); border-radius:14px;
      padding:18px 18px; box-shadow:0 6px 18px rgba(0,0,0,.06);
    }
    h1{font-size:22px;margin:0 0 6px;}
    .sub{color:var(--muted);margin:0;line-height:1.45}
    .grid{
      display:grid; gap:16px; margin-top:16px;
      grid-template-columns: 1.2fr .8fr;
    }
    @media (max-width: 900px){
      .grid{grid-template-columns:1fr;}
    }

    /* Mapa dentro de un div (NO fullscreen) */
    #map{
      height: 480px; /* ajusta a tu gusto */
      border:1px solid var(--border);
      border-radius:14px;
      overflow:hidden;
      box-shadow:0 6px 18px rgba(0,0,0,.06);
      background:#fff;
    }

    .panel{
      background:var(--card); border:1px solid var(--border); border-radius:14px;
      padding:14px; box-shadow:0 6px 18px rgba(0,0,0,.06);
    }
    .panel h2{font-size:16px;margin:0 0 10px;}
    .list{margin:0;padding:0;list-style:none;max-height:480px;overflow:auto;}
    .item{
      padding:10px; border:1px solid var(--border); border-radius:12px; background:#fff;
      margin-bottom:10px;
    }
    .item b{display:block;font-size:14px;margin-bottom:2px;}
    .item small{color:var(--muted)}
    .item a{display:inline-block;margin-top:6px;text-decoration:none}
    .hint{font-size:12px;color:var(--muted);margin-top:10px;line-height:1.4}
    .badge{
      display:inline-block; padding:3px 8px; border:1px solid var(--border); border-radius:999px;
      font-size:12px; color:var(--muted); margin-left:6px;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="hero">
      <h1>Escuela de Manejo con presencia nacional <span class="badge">Perú</span></h1>
      <p class="sub">
        Somos una escuela de manejo con sedes y operaciones en diversas ciudades del país.
        Este laboratorio muestra nuestras ubicaciones en un mapa interactivo para que veas cómo quedaría en tu web PHP.
        Haz clic en un marcador para ver el nombre de la sede y acceder al enlace.
      </p>
    </div>

    <div class="grid">
      <div id="map" aria-label="Mapa de sedes"></div>

      <div class="panel">
        <h2>Nuestras sedes (demo)</h2>
        <ul class="list" id="sedeList">
          <?php foreach ($sedes as $s): ?>
            <?php
              $titulo = $s["empresa"]." - Sede ".$s["sede"];
              $url = $s["url"];
            ?>
            <li class="item">
              <b><?= htmlspecialchars($titulo) ?></b>
              <small><?= htmlspecialchars($s["sede"]) ?></small><br>
              <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">Abrir enlace</a>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="hint">
          * Demo rápido: coordenadas aproximadas por ciudad. Si quieres precisión exacta (dirección real),
          lo ideal es guardar lat/lng por sede en tu BD.
        </div>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const sedes = <?php echo json_encode($sedes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    // Crear mapa dentro del div
    const map = L.map('map', { zoomControl: true });

    // Capa base OSM
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Grupo de marcadores para ajustar el encuadre
    const group = L.featureGroup().addTo(map);

    function escapeHtml(str){
      return String(str).replace(/[&<>"']/g, s => ({
        "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"
      }[s]));
    }

    sedes.forEach(s => {
      const title = `${s.empresa} - Sede ${s.sede}`;
      const popup = `
        <div style="min-width:220px">
          <b>${escapeHtml(title)}</b><br>
          <span style="color:#6b7280">${escapeHtml(s.sede)}</span><br>
          <a href="${escapeHtml(s.url)}" target="_blank" rel="noopener">Abrir enlace</a>
        </div>
      `;

      const marker = L.marker([s.lat, s.lng]).addTo(group);
      marker.bindPopup(popup);
    });

    // Encadre automático
    if (sedes.length) {
      map.fitBounds(group.getBounds().pad(0.2));
    } else {
      // fallback: Perú aprox
      map.setView([-9.19, -75.02], 5);
    }
  </script>
</body>
</html>

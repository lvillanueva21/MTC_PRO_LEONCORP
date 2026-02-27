<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Cámaras Grupo Car</title>
  <style> 
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: url('https://static.vecteezy.com/system/resources/previews/012/999/077/large_2x/cctv-camera-install-by-have-water-proof-cover-to-protect-camera-with-home-security-system-concept-free-photo.jpg') no-repeat center center fixed;
      background-size: cover;
      background-color: #000;
    }

    body:before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: -1;
    }

    .apple-header {
      max-width: 700px;
      margin: 30px auto 20px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.2);
      padding: 20px;
      text-align: center;
      position: relative;
    }

.apple-header img {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 60px;
  height: auto;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}


    .apple-header h1 {
      font-size: 1.8rem;
      margin: 10px 0;
      color: #333;
    }

    #clock {
      margin-top: 15px;
      font-size: 0.95rem;
      color: #666;
      font-weight: bold;
    }

.container {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  max-width: 100%;
  padding: 20px;
  box-sizing: border-box;
}

.city {
  width: 18%;
  margin: 1%;
  height: 280px;
  padding: 15px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  transition: transform 0.3s, box-shadow 0.3s;
  box-sizing: border-box;
}

    .city:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }

    .city h2 {
      font-size: 1.3rem;
      margin: 0 0 15px;
      text-align: center;
    }

    .city h2 img {
      vertical-align: middle;
      width: 20px;
      height: 20px;
      margin-right: 8px;
    }

    .city_container {
      margin-top: 10px;
    }

    .cam {
      display: block;
      text-align: center;
      padding: 10px;
      margin: 8px 0;
      border-radius: 8px;
      color: white;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s, transform 0.2s;
    }

    .cam:hover {
      opacity: 0.9;
      transform: scale(1.05);
    }

    .cam-sol { background: #f1b72e; }
    .cam-global { background: #99322d; }
    .cam-medic { background: #00b4d8; }
    .cam-selva { background: #2a9d8f; }
    .cam-lc { background: #000000; }

    .container::after {
      content: "";
      display: table;
      clear: both;
    }
  </style>
</head>
<body>

  <div class="apple-header">
    <img src="https://leoncorp.pe/antiguo/img/logo.png" alt="Logo León Corp">
    <h1>GRUPO LEÓN & ASOC.<br>Sistema de Videovigilancia</h1>
    <div id="clock">Cargando hora de Lima...</div>
  </div>

  <div class="container">
    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Tumbes</h2>
      <div class="city_container">
        <a href="http://sctumbes.dvrdns.org:2000" target="_blank" class="cam cam-sol">🚗 Global Car</a>
        <a href="http://gmtumbes.dvrdns.org:2001" target="_blank" class="cam cam-medic">🏥 Global Medic</a>
        <a href="http://omtumbes.dvrdns.org:2000" target="_blank" class="cam cam-medic">🏥 Open Medic</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Piura</h2>
      <div class="city_container">
        <a href="http://scpiura.dvrdns.org:2000" target="_blank" class="cam cam-sol">🚗 Global Car</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Chiclayo</h2>
      <div class="city_container">
        <a href="http://gcchiclayo.dvrdns.org:2001" target="_blank" class="cam cam-global">🚗 Global Car</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Trujillo</h2>
      <div class="city_container">
        <a href="http://scglobalcar.dvrdns.org:2000" target="_blank" class="cam cam-sol">🚗 Escuela Global Car</a>
        <a href="http://opmtrujillo.dvrdns.org:2001" target="_blank" class="cam cam-medic">🏥 Open Medic</a>
        <!--<a href="http://cirtrujillo.dvrdns.org:2000" target="_blank" class="cam cam-lc">🏁 Circuito Huanchaco</a> -->
        <a href="http://scgctrujillo.dvrdns.org:1000" target="_blank" class="cam cam-global">🚗 Global Car Trujillo</a>
        <a href="http://192.168.18.201:2001" target="_blank" class="cam cam-lc">🦁 Central LeonCorp</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Chota</h2>
      <div class="city_container">
        <a href="http://gcpchota.dvrdns.org:2000" target="_blank" class="cam cam-global">🚗 Global Car</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Chimbote</h2>
      <div class="city_container">
        <a href="http://scchimbote.dvrdns.org:2000" target="_blank" class="cam cam-sol">🚗 Global Car</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Pucallpa</h2>
      <div class="city_container">
        <a href="http://scpucallpa.dvrdns.org:2000" target="_blank" class="cam cam-selva">🚗 Selva Car</a>
        <a href="http://gmpucallpa.dvrdns.org:2001" target="_blank" class="cam cam-medic">🏥 Global Medic</a>
        <a href="http://scaguaytia.dvrdns.org:2002" target="_blank" class="cam cam-selva">🚗 Aguaytia</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Tarapoto</h2>
      <div class="city_container">
        <a href="http://escuelashadai.dvrdns.org:2000" target="_blank" class="cam cam-selva">🚗 Selva Car</a>
        <a href="http://omtarapoto.dvrdns.org:2000" target="_blank" class="cam cam-medic">🏥 Open Medic</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Huaraz</h2>
      <div class="city_container">
        <a href="http://guiasmisrutas.dvrdns.org:2010" target="_blank" class="cam cam-global">🚗 Guías Mis Rutas</a>
      </div>
    </div>

    <div class="city"><h2><img src="https://cdn-icons-png.flaticon.com/512/8334/8334315.png">Iquitos</h2>
      <div class="city_container">
        <a href="http://sciquitos.dvrdns.org:2001" target="_blank" class="cam cam-selva">🚗 Selva Car</a>
      </div>
    </div>
  </div>

  <script>
    function updateClock() {
      var now = new Date();
      var utc = now.getTime() + (now.getTimezoneOffset() * 60000);
      var limaOffset = -5;
      var limaTime = new Date(utc + (3600000 * limaOffset));

      var options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      };

      var formatted = limaTime.toLocaleString('es-PE', options);
      document.getElementById('clock').innerText = formatted;
    }

    updateClock();
    setInterval(updateClock, 1000);
  </script>
</body>
</html>

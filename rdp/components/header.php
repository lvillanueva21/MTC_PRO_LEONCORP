<!-- components/header.php -->
<div class="encabezado-box">
  <div class="row align-items-center w-100 m-0">
    
    <!-- Logo MTC a la izquierda -->
    <div class="col-2 text-start">
      <a href="https://sso.mtc.gob.pe/Manager/" target="_blank">
        <img src="assets/logo_mtc.webp" alt="Logo MTC" class="logo-img">
      </a>
    </div>

    <!-- T��tulo y hora al centro -->
    <div class="col-8 text-center">
      <h1 class="titulo-encabezado mb-2">REGISTRO DE DATOS PC</h1>
      <div id="clock" class="reloj-encabezado">Cargando hora de Lima...</div>
    </div>

    <!-- Logo Le��n Corp a la derecha -->
    <div class="col-2 text-end">
      <a href="index.php">
        <img src="../antiguo/img/logo.png" alt="Logo Le��n Corp" class="logo-img">
      </a>
    </div>
  </div>
</div>

<script>
function updateClock() {
  const now = new Date();
  const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
  const limaOffset = -5;
  const limaTime = new Date(utc + (3600000 * limaOffset));

  const options = {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  };

  const formatted = limaTime.toLocaleString('es-PE', options);
  document.getElementById('clock').innerText = formatted;
}

updateClock();
setInterval(updateClock, 1000);
</script>
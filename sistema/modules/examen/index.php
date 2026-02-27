<?php
// modules/examen/index.php
require_once __DIR__ . '/../../includes/acl.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/auth.php';

// Permisos: Cliente(7), Desarrollo(1), Recepción(3), Administración(4), Gerente(6)
acl_require_ids([7,1,3,4,6]);

include __DIR__ . '/../../includes/header.php';

// -----------------------------
// Datos de ejemplo (sin BD)
// -----------------------------
$exam = [
  'title' => 'Simulacro Examen MTC - Brevete A1',
  'subtitle' => 'Preguntas de cultura y normas de tránsito (modo práctica).',
  'max_minutes' => 20,
  'pass_score' => 14, // de 20
];

// Preguntas tipo MTC (genéricas, de ejemplo). Correcta = índice (0..3)
$questions = [
  [
    'id'=>'q1',
    'text'=>'¿Cuál es el límite máximo de velocidad en zona urbana, salvo señalización distinta?',
    'options'=>['50 km/h','60 km/h','80 km/h','40 km/h'],
    'correct'=>0,
    'tip'=>'En vías urbanas, el límite general es 50 km/h.',
    'ref'=>'RNT — Límites de velocidad (urbano).',
  ],
  [
    'id'=>'q2',
    'text'=>'Ante una luz amarilla fija del semáforo usted debe:',
    'options'=>['Acelerar para cruzar antes que cambie','Detenerse si puede hacerlo con seguridad','Cruzar sin detenerse','Tocar bocina y avanzar'],
    'correct'=>1,
    'tip'=>'La amarilla advierte cambio a rojo; deténgase si es seguro.',
    'ref'=>'Señales luminosas — Luz amarilla.',
  ],
  [
    'id'=>'q3',
    'text'=>'Está prohibido estacionar:',
    'options'=>['A 10 m de esquinas','En zonas señalizadas con línea amarilla continua','En zonas con señal “E permitido”','En bahías autorizadas'],
    'correct'=>1,
    'tip'=>'La línea amarilla longitudinal indica prohibición de estacionar.',
    'ref'=>'Marcas en el pavimento.',
  ],
  [
    'id'=>'q4',
    'text'=>'El uso de cinturón de seguridad es obligatorio para:',
    'options'=>['Solo el conductor','Conductor y copiloto','Todos los ocupantes','Solo niños'],
    'correct'=>2,
    'tip'=>'Todos los ocupantes deben usarlo.',
    'ref'=>'Seguridad vial — Dispositivos de retención.',
  ],
  [
    'id'=>'q5',
    'text'=>'Si un peatón está en el cruce peatonal, usted debe:',
    'options'=>['Cederle el paso','Tocar bocina y pasar','Avanzar si tiene luz verde','Pasar si el peatón duda'],
    'correct'=>0,
    'tip'=>'Prioridad del peatón en cruce marcado.',
    'ref'=>'Prioridades de paso.',
  ],
  [
    'id'=>'q6',
    'text'=>'La distancia mínima de seguimiento (“dos segundos”) sirve para:',
    'options'=>['Ahorrar combustible','Mantener separación segura','Evitar multas','Mejorar visibilidad del retrovisor'],
    'correct'=>1,
    'tip'=>'Regla básica para detenerse a tiempo.',
    'ref'=>'Conducción defensiva.',
  ],
  [
    'id'=>'q7',
    'text'=>'Está permitido adelantar en curva?',
    'options'=>['Sí, con luz de cruce','Sí, si no vienen vehículos','No, está prohibido','Solo de día'],
    'correct'=>2,
    'tip'=>'En curvas y pendientes con visibilidad reducida está prohibido.',
    'ref'=>'Adelantamiento — Zonas prohibidas.',
  ],
  [
    'id'=>'q8',
    'text'=>'La señal de tránsito con un triángulo invertido indica:',
    'options'=>['Pare','Ceda el paso','Prohibido girar','Velocidad máxima'],
    'correct'=>1,
    'tip'=>'Triángulo invertido = Ceda el paso.',
    'ref'=>'Señales reglamentarias.',
  ],
  [
    'id'=>'q9',
    'text'=>'El alcohol afecta principalmente:',
    'options'=>['La visión y el tiempo de reacción','La potencia del motor','La presión de llantas','El consumo de combustible'],
    'correct'=>0,
    'tip'=>'Aumenta el tiempo de reacción y reduce percepción.',
    'ref'=>'Conducción y alcohol.',
  ],
  [
    'id'=>'q10',
    'text'=>'En caso de neblina, se debe circular con:',
    'options'=>['Luces altas','Luces intermitentes','Luces bajas y a velocidad prudente','Solo luces de estacionamiento'],
    'correct'=>2,
    'tip'=>'Luces bajas, mayor distancia y menor velocidad.',
    'ref'=>'Condiciones adversas.',
  ],
  [
    'id'=>'q11',
    'text'=>'El teléfono celular al conducir:',
    'options'=>['Se puede usar en altavoz','Solo para mensajes cortos','Está prohibido si no es manos libres','Es obligatorio para GPS'],
    'correct'=>2,
    'tip'=>'Evite distracciones; solo manos libres.',
    'ref'=>'Distracciones al volante.',
  ],
  [
    'id'=>'q12',
    'text'=>'Antes de cambiar de carril usted debe:',
    'options'=>['Tocar bocina','Acelerar','Usar direccional y verificar puntos ciegos','Esperar luz verde'],
    'correct'=>2,
    'tip'=>'Señalice y verifique espejos y punto ciego.',
    'ref'=>'Maniobras seguras.',
  ],
  [
    'id'=>'q13',
    'text'=>'La presión adecuada de los neumáticos:',
    'options'=>['Se revisa semanalmente en frío','No es relevante en ciudad','Se infla al máximo','Solo en viajes largos'],
    'correct'=>0,
    'tip'=>'Siempre revisar en frío según fabricante.',
    'ref'=>'Mecánica básica.',
  ],
  [
    'id'=>'q14',
    'text'=>'Si un vehículo de emergencia se acerca con sirena y luces, usted debe:',
    'options'=>['Seguirlo','Detenerse en seco','Ceder el paso y apartarse con seguridad','Acelerar para despejar'],
    'correct'=>2,
    'tip'=>'Ceda el paso, oríllese cuando sea seguro.',
    'ref'=>'Prioridad de paso — Emergencias.',
  ],
  [
    'id'=>'q15',
    'text'=>'En un cruce sin semáforo ni señal, tiene prioridad:',
    'options'=>['Quien va más rápido','El de la izquierda','El de la derecha','Nadie'],
    'correct'=>2,
    'tip'=>'Regla general: prioridad del vehículo que viene por la derecha.',
    'ref'=>'Intersecciones no señalizadas.',
  ],
  [
    'id'=>'q16',
    'text'=>'Los niños deben viajar:',
    'options'=>['En brazos del adulto','En el asiento delantero','En sistemas de retención adecuados','De pie atrás'],
    'correct'=>2,
    'tip'=>'SRI según talla y peso.',
    'ref'=>'Seguridad pasiva.',
  ],
  [
    'id'=>'q17',
    'text'=>'El “punto ciego” es:',
    'options'=>['Un parche del parabrisas','Área no visible en espejos','Zona de estacionamiento','Otro nombre del parachoques'],
    'correct'=>1,
    'tip'=>'Gira la cabeza para verificarlo.',
    'ref'=>'Conducción defensiva.',
  ],
  [
    'id'=>'q18',
    'text'=>'Si pincha una llanta en carretera, lo primero es:',
    'options'=>['Frenar bruscamente','Sujetar firme el volante y desacelerar','Acelerar y orillarse rápido','Apagar el motor de inmediato'],
    'correct'=>1,
    'tip'=>'Mantenga control, señalice y deténgase en zona segura.',
    'ref'=>'Situaciones de emergencia.',
  ],
  [
    'id'=>'q19',
    'text'=>'La distancia para adelantar debe considerar:',
    'options'=>['El clima y visibilidad','Velocidad propia y del otro vehículo','Longitud de la maniobra','Todas las anteriores'],
    'correct'=>3,
    'tip'=>'Evalúe todos los factores.',
    'ref'=>'Adelantamiento seguro.',
  ],
  [
    'id'=>'q20',
    'text'=>'Circular por el carril exclusivo de transporte público:',
    'options'=>['Está permitido si está vacío','Prohibido salvo autorización','Permitido los domingos','Solo para autos híbridos'],
    'correct'=>1,
    'tip'=>'Respete carriles exclusivos.',
    'ref'=>'Uso de vías especiales.',
  ],
];
?>
<style>
  .quiz-card{border-radius:14px}
  .quiz-option{border:1px solid #e5e7eb;border-radius:10px;padding:.7rem .8rem;cursor:pointer}
  .quiz-option:hover{background:#f8fafc}
  .quiz-option.selected{background:#eef2ff;border-color:#6366f1}
  .quiz-option.correct{background:#dcfce7;border-color:#16a34a}
  .quiz-option.incorrect{background:#fee2e2;border-color:#ef4444}
  .sticky-side{position:sticky;top:1rem}
  .progress{height:10px;border-radius:999px}
  .resource-link{display:flex;align-items:center;gap:.5rem}
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <h1 class="m-0"><?= htmlspecialchars($exam['title']) ?></h1>
          <p class="text-muted mb-2"><?= htmlspecialchars($exam['subtitle']) ?></p>
          <div class="d-flex align-items-center gap-3 small text-muted">
            <span><i class="far fa-clock me-1"></i> Tiempo máx: <?= (int)$exam['max_minutes'] ?> min</span>
            <span><i class="fas fa-check me-1"></i> Apruebas con <?= (int)$exam['pass_score'] ?>/20</span>
          </div>
        </div>
        <div class="text-end">
          <div class="small text-muted">Progreso</div>
          <div class="progress" style="width:280px;">
            <div id="bar" class="progress-bar bg-primary" style="width:0%"></div>
          </div>
          <div class="small mt-1"><span id="progText">0 / 20</span></div>
          <div class="mt-2 badge bg-dark" id="timer"><i class="far fa-hourglass me-1"></i><?= (int)$exam['max_minutes'] ?>:00</div>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row g-3">
        <!-- Col principal -->
        <div class="col-lg-8">
          <div class="card quiz-card shadow-sm">
            <div class="card-body">
              <div id="qContainer"></div>

              <div class="d-flex justify-content-between mt-3">
                <button id="prevBtn" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Anterior</button>
                <div class="d-flex gap-2">
                  <button id="markBtn" class="btn btn-outline-warning"><i class="far fa-flag me-1"></i> Marcar</button>
                  <button id="clearBtn" class="btn btn-outline-secondary"><i class="far fa-times-circle me-1"></i> Limpiar</button>
                  <button id="nextBtn" class="btn btn-primary">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                </div>
              </div>

              <hr class="my-4">

              <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="showTips">
                    <label class="form-check-label" for="showTips">Mostrar tips y referencias</label>
                  </div>
                </div>
                <button id="finishBtn" class="btn btn-success">
                  <i class="fas fa-check-double me-1"></i> Finalizar y Calificar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Col lateral -->
        <div class="col-lg-4">
          <div class="sticky-side">
            <div class="card shadow-sm quiz-card mb-3">
              <div class="card-body">
                <h5 class="mb-2"><i class="fas fa-list-ol me-1"></i> Navegación</h5>
                <div class="d-flex flex-wrap gap-2" id="navGrid"></div>
              </div>
            </div>

            <div class="card shadow-sm quiz-card">
              <div class="card-body">
                <h6 class="mb-2"><i class="fas fa-book-open me-1"></i> Recursos de apoyo</h6>
                <div class="d-grid gap-2">
                  <a class="resource-link text-decoration-none" href="#" target="_blank">
                    <i class="far fa-file-alt"></i> Manual de normas de tránsito (PDF)
                  </a>
                  <a class="resource-link text-decoration-none" href="#" target="_blank">
                    <i class="fas fa-traffic-light"></i> Señales y demarcaciones — guía visual
                  </a>
                  <a class="resource-link text-decoration-none" href="#" target="_blank">
                    <i class="fas fa-car-crash"></i> Conducción defensiva — tips rápidos
                  </a>
                </div>
                <p class="small text-muted mt-2 mb-0">* En producción enlaza a tus materiales oficiales.</p>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- /row -->
    </div>
  </section>
</div>

<!-- Modal resultado -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-poll me-1"></i> Resultado del simulacro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div>
            <div class="h4 m-0">Puntaje: <span id="scoreText">0</span> / 20</div>
            <div class="small text-muted">Mínimo para aprobar: <?= (int)$exam['pass_score'] ?></div>
          </div>
          <div class="text-end">
            <span id="passBadge" class="badge bg-success d-none">APROBADO</span>
            <span id="failBadge" class="badge bg-danger d-none">DESAPROBADO</span>
          </div>
        </div>

        <hr>
        <h6>Revisión rápida</h6>
        <div id="reviewList" class="list-group list-group-flush"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button id="retryBtn" class="btn btn-primary">Reiniciar</button>
      </div>
    </div>
  </div>
</div>

<script>
  // === Datos desde PHP al JS ===
  const QUESTIONS = <?= json_encode($questions, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  const PASS_SCORE = <?= (int)$exam['pass_score'] ?>;
  const MAX_MINUTES = <?= (int)$exam['max_minutes'] ?>;

  // === Estado ===
  let current = 0;
  const answers = {};       // { indexPregunta: indexOpcion }
  const marked = new Set(); // marcadas para revisar
  let timerSec = MAX_MINUTES * 60;
  let timerId = null;

  // === Utilidades UI ===
  const el = sel => document.querySelector(sel);
  const $q = id => document.getElementById(id);

  function renderQuestion(idx){
    const q = QUESTIONS[idx];
    if (!q) return;

    const container = $q('qContainer');
    let opts = '';
    q.options.forEach((op, i)=>{
      const selected = (answers[idx] === i) ? 'selected' : '';
      opts += `
        <div class="quiz-option ${selected}" data-idx="${i}">
          <div class="d-flex justify-content-between align-items-center">
            <div>${String.fromCharCode(65+i)}. ${op}</div>
            ${answers[idx] === i ? '<i class="fas fa-check text-primary"></i>' : ''}
          </div>
        </div>`;
    });

    const tipBlock = `
      <div id="tipBox" class="alert alert-info mt-3 d-none">
        <div class="fw-semibold">${q.ref ?? 'Referencia'}</div>
        <div class="small mb-0">${q.tip ?? ''}</div>
      </div>`;

    container.innerHTML = `
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="badge bg-light text-dark">Pregunta ${idx+1} de ${QUESTIONS.length}</div>
        <div class="small text-muted">Marcada: <span class="fw-semibold">${marked.has(idx) ? 'Sí' : 'No'}</span></div>
      </div>
      <h5 class="mb-3">${q.text}</h5>
      ${opts}
      ${tipBlock}
    `;

    // listeners de opciones
    container.querySelectorAll('.quiz-option').forEach(opt=>{
      opt.addEventListener('click', ()=>{
        // guardar respuesta
        answers[idx] = parseInt(opt.dataset.idx);
        // refrescar selección visual
        container.querySelectorAll('.quiz-option').forEach(o=>o.classList.remove('selected'));
        opt.classList.add('selected');
        updateProgress();
      });
    });

    // Tips on/off
    el('#showTips').addEventListener('change', (e)=>{
      $q('tipBox')?.classList.toggle('d-none', !e.target.checked);
    });
    // estado inicial del tip
    $q('tipBox')?.classList.toggle('d-none', !el('#showTips').checked);

    // botones
    el('#prevBtn').disabled = (idx === 0);
    el('#nextBtn').textContent = (idx === QUESTIONS.length-1) ? 'Finalizar' : 'Siguiente';
  }

  function renderNav(){
    const grid = $q('navGrid');
    grid.innerHTML = '';
    for (let i=0;i<QUESTIONS.length;i++){
      const answered = (answers[i] !== undefined);
      const m = marked.has(i);
      const btn = document.createElement('button');
      btn.className = 'btn btn-sm ' + (answered ? 'btn-primary' : 'btn-outline-secondary');
      btn.textContent = (i+1);
      btn.title = answered ? 'Respondida' : 'Sin responder';
      if (m) btn.classList.add('border-warning');
      btn.addEventListener('click', ()=>{ current = i; renderQuestion(current); });
      grid.appendChild(btn);
    }
  }

  function updateProgress(){
    const answered = Object.keys(answers).length;
    const pct = Math.round((answered/QUESTIONS.length)*100);
    $q('progText').textContent = `${answered} / ${QUESTIONS.length}`;
    $q('bar').style.width = pct + '%';
    renderNav();
  }

  function next(){
    if (current < QUESTIONS.length-1){
      current++;
      renderQuestion(current);
    } else {
      grade();
    }
  }
  function prev(){
    if (current > 0){ current--; renderQuestion(current); }
  }
  function toggleMark(){
    if (marked.has(current)) marked.delete(current); else marked.add(current);
    renderQuestion(current); renderNav();
  }
  function clearAnswer(){
    delete answers[current];
    renderQuestion(current); updateProgress();
  }

  function grade(){
    // detener temporizador
    if (timerId) clearInterval(timerId);

    let score = 0;
    const review = [];
    QUESTIONS.forEach((q, i)=>{
      const ans = answers[i];
      const ok = (ans === q.correct);
      if (ok) score++;
      review.push({idx:i, ok, q, ans});
    });

    // Pinta el resultado
    $q('scoreText').textContent = `${score}`;
    const pass = (score >= PASS_SCORE);
    $q('passBadge').classList.toggle('d-none', !pass);
    $q('failBadge').classList.toggle('d-none', pass);

    const reviewEl = $q('reviewList');
    reviewEl.innerHTML = '';
    review.forEach(r=>{
      const li = document.createElement('div');
      li.className = 'list-group-item';
      const ansTxt = (r.ans !== undefined) ? `${String.fromCharCode(65+r.ans)}. ${r.q.options[r.ans]}` : '<em>Sin responder</em>';
      const corTxt = `${String.fromCharCode(65+r.q.correct)}. ${r.q.options[r.q.correct]}`;
      li.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold">P${r.idx+1}: ${r.q.text}</div>
            <div class="small ${r.ok?'text-success':'text-danger'}">
              Tu respuesta: ${ansTxt}
              ${r.ok?'':'<br>Respuesta correcta: '+corTxt}
            </div>
            <div class="small text-muted mt-1"><strong>Ref:</strong> ${r.q.ref ?? ''} — ${r.q.tip ?? ''}</div>
          </div>
          <span class="badge ${r.ok?'bg-success':'bg-danger'}">${r.ok?'Correcta':'Incorrecta'}</span>
        </div>`;
      reviewEl.appendChild(li);
    });

    // mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('resultModal'));
    modal.show();
  }

  function startTimer(){
    function tick(){
      const m = Math.floor(timerSec/60);
      const s = (timerSec%60).toString().padStart(2,'0');
      $q('timer').textContent = `${m}:${s}`;
      if (timerSec<=0){ grade(); }
      timerSec--;
    }
    tick();
    timerId = setInterval(tick, 1000);
  }

  // eventos
  $q('nextBtn').addEventListener('click', next);
  $q('prevBtn').addEventListener('click', prev);
  $q('markBtn').addEventListener('click', toggleMark);
  $q('clearBtn').addEventListener('click', clearAnswer);
  $q('finishBtn').addEventListener('click', grade);
  $q('retryBtn').addEventListener('click', ()=>{ location.reload(); });

  // init
  renderQuestion(current);
  renderNav();
  updateProgress();
  startTimer();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

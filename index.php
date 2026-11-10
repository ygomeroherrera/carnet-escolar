<?php
$pagina = 'carnets';
$titulo = 'Carnets';
$extra_head = <<<'CSS'
<style>
  .contenedor { display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; align-items: flex-start; }
  .contenedor .panel { width: 340px; }
  input[readonly] { background: #f3f5f7; }
  .panel button { width: 100%; margin-top: 16px; padding: 10px; font-size: 15px; }
  .panel button.sec { background: #43a047; }
  button:disabled { background: #b0bec5; cursor: not-allowed; }
  #msg { margin-top: 10px; font-size: 13px; min-height: 18px; }
  .ok { color: #2e7d32; } .err { color: #c62828; }

  /* Buscador */
  .buscador { position: relative; }
  #resultados { position: absolute; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #aab;
    border-radius: 0 0 6px 6px; max-height: 260px; overflow-y: auto; z-index: 10; display: none; box-shadow: 0 4px 10px rgba(0,0,0,.2); }
  #resultados div { padding: 8px 10px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #eee; }
  #resultados div:hover { background: #e3f2fd; }
  #resultados small { color: #607d8b; display: block; }
  #resultados .vacio { color: #888; cursor: default; }

  /* Carnet */
  .carnet { width: 324px; height: 204px; border-radius: 12px; background: #fff; border: 2px solid #1e88e5;
    box-shadow: 0 2px 8px rgba(0,0,0,.25); overflow: hidden; display: flex; flex-direction: column; }
  .cabecera { background: linear-gradient(90deg, #1e88e5, #4fc3f7); color: #fff; text-align: center; padding: 8px 6px; }
  .cabecera .ie { font-size: 17px; font-weight: bold; }
  .cabecera .sub { font-size: 11px; letter-spacing: 1px; }
  .cuerpo { flex: 1; display: flex; padding: 10px; gap: 10px; }
  .foto { width: 80px; height: 100px; border: 2px dashed #90a4ae; border-radius: 6px; display: flex; overflow: hidden;
    align-items: center; justify-content: center; font-size: 11px; color: #78909c; text-align: center; flex-shrink: 0; }
  .foto img { width: 100%; height: 100%; object-fit: cover; }
  .foto.con { border-style: solid; border-color: #1e88e5; }
  .datos { font-size: 11px; line-height: 1.45; min-width: 0; flex: 1; }
  .datos .nombre { font-size: 13px; font-weight: bold; color: #0d47a1; margin-bottom: 4px; }
  .datos b { color: #455a64; }
  .qr { width: 64px; flex-shrink: 0; text-align: center; font-size: 8px; color: #607d8b; align-self: center; }
  .qr svg { width: 64px; height: 64px; display: block; }
  .qr.vacio { height: 64px; border: 2px dashed #cfd8dc; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
  .pie { background: #ffca28; text-align: center; font-size: 10px; padding: 4px; font-weight: bold; }

  @media print {
    .panel { display: none; }
    .carnet { box-shadow: none; }
  }
</style>
CSS;
require 'menu.php';
?>
<h1>Generador de Carnets</h1>
<div class="contenedor">
  <div class="panel">
    <div class="buscador">
      <label for="buscar">Buscar alumno (nombre, apellido o DNI)</label>
      <input type="text" id="buscar" autocomplete="off" placeholder="Escriba al menos 2 letras...">
      <div id="resultados"></div>
    </div>

    <label>Nombres <input type="text" id="nombre" readonly></label>
    <label>Apellidos <input type="text" id="apellido" readonly></label>
    <label>Nivel <input type="text" id="nivel" readonly></label>
    <label>Grado <input type="text" id="grado" readonly></label>
    <label>Sección <input type="text" id="seccion" readonly></label>
    <label>Apoderado <input type="text" id="apoderado" readonly></label>

    <label>Fotografía (JPG, PNG o WEBP, máx. 3 MB)
      <input type="file" id="archivo" accept="image/jpeg,image/png,image/webp" disabled>
    </label>
    <div id="msg"></div>
    <button type="button" class="sec" id="imprimir" disabled onclick="window.print()">Imprimir carnet</button>
  </div>

  <div class="carnet">
    <div class="cabecera">
      <div class="ie">IE Gotitas del Cielo</div>
      <div class="sub">CARNET ESCOLAR</div>
    </div>
    <div class="cuerpo">
      <div class="foto" id="c-foto">FOTO</div>
      <div class="datos">
        <div class="nombre" id="c-nombre">Nombre Apellido</div>
        <div><b>Nivel:</b> <span id="c-nivel">-</span></div>
        <div><b>Grado:</b> <span id="c-grado">-</span> &nbsp; <b>Sección:</b> <span id="c-seccion">-</span></div>
        <div><b>Apoderado:</b> <span id="c-apoderado">-</span></div>
      </div>
      <div class="qr vacio" id="c-qr">QR</div>
    </div>
    <div class="pie">Año escolar <span id="anio"></span></div>
  </div>
</div>

<script src="js/qrcode.js"></script>
<script>
  const $ = id => document.getElementById(id);
  let alumnoId = null, temporizador = null;
  $('anio').textContent = new Date().getFullYear();

  const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  function mensaje(txt, clase) { $('msg').textContent = txt; $('msg').className = clase || ''; }

  function mostrarFoto(ruta) {
    const f = $('c-foto');
    if (ruta) { f.innerHTML = '<img src="' + esc(ruta) + '?t=' + Date.now() + '" alt="Foto">'; f.classList.add('con'); }
    else { f.textContent = 'FOTO'; f.classList.remove('con'); }
  }

  // El QR contiene el DNI del alumno (o ID-n si no tiene DNI)
  function mostrarQR(a) {
    const caja = $('c-qr');
    const codigo = a.dni ? String(a.dni) : 'ID-' + a.id;
    const qr = qrcode(0, 'M');
    qr.addData(codigo);
    qr.make();
    caja.className = 'qr';
    caja.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true }) + '<div>' + esc(codigo) + '</div>';
  }

  function seleccionar(a) {
    alumnoId = a.id;
    $('nombre').value = a.nombres;   $('apellido').value = a.apellidos;
    $('nivel').value = a.nivel;      $('grado').value = a.grado;
    $('seccion').value = a.seccion;  $('apoderado').value = a.apoderado;
    $('c-nombre').textContent = a.nombres + ' ' + a.apellidos;
    $('c-nivel').textContent = a.nivel;  $('c-grado').textContent = a.grado;
    $('c-seccion').textContent = a.seccion;  $('c-apoderado').textContent = a.apoderado;
    mostrarFoto(a.foto);
    mostrarQR(a);
    $('archivo').disabled = false;  $('archivo').value = '';
    $('imprimir').disabled = false;
    $('resultados').style.display = 'none';
    $('buscar').value = a.nombres + ' ' + a.apellidos;
    mensaje('');
  }

  async function buscar() {
    const q = $('buscar').value.trim();
    const caja = $('resultados');
    if (q.length < 2) { caja.style.display = 'none'; return; }
    try {
      const r = await fetch('buscar.php?q=' + encodeURIComponent(q));
      const lista = await r.json();
      caja.innerHTML = '';
      if (!lista.length) caja.innerHTML = '<div class="vacio">Sin resultados</div>';
      lista.forEach(a => {
        const d = document.createElement('div');
        d.innerHTML = esc(a.nombres + ' ' + a.apellidos) +
          '<small>' + esc(a.nivel + ' - ' + a.grado + ' "' + a.seccion + '"') + '</small>';
        d.onclick = () => seleccionar(a);
        caja.appendChild(d);
      });
      caja.style.display = 'block';
    } catch (e) { mensaje('Error al buscar en la base de datos.', 'err'); }
  }

  $('buscar').addEventListener('input', () => { clearTimeout(temporizador); temporizador = setTimeout(buscar, 250); });
  document.addEventListener('click', e => { if (!e.target.closest('.buscador')) $('resultados').style.display = 'none'; });

  // Abrir con ?id=N carga directamente ese alumno
  const idUrl = new URLSearchParams(location.search).get('id');
  if (idUrl) fetch('buscar.php?id=' + encodeURIComponent(idUrl)).then(r => r.json()).then(l => { if (l[0]) seleccionar(l[0]); });

  $('archivo').addEventListener('change', async function () {
    if (!this.files[0] || !alumnoId) return;
    const fd = new FormData();
    fd.append('id', alumnoId);
    fd.append('foto', this.files[0]);
    mensaje('Subiendo foto...');
    try {
      const r = await fetch('subir_foto.php', { method: 'POST', body: fd });
      const j = await r.json();
      if (j.ok) { mostrarFoto(j.foto); mensaje('Foto guardada.', 'ok'); }
      else mensaje(j.error, 'err');
    } catch (e) { mensaje('Error al subir la foto.', 'err'); }
  });
</script>
</main>
</body>
</html>

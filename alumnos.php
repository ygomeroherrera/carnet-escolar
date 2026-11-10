<?php
session_start();
require 'config.php';

$niveles = ['Inicial', 'Primaria', 'Secundaria'];
$h = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');

function aviso(string $tipo, string $txt) { $_SESSION['aviso'] = [$tipo, $txt]; header('Location: alumnos.php'); exit; }

// ---------- Acciones (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($accion === 'eliminar' && $id) {
        $foto = db()->prepare('SELECT foto FROM alumnos WHERE id = ?');
        $foto->execute([$id]);
        $ruta = $foto->fetchColumn();
        db()->prepare('DELETE FROM alumnos WHERE id = ?')->execute([$id]);
        if ($ruta && is_file(__DIR__ . '/' . $ruta)) @unlink(__DIR__ . '/' . $ruta);
        aviso('ok', 'Alumno eliminado.');
    }

    if ($accion === 'guardar') {
        $d = [];
        foreach (['dni', 'nombres', 'apellidos', 'nivel', 'grado', 'seccion', 'apoderado'] as $c) $d[$c] = trim($_POST[$c] ?? '');
        $d['seccion'] = mb_strtoupper($d['seccion']);

        $error = null;
        foreach (['nombres', 'apellidos', 'nivel', 'grado', 'seccion', 'apoderado'] as $c)
            if ($d[$c] === '') $error = 'Complete todos los campos obligatorios.';
        if (!in_array($d['nivel'], $niveles, true)) $error = 'Nivel no válido.';
        if ($d['dni'] !== '' && !preg_match('/^\d{8,12}$/', $d['dni'])) $error = 'El DNI debe tener solo números (8 a 12 dígitos).';

        if ($error) {
            $_SESSION['form'] = $d + ['id' => $id];
            aviso('err', $error);
        }
        $d['dni'] = $d['dni'] === '' ? null : $d['dni'];

        if ($id) {
            db()->prepare('UPDATE alumnos SET dni=:dni, nombres=:nombres, apellidos=:apellidos, nivel=:nivel, grado=:grado, seccion=:seccion, apoderado=:apoderado WHERE id=:id')
                ->execute($d + ['id' => $id]);
        } else {
            db()->prepare('INSERT INTO alumnos (dni,nombres,apellidos,nivel,grado,seccion,apoderado) VALUES (:dni,:nombres,:apellidos,:nivel,:grado,:seccion,:apoderado)')
                ->execute($d);
            $id = (int)db()->lastInsertId();
        }

        // Foto opcional
        if (isset($_FILES['foto'])) {
            [$ruta, $errFoto] = guardar_foto($_FILES['foto'], $id);
            if ($ruta) db()->prepare('UPDATE alumnos SET foto=? WHERE id=?')->execute([$ruta, $id]);
            if ($errFoto) aviso('err', 'Alumno guardado, pero la foto falló: ' . $errFoto);
        }
        aviso('ok', 'Alumno guardado correctamente.');
    }
}

// ---------- Datos para la vista ----------
$aviso = $_SESSION['aviso'] ?? null;  unset($_SESSION['aviso']);
$form  = $_SESSION['form'] ?? null;   unset($_SESSION['form']);

$editar = null;
if (isset($_GET['editar'])) {
    $st = db()->prepare('SELECT * FROM alumnos WHERE id = ?');
    $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch() ?: null;
}
$f = $form ?? $editar ?? [];
$mostrarForm = $editar || $form || isset($_GET['nuevo']);

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM alumnos WHERE 1=1';
$params = [];
if ($q !== '') {
    foreach (preg_split('/\s+/', $q) as $i => $p) {
        $sql .= " AND CONCAT(nombres,' ',apellidos,' ',IFNULL(dni,'')) LIKE :p$i";
        $params[":p$i"] = "%$p%";
    }
}
$sql .= ' ORDER BY apellidos, nombres';
$st = db()->prepare($sql);
$st->execute($params);
$alumnos = $st->fetchAll();

$pagina = 'alumnos';
$titulo = 'Alumnos';
$extra_head = '<style>
  .barra { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
  .barra form { display:flex; gap:8px; flex:1; min-width:240px; }
  .barra input { margin:0; }
  .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:0 16px; }
  .aviso { padding:10px 14px; border-radius:6px; margin-bottom:16px; font-size:14px; }
  .aviso.ok { background:#e8f5e9; color:#2e7d32; } .aviso.err { background:#ffebee; color:#c62828; }
  table { width:100%; border-collapse:collapse; font-size:14px; }
  th, td { padding:8px 10px; text-align:left; border-bottom:1px solid #e3e8ee; vertical-align:middle; }
  th { background:#f3f6fa; }
  .tabla { overflow-x:auto; }
  td img { width:36px; height:46px; object-fit:cover; border-radius:4px; display:block; }
  .sinfoto { width:36px; height:46px; background:#eceff1; border-radius:4px; }
  td form { display:inline; }
</style>';
require 'menu.php';
?>
<h1>Alumnos</h1>

<?php if ($aviso): ?><div class="aviso <?= $aviso[0] ?>"><?= $h($aviso[1]) ?></div><?php endif; ?>

<?php if ($mostrarForm): ?>
<div class="panel" style="margin-bottom:20px">
  <h2 style="margin-top:0"><?= !empty($f['id']) ? 'Editar alumno' : 'Nuevo alumno' ?></h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
    <div class="grid">
      <label>DNI (opcional) <input name="dni" value="<?= $h($f['dni'] ?? '') ?>" maxlength="12" inputmode="numeric"></label>
      <label>Nombres * <input name="nombres" value="<?= $h($f['nombres'] ?? '') ?>" required></label>
      <label>Apellidos * <input name="apellidos" value="<?= $h($f['apellidos'] ?? '') ?>" required></label>
      <label>Nivel *
        <select name="nivel" required>
          <option value="">Seleccione</option>
          <?php foreach ($niveles as $n): ?>
            <option <?= ($f['nivel'] ?? '') === $n ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Grado * <input name="grado" value="<?= $h($f['grado'] ?? '') ?>" required placeholder="Ej: 3° o 5 años"></label>
      <label>Sección * <input name="seccion" value="<?= $h($f['seccion'] ?? '') ?>" required maxlength="5" placeholder="Ej: A"></label>
      <label>Apoderado * <input name="apoderado" value="<?= $h($f['apoderado'] ?? '') ?>" required></label>
      <label>Fotografía (opcional)
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
      </label>
    </div>
    <div style="margin-top:16px; display:flex; gap:8px">
      <button type="submit" class="verde btn">Guardar</button>
      <a href="alumnos.php" class="btn gris">Cancelar</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="panel">
  <div class="barra">
    <form method="get">
      <input name="q" value="<?= $h($q) ?>" placeholder="Buscar por nombre, apellido o DNI...">
      <button type="submit">Buscar</button>
      <?php if ($q !== ''): ?><a href="alumnos.php" class="btn gris">Limpiar</a><?php endif; ?>
    </form>
    <a href="alumnos.php?nuevo=1" class="btn verde">+ Nuevo alumno</a>
  </div>

  <div class="tabla">
  <table>
    <tr><th>Foto</th><th>DNI</th><th>Alumno</th><th>Nivel</th><th>Grado / Sec.</th><th>Apoderado</th><th></th></tr>
    <?php foreach ($alumnos as $a): ?>
    <tr>
      <td><?= $a['foto'] ? '<img src="' . $h($a['foto']) . '" alt="">' : '<div class="sinfoto"></div>' ?></td>
      <td><?= $h($a['dni']) ?></td>
      <td><?= $h($a['apellidos'] . ', ' . $a['nombres']) ?></td>
      <td><?= $h($a['nivel']) ?></td>
      <td><?= $h($a['grado'] . ' "' . $a['seccion'] . '"') ?></td>
      <td><?= $h($a['apoderado']) ?></td>
      <td style="white-space:nowrap">
        <a class="btn chico" href="alumnos.php?editar=<?= (int)$a['id'] ?>">Editar</a>
        <form method="post" onsubmit="return confirm('¿Eliminar a este alumno?')">
          <input type="hidden" name="accion" value="eliminar">
          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <button class="rojo chico" type="submit">Eliminar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$alumnos): ?><tr><td colspan="7" style="color:#888">No hay alumnos<?= $q !== '' ? ' que coincidan con la búsqueda' : '' ?>.</td></tr><?php endif; ?>
  </table>
  </div>
  <p style="color:#78909c; font-size:13px; margin-bottom:0"><?= count($alumnos) ?> alumno(s)</p>
</div>
</main>
</body>
</html>

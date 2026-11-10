<?php
// Uso: $pagina = 'carnets'; require 'menu.php';  ... contenido ... </main></body></html>
$pagina = $pagina ?? '';
$titulo = $titulo ?? 'Sistema de Carnets';
$items = ['carnets' => ['index.php', '🪪 Carnets'], 'alumnos' => ['alumnos.php', '🎓 Alumnos']];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titulo) ?> - IE Gotitas del Cielo</title>
<link rel="stylesheet" href="estilos.css">
<?= $extra_head ?? '' ?>
</head>
<body>
<nav class="sidebar">
  <div class="marca">IE Gotitas del Cielo<small>Sistema de carnets</small></div>
  <?php foreach ($items as $clave => [$url, $texto]): ?>
    <a href="<?= $url ?>" class="<?= $clave === $pagina ? 'activo' : '' ?>"><?= $texto ?></a>
  <?php endforeach; ?>
</nav>
<main>

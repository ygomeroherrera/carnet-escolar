<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');

function fallo(string $msg) { echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE); exit; }

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) fallo('Primero seleccione un alumno.');
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) fallo('No se recibió la imagen.');
if ($_FILES['foto']['size'] > 3 * 1024 * 1024) fallo('La imagen supera 3 MB.');

// Validar que realmente sea una imagen JPG/PNG/WEBP
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['foto']['tmp_name']);
$ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
if (!$ext) fallo('Formato no permitido (use JPG, PNG o WEBP).');

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$nombre = 'alumno_' . $id . '_' . time() . '.' . $ext;
if (!move_uploaded_file($_FILES['foto']['tmp_name'], "$dir/$nombre")) fallo('No se pudo guardar el archivo.');

$ruta = 'uploads/' . $nombre;
$st = db()->prepare('UPDATE alumnos SET foto = ? WHERE id = ?');
$st->execute([$ruta, $id]);

echo json_encode(['ok' => true, 'foto' => $ruta]);

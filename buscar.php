<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) { echo '[]'; exit; }

// Cada palabra escrita debe aparecer en nombres o apellidos
$sql = 'SELECT id, dni, nombres, apellidos, nivel, grado, seccion, apoderado, foto FROM alumnos WHERE 1=1';
$params = [];
foreach (preg_split('/\s+/', $q) as $i => $palabra) {
    $sql .= " AND CONCAT(nombres,' ',apellidos,' ',IFNULL(dni,'')) LIKE :p$i";
    $params[":p$i"] = '%' . $palabra . '%';
}
$sql .= ' ORDER BY apellidos, nombres LIMIT 20';

$st = db()->prepare($sql);
$st->execute($params);
echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE);

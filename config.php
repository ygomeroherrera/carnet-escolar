<?php
// Guarda una foto subida en uploads/. Devuelve [ruta|null, error|null]
function guardar_foto(array $f, int $id): array {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($f['error'] !== UPLOAD_ERR_OK) return [null, 'No se recibió la imagen.'];
    if ($f['size'] > 3 * 1024 * 1024) return [null, 'La imagen supera 3 MB.'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) return [null, 'Formato de imagen no permitido (JPG, PNG o WEBP).'];
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $nombre = "alumno_{$id}_" . time() . ".$ext";
    if (!move_uploaded_file($f['tmp_name'], "$dir/$nombre")) return [null, 'No se pudo guardar la imagen.'];
    return ["uploads/$nombre", null];
}

// Conexión a la base de datos "carnet" (XAMPP por defecto: root sin contraseña)
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=localhost;dbname=carnet;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

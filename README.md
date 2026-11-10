# Sistema de Carnets - IE Gotitas del Cielo

Sistema en PHP + MySQL para buscar alumnos, generar e imprimir carnets escolares con fotografía.

## Instalación (XAMPP)
1. Copiar la carpeta a `C:\xampp\htdocs\carnet`.
2. Iniciar Apache y MySQL.
3. Crear la base de datos importando `setup.sql` (phpMyAdmin o `mysql -u root < setup.sql`).
4. Ajustar usuario/contraseña en `config.php` si es necesario.
5. Abrir `http://localhost/carnet/`.

## Módulos
- **Carnets** (`index.php`): buscar alumno, subir foto, código QR e imprimir carnet.
- **Alumnos** (`alumnos.php`): crear, editar y eliminar alumnos.

Las fotos se guardan en `uploads/` (en este repositorio solo hay fotos de ejemplo).

Versión actual: ver [CHANGELOG.md](CHANGELOG.md).

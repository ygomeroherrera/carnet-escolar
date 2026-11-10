CREATE DATABASE IF NOT EXISTS carnet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carnet;

CREATE TABLE IF NOT EXISTS alumnos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dni VARCHAR(12) NULL,
  nombres VARCHAR(80) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  nivel ENUM('Inicial','Primaria','Secundaria') NOT NULL,
  grado VARCHAR(20) NOT NULL,
  seccion VARCHAR(5) NOT NULL,
  apoderado VARCHAR(120) NOT NULL,
  foto VARCHAR(255) NULL,
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (nombres),
  INDEX (apellidos)
) ENGINE=InnoDB;

INSERT INTO alumnos (dni, nombres, apellidos, nivel, grado, seccion, apoderado) VALUES
('70123456','Juan Carlos','Pérez Rojas','Primaria','3°','A','María Rojas Díaz'),
('70123457','Juan Diego','Quispe Mamani','Secundaria','1°','B','Pedro Quispe Lazo'),
('70123458','Juana Lucía','Torres Vega','Inicial','5 años','C','Rosa Vega Ríos'),
('70123459','Ana María','Flores Huamán','Primaria','5°','A','Luis Flores Cruz'),
('70123460','Ana Sofía','Ramos Ccori','Secundaria','3°','B','Carmen Ccori Paz'),
('70123461','Luis Miguel','Castillo Soto','Primaria','6°','B','Elena Soto Ayala');

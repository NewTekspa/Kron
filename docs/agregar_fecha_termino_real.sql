-- Script para agregar la columna fecha_termino_real a la tabla kron_tasks
-- Ejecutar este script en el servidor de producción

-- Agregar columna fecha_termino_real si no existe
ALTER TABLE kron_tasks 
ADD COLUMN IF NOT EXISTS fecha_termino_real DATETIME DEFAULT NULL AFTER estado;

-- Actualizar las tareas terminadas existentes con la fecha updated_at
UPDATE kron_tasks 
SET fecha_termino_real = updated_at 
WHERE estado = 'terminada' AND fecha_termino_real IS NULL;

-- Verificar que la columna fue agregada
SELECT 'Columna fecha_termino_real agregada correctamente' AS resultado;
SHOW COLUMNS FROM kron_tasks LIKE 'fecha_termino_real';

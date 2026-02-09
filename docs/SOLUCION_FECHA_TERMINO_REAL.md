# Solución al error de columna fecha_termino_real

## Problema
Error en producción: `Column not found: 1054 Unknown column 't.fecha_termino_real'`

## Solución aplicada

### 1. Modificación temporal del código
El código en `app/Models/Task.php` ha sido actualizado para detectar automáticamente si la columna `fecha_termino_real` existe. Si no existe, usará `updated_at` como alternativa.

**Esto permite que la aplicación funcione inmediatamente sin errores.**

### 2. Solución permanente (recomendada)
Para tener la funcionalidad completa, ejecuta el siguiente script SQL en tu base de datos de producción:

**Archivo:** `docs/agregar_fecha_termino_real.sql`

Puedes ejecutarlo desde:
- phpMyAdmin (pestaña SQL)
- Consola de MySQL
- Panel de control de tu hosting

```sql
ALTER TABLE kron_tasks 
ADD COLUMN IF NOT EXISTS fecha_termino_real DATETIME DEFAULT NULL AFTER estado;

UPDATE kron_tasks 
SET fecha_termino_real = updated_at 
WHERE estado = 'terminada' AND fecha_termino_real IS NULL;
```

### 3. Para instalaciones nuevas
Los archivos de schema (`docs/kron_schema.sql` y `docs/crear_tablas_completo.sql`) han sido actualizados para incluir la columna desde el inicio.

## Próximos pasos

1. **Inmediato:** El código ya funciona sin errores (usa `updated_at` si falta la columna)
2. **Recomendado:** Ejecuta el script SQL para agregar la columna y tener la funcionalidad completa
3. Guarda los cambios en GitHub cuando estés listo

## Verificación
Después de ejecutar el script SQL, verifica que la columna existe con:
```sql
SHOW COLUMNS FROM kron_tasks LIKE 'fecha_termino_real';
```

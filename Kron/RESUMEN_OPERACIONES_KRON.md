# RESUMEN DE OPERACIONES Y AJUSTES REALIZADOS EN KRON

## 1. Estandarización de Tablas y Roles
- Se creó/ajustó la estructura de las tablas `kron_roles` y `kron_user_roles` según el modelo funcional.
- Se eliminaron los roles no válidos: **Usuario** y **Supervisor** (en cualquier variante de mayúsculas/minúsculas).
- Se insertaron/actualizaron los roles válidos:
  - administrador
  - jefe
  - subgerente
  - colaborador

## 2. Script de actualización automática
- Se generó el script `public/actualizar_roles.php` que:
  - Crea las tablas si no existen
  - Agrega columnas faltantes
  - Elimina roles no válidos y sus asignaciones
  - Inserta/actualiza los roles correctos
  - Muestra el estado actual de roles y usuarios
  - Da instrucciones para asignar roles manualmente

## 3. Jerarquía de permisos funcional
- **administrador**: ve y gestiona todo el sistema
- **subgerente**: ve y gestiona todos los usuarios de los equipos que supervisa
- **jefe**: ve y gestiona a su equipo (él mismo y sus colaboradores)
- **colaborador**: solo ve y gestiona sus propias tareas

## 4. Instrucciones de uso
1. Subir `actualizar_roles.php` al host (carpeta `public/`)
2. Acceder vía navegador para ejecutar la actualización
3. Revisar el estado final y reasignar roles si algún usuario quedó sin rol

## 5. Notas adicionales
- El script es seguro: no borra datos de usuarios ni tareas
- Si se agregan usuarios nuevos, asignarles el rol correspondiente usando la tabla `kron_user_roles`
- El archivo puede ser reutilizado para futuras migraciones o restauraciones

---

**Fecha de última actualización:** 7 de febrero de 2026

---


# Consultas SQL — Validación de datos (CRM Alcaldía)

Consultas para validar en la base de datos que todo se esté guardando bien.

**Dónde ejecutarlas:** terminal del contenedor `**espocrm-db`** en **Dokploy** (producción).

---

## Dónde y cómo ejecutarlas

### 1. Abrir la terminal en Dokploy

1. Entra a **Dokploy**.
2. Abre el proyecto **CRM Alcaldía**.
3. Entra al servicio `**espocrm-db`**.
4. Haz clic en **Terminal**.

Ahí es donde debes correr todo. No uses otra terminal ni tu PC.

### 2. Conectarte a PostgreSQL

En esa terminal escribe:

```bash
psql -U "$POSTGRES_USER" -d "$POSTGRESpuedes p_DB"
```

Te pedirá la contraseña: es el valor de `**POSTGRES_PASSWORD**` (en Dokploy → proyecto → **Environment** / variables del stack).

Cuando veas el prompt `nombre_base=#`, ya estás dentro y egar las consultas.

### 3. Ejecutar una consulta

1. Copia el bloque SQL de este documento (desde `SELECT` hasta el `;`).
2. Pégalo en la terminal.
3. Presiona **Enter**.

Prueba con esta:

```sql
SELECT COUNT(*) AS casos_activos FROM "case" WHERE deleted = false;
```

### 4. Salir

Cuando termines, escribe:

```bash
\q
```

---

## Comandos útiles (dentro de psql)


| Comando     | Para qué sirve                            |
| ----------- | ----------------------------------------- |
| `\dt`       | Ver tablas                                |
| `\d "case"` | Ver columnas de casos                     |
| `\x`        | Vista expandida (filas con muchos campos) |
| `\q`        | Salir                                     |


---

## Convenciones importantes


| Tema           | Detalle                                                                                                       |
| -------------- | ------------------------------------------------------------------------------------------------------------- |
| Tabla de casos | Se llama `"case"` (palabra reservada). Siempre usar comillas: `"case"`.                                       |
| Borrado lógico | EspoCRM no borra filas; marca `deleted = true`. Filtra `deleted = false` en casi todas las consultas.         |
| IDs            | Formato EspoCRM, 17 caracteres (ej. `664a1b2c3d4e5f678`).                                                     |
| Campos custom  | Prefijo `c_` en BD (ej. `cNumeroRadicado` → `c_numero_radicado`).                                             |
| Fechas         | `created_at` / `modified_at` en UTC; campos de negocio como `c_fecha_caso` en hora local según configuración. |


---

## 1. Resumen general del sistema

```sql
-- Conteo rápido por entidad principal
SELECT 'case' AS entidad, COUNT(*) AS total
FROM "case" WHERE deleted = false
UNION ALL
SELECT 'acta_visita', COUNT(*) FROM acta_visita WHERE deleted = false
UNION ALL
SELECT 'actuo_archivo', COUNT(*) FROM actuo_archivo WHERE deleted = false
UNION ALL
SELECT 'comunicacion_caso', COUNT(*) FROM comunicacion_caso WHERE deleted = false
UNION ALL
SELECT 'asignacion_historial', COUNT(*) FROM asignacion_historial WHERE deleted = false
UNION ALL
SELECT 'contact', COUNT(*) FROM contact WHERE deleted = false
UNION ALL
SELECT 'account', COUNT(*) FROM account WHERE deleted = false
UNION ALL
SELECT 'document', COUNT(*) FROM document WHERE deleted = false
UNION ALL
SELECT 'user', COUNT(*) FROM "user" WHERE deleted = false
UNION ALL
SELECT 'role', COUNT(*) FROM role WHERE deleted = false;
```

---

## 2. Roles operativos

```sql
-- Los 4 roles base deben existir
SELECT id, name, created_at
FROM role
WHERE deleted = false
ORDER BY name;
```

Esperado: **Inspección**, **Radicación**, **Asignación**, **Patrullaje**.

```sql
-- Usuarios y sus roles
SELECT
    u.user_name,
    u.first_name,
    u.last_name,
    u.is_active,
    r.name AS rol
FROM "user" u
JOIN role_user ru ON ru.user_id = u.id AND ru.deleted = false
JOIN role r ON r.id = ru.role_id AND r.deleted = false
WHERE u.deleted = false
ORDER BY u.user_name, r.name;
```

```sql
-- Usuarios sin rol asignado
SELECT u.id, u.user_name, u.first_name, u.last_name
FROM "user" u
LEFT JOIN role_user ru ON ru.user_id = u.id AND ru.deleted = false
WHERE u.deleted = false
  AND u.type = 'regular'
  AND ru.id IS NULL;
```

---

## 3. Casos — listado y detalle

### Últimos casos creados

```sql
SELECT
    c.id,
    c.c_numero_radicado,
    c.c_expediente,
    c.status,
    c.c_fecha_caso,
    c.c_nombre_peticionario,
    c.c_apellido_peticionario,
    c.c_recurso_tema,
    u.user_name AS asignado_a,
    cb.user_name AS creado_por,
    c.created_at
FROM "case" c
LEFT JOIN "user" u ON u.id = c.assigned_user_id AND u.deleted = false
LEFT JOIN "user" cb ON cb.id = c.created_by_id AND cb.deleted = false
WHERE c.deleted = false
ORDER BY c.created_at DESC
LIMIT 20;
```

### Un caso específico (por radicado)

```sql
-- Cambia el valor del radicado
SELECT *
FROM "case"
WHERE deleted = false
  AND c_numero_radicado = 'ENV-AIR-001-2026';
```

### Casos por estado

```sql
SELECT
    status,
    COUNT(*) AS cantidad
FROM "case"
WHERE deleted = false
GROUP BY status
ORDER BY cantidad DESC;
```

Estados esperados en el flujo:

- `Pendiente de radicacion`
- `Radicado`
- `Asignado`
- `En proceso`
- `Visita realizada`
- `Visita aprobada`
- `Finalizado`
- `Proceso cerrado`

---

## 4. Validar campos obligatorios del caso

### Casos recién creados sin datos mínimos

```sql
SELECT
    id,
    c_numero_radicado,
    status,
    c_fecha_caso,
    c_tipo_persona_peticionario,
    c_documento_peticionario,
    c_nombre_peticionario,
    description IS NULL OR TRIM(description) = '' AS sin_descripcion,
    created_at
FROM "case"
WHERE deleted = false
  AND (
        c_fecha_caso IS NULL
     OR c_documento_peticionario IS NULL OR TRIM(c_documento_peticionario) = ''
     OR c_nombre_peticionario IS NULL OR TRIM(c_nombre_peticionario) = ''
     OR description IS NULL OR TRIM(description) = ''
  )
ORDER BY created_at DESC;
```

### Radicación y expediente

```sql
SELECT
    id,
    c_numero_radicado,
    c_expediente,
    c_radicado_modo,
    c_radicado_siglas,
    c_radicado_anio,
    status,
    created_at
FROM "case"
WHERE deleted = false
ORDER BY created_at DESC
LIMIT 30;
```

```sql
-- Radicados duplicados (no debería haber)
SELECT c_numero_radicado, COUNT(*) AS veces
FROM "case"
WHERE deleted = false
  AND c_numero_radicado IS NOT NULL
  AND TRIM(c_numero_radicado) <> ''
GROUP BY c_numero_radicado
HAVING COUNT(*) > 1;
```

### Peticionario e infractor (perjudicante)

```sql
SELECT
    id,
    c_numero_radicado,
    c_tipo_persona_peticionario,
    c_documento_peticionario,
    c_nombre_peticionario,
    c_apellido_peticionario,
    c_telefono_peticionario,
    c_direccion_peticionario,
    c_barrio_peticionario,
    c_tipo_persona_perjudicante,
    c_documento_perjudicante,
    c_nombre_perjudicante,
    c_apellido_perjudicante,
    contact_id AS contacto_peticionario_vinculado,
    c_perjudicante_contact_id,
    c_perjudicante_cuenta_id
FROM "case"
WHERE deleted = false
ORDER BY modified_at DESC
LIMIT 20;
```

### Asignación y reasignación

```sql
SELECT
    c.c_numero_radicado,
    c.status,
    u.user_name AS patrullero_asignado,
    c.c_motivo_reasignacion,
    c.modified_at
FROM "case" c
LEFT JOIN "user" u ON u.id = c.assigned_user_id AND u.deleted = false
WHERE c.deleted = false
  AND c.assigned_user_id IS NOT NULL
ORDER BY c.modified_at DESC;
```

### Vencimientos

```sql
SELECT
    c_numero_radicado,
    status,
    c_fecha_vencimiento,
    CASE
        WHEN c_fecha_vencimiento < CURRENT_DATE THEN 'Vencido'
        WHEN c_fecha_vencimiento <= CURRENT_DATE + INTERVAL '3 days' THEN 'Próximo a vencer'
        ELSE 'Al día'
    END AS semaforo
FROM "case"
WHERE deleted = false
  AND c_fecha_vencimiento IS NOT NULL
  AND status NOT IN ('Finalizado', 'Proceso cerrado')
ORDER BY c_fecha_vencimiento;
```

---

## 5. Actas de visita

```sql
SELECT
    av.id,
    av.numero_radicado,
    av.expediente,
    av.estado,
    av.modo_diligenciamiento,
    av.fecha_visita,
    av.case_id,
    c.status AS estado_caso,
    av.c_formato_acta_visita_pdf_id,
    av.created_at
FROM acta_visita av
LEFT JOIN "case" c ON c.id = av.case_id AND c.deleted = false
WHERE av.deleted = false
ORDER BY av.created_at DESC
LIMIT 20;
```

```sql
-- Casos con acta vinculada
SELECT
    c.c_numero_radicado,
    COUNT(av.id) AS actas
FROM "case" c
JOIN acta_visita av ON av.case_id = c.id AND av.deleted = false
WHERE c.deleted = false
GROUP BY c.c_numero_radicado
ORDER BY actas DESC;
```

```sql
-- Actas sin PDF generado
SELECT id, numero_radicado, case_id, estado, created_at
FROM acta_visita
WHERE deleted = false
  AND c_formato_acta_visita_pdf_id IS NULL;
```

---

## 6. Autos de archivo (ActuoArchivo)

```sql
SELECT
    aa.id,
    aa.numero_radicado,
    aa.fecha_auto,
    aa.estado,
    aa.motivo_archivo,
    aa.case_id,
    aa.c_formato_actuo_archivo_pdf_id,
    aa.created_at
FROM actuo_archivo aa
WHERE aa.deleted = false
ORDER BY aa.created_at DESC
LIMIT 20;
```

---

## 7. Comunicaciones del caso

```sql
SELECT
    cc.id,
    cc.numero_radicado,
    cc.tipo,
    cc.fecha,
    cc.destinatario,
    cc.asunto,
    cc.es_respuesta_final,
    cc.case_id,
    u.user_name AS registrado_por,
    cc.created_at
FROM comunicacion_caso cc
LEFT JOIN "user" u ON u.id = cc.created_by_id AND u.deleted = false
WHERE cc.deleted = false
ORDER BY cc.created_at DESC
LIMIT 30;
```

```sql
-- Comunicaciones huérfanas (sin caso)
SELECT id, numero_radicado, asunto, case_id
FROM comunicacion_caso
WHERE deleted = false
  AND (case_id IS NULL OR case_id = '');
```

---

## 8. Historial de asignaciones

```sql
SELECT
    ah.fecha,
    ah.numero_radicado,
    c.status AS estado_caso_actual,
    ap.user_name AS quien_asigno,
    ra.user_name AS responsable_anterior,
    rn.user_name AS responsable_nuevo,
    ah.motivo
FROM asignacion_historial ah
LEFT JOIN "case" c ON c.id = ah.case_id AND c.deleted = false
LEFT JOIN "user" ap ON ap.id = ah.asignado_por_id AND ap.deleted = false
LEFT JOIN "user" ra ON ra.id = ah.responsable_anterior_id AND ra.deleted = false
LEFT JOIN "user" rn ON rn.id = ah.responsable_nuevo_id AND rn.deleted = false
WHERE ah.deleted = false
ORDER BY ah.fecha DESC
LIMIT 30;
```

---

## 9. Documentos y formatos PDF

```sql
-- Documentos por categoría
SELECT
    c_categoria,
    COUNT(*) AS cantidad
FROM document
WHERE deleted = false
GROUP BY c_categoria
ORDER BY cantidad DESC;
```

```sql
-- Casos con formato de solicitud PDF
SELECT
    c.c_numero_radicado,
    c.status,
    d.name AS documento,
    d.c_categoria,
    d.created_at
FROM "case" c
JOIN document d ON d.id = c.c_formato_solicitud_pdf_id AND d.deleted = false
WHERE c.deleted = false
ORDER BY d.created_at DESC
LIMIT 20;
```

```sql
-- Casos radicados sin PDF de solicitud
SELECT id, c_numero_radicado, status, c_formato_solicitud_pdf_id
FROM "case"
WHERE deleted = false
  AND c_numero_radicado IS NOT NULL
  AND TRIM(c_numero_radicado) <> ''
  AND status <> 'Pendiente de radicacion'
  AND c_formato_solicitud_pdf_id IS NULL;
```

---

## 10. Personas naturales y jurídicas (Contact / Account)

### Contactos (personas naturales)

```sql
SELECT
    id,
    first_name,
    last_name,
    c_tipo_de_documento,
    c_numero_de_documento,
    c_barrio_residencia,
    c_municipio,
    created_at
FROM contact
WHERE deleted = false
ORDER BY created_at DESC
LIMIT 20;
```

```sql
-- Documentos duplicados en contactos
SELECT c_numero_de_documento, COUNT(*) AS veces
FROM contact
WHERE deleted = false
  AND c_numero_de_documento IS NOT NULL
  AND TRIM(c_numero_de_documento) <> ''
GROUP BY c_numero_de_documento
HAVING COUNT(*) > 1;
```

### Cuentas (personas jurídicas / NIT)

```sql
SELECT
    id,
    name,
    c_nit,
    c_sector_economico,
    created_at
FROM account
WHERE deleted = false
ORDER BY created_at DESC
LIMIT 20;
```

---

## 11. Integridad entre entidades

```sql
-- Acta / actuo / comunicación apuntando a caso inexistente o borrado
SELECT 'acta_visita' AS origen, av.id, av.case_id
FROM acta_visita av
LEFT JOIN "case" c ON c.id = av.case_id AND c.deleted = false
WHERE av.deleted = false AND c.id IS NULL

UNION ALL

SELECT 'actuo_archivo', aa.id, aa.case_id
FROM actuo_archivo aa
LEFT JOIN "case" c ON c.id = aa.case_id AND c.deleted = false
WHERE aa.deleted = false AND c.id IS NULL

UNION ALL

SELECT 'comunicacion_caso', cc.id, cc.case_id
FROM comunicacion_caso cc
LEFT JOIN "case" c ON c.id = cc.case_id AND c.deleted = false
WHERE cc.deleted = false AND c.id IS NULL;
```

```sql
-- Radicado en caso vs radicado en acta (deben coincidir)
SELECT
    c.id,
    c.c_numero_radicado AS radicado_caso,
    av.numero_radicado AS radicado_acta,
    av.id AS acta_id
FROM "case" c
JOIN acta_visita av ON av.case_id = c.id AND av.deleted = false
WHERE c.deleted = false
  AND COALESCE(c.c_numero_radicado, '') <> COALESCE(av.numero_radicado, '');
```

---

## 12. Actividad reciente (auditoría)

```sql
-- Últimos cambios registrados en casos
SELECT
    ahr.created_at,
    u.user_name,
    ahr.action,
    ahr.target_id AS case_id,
    c.c_numero_radicado
FROM action_history_record ahr
LEFT JOIN "user" u ON u.id = ahr.user_id AND u.deleted = false
LEFT JOIN "case" c ON c.id = ahr.target_id AND c.deleted = false
WHERE ahr.deleted = false
  AND ahr.target_type = 'Case'
ORDER BY ahr.created_at DESC
LIMIT 30;
```

---

## 13. Checklist rápido después de probar en la UI


| Acción en CRM         | Qué validar en BD                                                                        |
| --------------------- | ---------------------------------------------------------------------------------------- |
| Crear caso            | Nueva fila en `"case"`, `c_fecha_caso`, peticionario, `status = Pendiente de radicacion` |
| Radicar               | `c_numero_radicado`, `c_expediente`, cambio de `status`, `c_formato_solicitud_pdf_id`    |
| Asignar patrullero    | `assigned_user_id`, fila en `asignacion_historial` si hubo reasignación                  |
| Acta de visita        | Fila en `acta_visita` con `case_id`, `c_formato_acta_visita_pdf_id`                      |
| Auto de archivo       | Fila en `actuo_archivo` con `case_id`                                                    |
| Comunicación          | Fila en `comunicacion_caso` con `case_id`                                                |
| Crear usuario con rol | Fila en `role_user` enlazando `user` y `role`                                            |


---

## 14. Consultas útiles por ID de caso

Si tienes el ID del caso (visible en la URL: `#Case/view/XXXXXXXX`), sustituye `'CASE_ID_AQUI'` y pégalo en la terminal de `**espocrm-db**` (ya dentro de `psql`):

```sql
SELECT * FROM "case" WHERE id = 'CASE_ID_AQUI' AND deleted = false;

SELECT * FROM acta_visita WHERE case_id = 'CASE_ID_AQUI' AND deleted = false;
SELECT * FROM actuo_archivo WHERE case_id = 'CASE_ID_AQUI' AND deleted = false;
SELECT * FROM comunicacion_caso WHERE case_id = 'CASE_ID_AQUI' AND deleted = false;
SELECT * FROM asignacion_historial WHERE case_id = 'CASE_ID_AQUI' AND deleted = false;
```

---

## Notas

- Todas las consultas van en la terminal de `**espocrm-db**` en Dokploy, después de entrar con `psql`.
- La contraseña está en Dokploy → variables del proyecto → `**POSTGRES_PASSWORD**`.
- Si ves 0 filas pero hay datos en el CRM, revisa que estés en el proyecto correcto de Dokploy.
- Tras un wipe de datos, solo quedarán configuración, roles y el usuario admin.


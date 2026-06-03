# Asignador (Julian) y Patrulleros

## Flujo

1. Edwin radica → caso **Radicado** + **Expediente**.
2. **Julian** (rol Asignador) recibe campana y correo.
3. Julian abre el caso y en **Asignado a** elige un **patrullero** (equipo Patrulleros).
4. El patrullero recibe **solo campana** (sin correo).

---

## Manual en el CRM (admin)

### 1. Usuario Julian

**Administración → Usuarios → Crear**

| Campo | Valor |
|--------|--------|
| Usuario | ej. `julian.asignador` |
| Email | correo de Julian |
| Roles | **Asignador** |
| Equipos | **no** incluir Patrulleros (solo el rol Asignador) |

### 2. Patrulleros (varios usuarios)

Por cada patrullero:

**Administración → Usuarios → Crear**

| Campo | Valor |
|--------|--------|
| Roles | sin Asignador (vacío o solo lectura si define otro rol) |
| Equipos | **Patrulleros** (obligatorio) |
| Email | opcional (no reciben correo al asignar, solo campana) |

### 3. Equipo Patrulleros — agregar patrulleros

En EspoCRM **no hay un campo “Usuarios” arriba del formulario**. Los patrulleros se agregan en la sección de abajo:

**Opción A — desde el equipo**

1. **Administración → Equipos → Patrulleros**
2. Si ves **Guardar / Cancelar**, primero pulsa **Guardar** (o **Cancelar** si no cambiaste nada).
3. **Baja con la rueda del ratón** hasta la sección **«Patrulleros del equipo»** (antes decía solo «Usuarios»).
4. Clic en el botón **+** (arriba a la derecha de esa tabla).
5. Elige el usuario patrullero y confirma.
6. Repite por cada patrullero. **No agregues a Julian.**

**Opción B — desde cada usuario (más fácil)**

1. **Administración → Usuarios →** abre el patrullero.
2. Campo **Equipos** → selecciona **Patrulleros** → **Guardar**.

Solo quienes estén en ese equipo aparecerán cuando Julian use **Asignado a** en un caso Radicado.

**Opción C — script (si ya creaste el usuario)**

```powershell
docker cp "./scripts/add-user-to-patrulleros-team.php" espocrm:/tmp/add-user-to-patrulleros-team.php
docker exec espocrm php /tmp/add-user-to-patrulleros-team.php carlos.patrullero
```

### 4. Correo saliente

**Administración → Correo saliente** → SMTP del CRM (una contraseña).

### 5. Rol Asignador en menú

**Administración → Roles → Asignador → Interfaz** → visible **Casos**.

---

## Cómo asigna Julian

1. Entra al CRM → **Casos**.
2. Abre un caso **Radicado** (con expediente).
3. Clic **Editar**.
4. Campo **Asignado a** (panel lateral o formulario) → elige el patrullero.
5. **Guardar**.

El patrullero verá el caso en **Casos** (si tiene permisos) y recibirá aviso.

---

## Desplegar código (Docker)

```powershell
docker cp "./espocrm-custom/Hooks/." espocrm:/var/www/html/custom/Espo/Custom/Hooks/
docker cp "./espocrm-custom/Classes/." espocrm:/var/www/html/custom/Espo/Custom/Classes/
docker cp "./espocrm-custom/Resources/." espocrm:/var/www/html/custom/Espo/Custom/Resources/
docker cp "./espocrm-custom/files/client/custom/." espocrm:/var/www/html/custom/Espo/Custom/files/client/custom/
docker exec espocrm php command.php rebuild
docker exec espocrm php command.php clear-cache
docker exec espocrm php /tmp/configure-asignador-patrulleros.php
docker exec espocrm php /tmp/assign-julian-asignador-role.php
docker exec espocrm php /tmp/backfill-asignador-notifications.php
```

**Julian** solo ve y edita casos en estado **Radicado**. Las notificaciones llegan al radicar (campana + correo si hay SMTP).

---

## Roles resumen

| Rol | Quién | Función |
|-----|--------|---------|
| Radicación | Edwin | Radicar |
| Inspección | Juan | Recibe aviso al radicar (inicio) |
| **Asignador** | **Julian** | Asigna patrulleros |
| (sin rol especial) | Patrulleros | Reciben casos asignados |

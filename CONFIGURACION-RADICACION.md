# Radicación de casos (Edwin → Juan / Inspección)

## Qué hace el sistema (automático, ya programado)

1. Edwin abre un caso en estado **Pendiente de radicacion**.
2. Solo él ve el campo **Número de radicación** (rol Radicación).
3. Escribe el número y guarda → el estado pasa a **Radicado**.
4. Usuarios con rol **Inspección** (ej. Juan) reciben **campana** y **correo**.

---

## Configurar manualmente en el CRM (admin)

### 1. Correo saliente (una sola vez)

**Administración → Correo saliente** → SMTP del buzón del CRM (contraseña de aplicación de ese buzón solamente).

### 2. Rol Inspección (para Juan)

**Administración → Roles → + Crear**

- Nombre: **Inspección** (exacto, con tilde).
- **Case**: lectura y edición = **Todo (all)** (o lo que necesiten).
- Menú del rol: visible **Casos**.

### 3. Usuario Juan

**Administración → Usuarios → + Crear**

- Usuario, nombre, **Email** de Juan.
- Rol: **Inspección**.
- Equipo: el que usen (opcional).

### 4. Layout del campo (solo Edwin / Radicación)

**Administración → Gestor de diseño → Entidades → Caso → Diseños**

- Editar diseño del rol **Radicación** (detalle y edición).
- Agregar campo **Número de radicación** (`cNumeroRadicacion`) donde quieras (recomendado arriba, cerca de Estado).

En diseños de otros roles **no** agregues ese campo (refuerzo visual; el script de campo también lo oculta).

### 5. Seguridad a nivel de campo (opcional si corriste el script)

**Administración → Roles → Radicación → Nivel de campo → Caso**

- `cNumeroRadicacion`: lectura y edición = **sí**.

En otros roles: lectura y edición = **no**.

(O ejecuta: `docker exec espocrm php /tmp/configure-radicacion-field-level.php`)

### 6. Desplegar código custom

```powershell
docker cp "./espocrm-custom/Hooks/." espocrm:/var/www/html/custom/Espo/Custom/Hooks/
docker cp "./espocrm-custom/Resources/." espocrm:/var/www/html/custom/Espo/Custom/Resources/
docker exec espocrm php /tmp/merge-radicacion-field-metadata.php
docker exec espocrm php command.php rebuild
docker exec espocrm php command.php clear-cache
docker exec espocrm php /tmp/configure-radicacion-field-level.php
```

---

## Flujo de Edwin

1. Entrar al CRM → **Casos**.
2. Abrir caso **Pendiente de radicacion**.
3. Llenar **Número de radicación**.
4. **Guardar** → estado **Radicado** + aviso a Inspección.

---

## Si Juan no recibe correo

- Revisar **Email** en su usuario.
- Revisar **Correo saliente** global.
- Campana del CRM debe mostrar el aviso igual (sin depender del correo).

# Equipo Recibidores y campos desplegables

Los campos **Recibida por** (`cRecibidaPor`) y **Remitido a** (`cRemitidoA`) son desplegables de **Usuario**. Solo muestran miembros del equipo **Recibidores**.

## Opción A — Script automático (recomendado)

Desde la carpeta del proyecto:

```bash
docker cp espocrm-custom/. espocrm:/var/www/html/custom/Espo/Custom/
docker cp scripts/configure-recibidores.php espocrm:/tmp/configure-recibidores.php
docker exec espocrm php /tmp/configure-recibidores.php
docker exec espocrm php command.php clear-cache
```

El script:

1. Crea el equipo **Recibidores** (si no existe).
2. Agrega **juan.inspeccion** al equipo.
3. Convierte los dos campos de texto a **Usuario** con filtro por equipo.

## Opción B — Manual en el CRM

### 1. Crear el equipo

1. **Administración → Equipos → Crear equipo**
2. Nombre: **Recibidores**
3. Guardar

### 2. Agregar a Juan

1. **Administración → Usuarios → juan.inspeccion**
2. Campo **Equipos** → marcar **Recibidores**
3. Guardar

(O desde el equipo Recibidores, sección **Usuarios** abajo, botón **Seleccionar**.)

### 3. Cambiar los campos a desplegable

EspoCRM no permite pasar de texto a Usuario en el mismo campo; hay que recrearlos:

1. **Administración → Entidad Manager → Caso → Campos**
2. Eliminar **Recibida por** y **Remitido a** (si son texto).
3. **Agregar campo** → tipo **Enlace** → entidad **Usuario**:
   - Nombre interno: `cRecibidaPor`, etiqueta: **Recibida por**
   - Repetir para `cRemitidoA`, etiqueta **Remitido a**
4. **Administración → Diseño de diseño → Caso → Edición** — colocar ambos campos en el panel «Queja y gestión».
5. **Rebuild** en Administración (o ejecutar el script de arriba solo por el filtro).

El filtro **Recibidores** (solo usuarios de ese equipo) viene del código en `espocrm-custom/`; hay que desplegarlo con `docker cp` y `clear-cache`.

## Agregar más recibidores después

```bash
docker cp scripts/add-user-to-recibidores-team.php espocrm:/tmp/
docker exec espocrm php /tmp/add-user-to-recibidores-team.php otro.usuario
```

O en **Usuarios → Equipos → Recibidores**.

## Resumen

| Elemento | Valor |
|----------|--------|
| Equipo | Recibidores |
| Usuario inicial | juan.inspeccion |
| Campos | cRecibidaPor, cRemitidoA (Usuario) |
| Filtro en desplegable | recibidores |

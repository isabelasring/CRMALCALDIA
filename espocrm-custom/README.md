# espocrm-custom — Código personalizado

Extensión de EspoCRM para el CRM Alcaldía. Se despliega a `custom/Espo/Custom/` en el contenedor.

## Modo actual

**Sin flujo por roles.** La lógica de Inspección / Radicación / Patrullero / Asignador fue eliminada. El CRM conserva entidades, campos, formatos y paneles; el acceso es vía permisos amplios (admin) hasta implementar el nuevo flujo.

## Backend (PHP)

```
Hooks/              # Eventos al guardar/crear entidades
  CaseObj/          # Casos: radicado, formatos, partes, Excel
  ActaVisita/       # Generación de formatos y Excel de acta
Tools/              # Lógica de negocio reutilizable
  CaseObj/          # Radicado, cronograma, alertas, formatos, Excel
  Party/            # Personas naturales/jurídicas y expediente
Controllers/        # Endpoints API extra (Case/action/...)
Classes/            # Filtros, record hooks
Entities/           # Entidades custom (ActaVisita, ComunicacionCaso, ...)
Jobs/               # Tareas programadas (alertas vencimiento)
Resources/
  metadata/         # entityDefs, clientDefs, app config
  layouts/          # Formularios y vistas por entidad
  i18n/             # Traducciones (es_ES, es_MX, en_US)
```

**Nota:** en PHP la entidad Caso se llama `CaseObj` (palabra reservada). En metadata JSON aparece como `Case`.

## Frontend (JS)

```
files/client/custom/
  src/
    views/          # Vistas extendidas (case, acta-visita, ...)
    helpers/        # Lógica compartida (formularios, paneles, formatos)
    loader/         # Parches de carga (i18n, tema)
  res/
    templates/      # Plantillas Handlebars
    css/            # Estilos custom
  dashboard.js      # Tablero gerencial
```

Los módulos AMD usan el prefijo `custom:` — **no renombrar archivos** sin actualizar todas las dependencias `define(...)`.

## Python (runtime)

```
files/scripts/      # Invocados por PHP al generar PDF/Excel
  fill-formato-solicitud.py
  fill-formato-acta-visita.py
  upsert-excel-alcaldia.py
  ...
```

Herramientas de desarrollo Python van en `scripts/` (raíz del repo), no aquí.

## Plantillas

| Ubicación | Rol |
|-----------|-----|
| `formatos/` (repo raíz) | **Fuente versionada** — Word/Excel oficiales |
| `files/templates/` | Copia en runtime + PDFs generados (no editar manualmente) |

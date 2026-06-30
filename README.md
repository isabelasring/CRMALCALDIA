# CRM Alcaldía (EspoCRM)

Gestión de quejas ambientales — EspoCRM + PostgreSQL.

## Estructura

```
CRMALCALDIA/
├── espocrm-custom/   # Backend y frontend custom
├── formatos/         # Plantillas Word/Excel (fuente oficial)
├── exports/          # Excel maestro (excelAlcaldia.xlsx)
├── scripts/          # Deploy y configuración
├── docker/           # Imagen EspoCRM
└── sql/              # Esquema de BD
```

## Local (Docker)

```bash
cp backups/despliegue-inicial/env.txt .env   # ajustar valores
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

http://localhost:8080

## Producción (Dokploy)

Variables requeridas: `ESPOCRM_ADMIN_USERNAME`, `ESPOCRM_ADMIN_PASSWORD`.

El deploy corre `scripts/deploy-custom-dokploy.sh` dentro del contenedor.

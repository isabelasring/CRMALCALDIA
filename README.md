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

## Producción (Dokploy) — solo push y Redeploy

Repositorio: `https://github.com/Geotrends/CRMALCALDIA` (rama `main`).

### Flujo habitual

1. Haces `git push` a `main`.
2. En Dokploy → **Redeploy** (debe **reconstruir la imagen**, no solo reiniciar).
3. El contenedor `espocrm` al arrancar ejecuta solo `deploy-custom-dokploy.sh` (copia custom + rebuild + scripts).
4. En logs debe aparecer: `==> Auto-deploy completado.`
5. En el navegador: **Ctrl+F5** y entrar con usuario **Inspección** (no admin).

### Variables en Dokploy → Environment

Obligatorias:

- `ESPOCRM_ADMIN_USERNAME`
- `ESPOCRM_ADMIN_PASSWORD`
- `POSTGRES_*`, `ESPOCRM_SITE_URL`, etc. (ver `backups/despliegue-inicial/env.txt`)

No hace falta definir `ESPO_RUN_AUTO_DEPLOY` (viene en la imagen como `1`).

Opcional: `DEPLOY_VERSION` si Dokploy no inyecta `SOURCE_COMMIT`.

### Configuración Dokploy (una vez)

- **Build type:** Dockerfile → `docker/espocrm/Dockerfile`
- **Compose:** `docker-compose.yml`
- **Rebuild on deploy:** activado

### Si algo no se actualiza

Revisa logs del servicio `espocrm` al arrancar. Si ves `Auto-deploy: sin cambios (omitido)` sin haber desplegado código nuevo, fuerza rebuild en Dokploy.

Verificación manual (solo diagnóstico):

```bash
docker exec espocrm bash /opt/bootstrap/repo/scripts/verify-custom-deploy.sh
```

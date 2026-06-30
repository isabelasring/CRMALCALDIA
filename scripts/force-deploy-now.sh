#!/bin/bash
# Fuerza deploy del custom en el contenedor (Dokploy / Docker).
# Uso en el servidor:
#   docker exec espocrm bash /opt/bootstrap/repo/scripts/force-deploy-now.sh
set -euo pipefail

REPO_ROOT="${REPO_ROOT:-/opt/bootstrap/repo}"
DEPLOY_SCRIPT="$REPO_ROOT/scripts/deploy-custom-dokploy.sh"

if [ ! -f "$DEPLOY_SCRIPT" ]; then
  echo "ERROR: no existe $DEPLOY_SCRIPT"
  echo "Monta el repo en /opt/bootstrap/repo o ajusta REPO_ROOT."
  exit 1
fi

export ESPO_FORCE_AUTO_DEPLOY=1
bash "$DEPLOY_SCRIPT"

echo ""
echo "Listo. En el navegador: Ctrl+F5 y vuelve a entrar con usuario Inspección (no admin)."

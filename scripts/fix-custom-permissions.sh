#!/bin/sh
# Permite que Administración (www-data) guarde campos y diseños en custom/
docker exec espocrm bash -c 'chown -R www-data:www-data /var/www/html/custom && chmod -R ug+rwX /var/www/html/custom'
echo "Permisos custom OK."

#!/bin/sh
set -e

# ✅ Fix permissions sur Storage/ au démarrage
mkdir -p /var/www/html/app/Storage/backups
chown -R www-data:www-data /var/www/html/app/Storage
chmod -R 775 /var/www/html/app/Storage
echo "[entrypoint] Permissions Storage OK"

# ✅ Fix permissions sur public/uploads/ au démarrage
mkdir -p /var/www/html/app/public/uploads/poiz
chown -R www-data:www-data /var/www/html/app/public/uploads
chmod -R 775 /var/www/html/app/public/uploads
echo "[entrypoint] Permissions uploads OK"

# ✅ SSH : copie la clé de root vers www-data pour le git push
# (le volume monte /root/.ssh mais git exec() tourne en www-data)
if [ -d /root/.ssh ]; then
    mkdir -p /var/www/.ssh
    cp -r /root/.ssh/. /var/www/.ssh/
    chown -R www-data:www-data /var/www/.ssh
    chmod 700 /var/www/.ssh
    chmod 600 /var/www/.ssh/* 2>/dev/null || true
    echo "[entrypoint] SSH config www-data OK"
fi
git config --system --add safe.directory /var/www/html
# ✅ Identité git pour les commits (nécessaire car www-data n'a pas de config ~/.gitconfig)
git config --system user.email "deploy@terra-aventura"
git config --system user.name "Terra Aventura Deploy"
echo "[entrypoint] Git config OK"

# ✅ Fix permission .git/ → www-data doit pouvoir écrire dedans
# (exec() PHP tourne en www-data, mais le volume bind-mount appartient à root)
if [ -d /var/www/html/.git ]; then
    chown -R www-data:www-data /var/www/html/.git
    echo "[entrypoint] Permissions .git OK"
fi

# Lance php-fpm
exec php-fpm
#!/bin/sh
set -e

# ✅ Fix permissions sur Storage/ au démarrage
mkdir -p /var/www/html/app/Storage/backups
chown -R www-data:www-data /var/www/html/app/Storage
chmod -R 775 /var/www/html/app/Storage
echo "[entrypoint] Permissions Storage OK"

# ✅ Fix git "dubious ownership" au niveau système (tous les users)
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
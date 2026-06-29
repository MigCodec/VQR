# Deploy por SSH

Este proyecto se despliega desde GitHub Actions entrando por SSH al servidor. El servidor hace `git pull` desde GitHub, por eso necesita una deploy key con acceso de lectura al repositorio privado.

## Secrets de GitHub Actions

Configurar en `Settings > Secrets and variables > Actions`:

- `SSH_HOST`: host o IP del servidor.
- `SSH_PORT`: puerto SSH, normalmente `22`.
- `SSH_USER`: usuario SSH del servidor.
- `SSH_PRIVATE_KEY`: llave privada que GitHub Actions usara para entrar al servidor.
- `SSH_PRIVATE_KEY_PASSPHRASE`: clave/passphrase de la llave privada, si la llave la tiene. Si la llave no tiene clave, dejar sin crear o vacio.
- `DEPLOY_PATH`: carpeta exacta del proyecto en el servidor, por ejemplo `/var/www/vqr`.
- `DEPLOY_BRANCH`: rama a desplegar, por ejemplo `main`. Si se omite, usa la rama que disparo el workflow.

La llave privada de `SSH_PRIVATE_KEY` debe corresponder a una llave cuya publica este en `~/.ssh/authorized_keys` del usuario `SSH_USER` en el servidor. Si esa llave fue creada desde cPanel y tiene clave, guardar esa clave en `SSH_PRIVATE_KEY_PASSPHRASE`.

Esta llave no es la misma que la deploy key de GitHub. Se usan dos llaves:

- GitHub Actions -> servidor: `SSH_PRIVATE_KEY` en secrets y publica en `authorized_keys`.
- Servidor -> GitHub: deploy key instalada en el servidor y publica registrada en GitHub.

## Preparar acceso del servidor al repo privado

En el servidor, conectado como el usuario de deploy:

```bash
ssh-keygen -t ed25519 -C "vqr-deploy-key" -f ~/.ssh/vqr_github -N ""
cat ~/.ssh/vqr_github.pub
```

Agregar esa llave publica en GitHub:

`Repository > Settings > Deploy keys > Add deploy key`

Marcar solo lectura. No activar write access.

Crear o editar `~/.ssh/config` en el servidor:

```sshconfig
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/vqr_github
  IdentitiesOnly yes
```

Probar desde el servidor:

```bash
ssh -T git@github.com
```

## Preparar carpeta de deploy

Primera vez en el servidor:

```bash
mkdir -p /var/www/vqr
cd /var/www/vqr
git clone git@github.com:OWNER/REPO.git .
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
```

Editar `.env` con valores reales de produccion. No subir `.env` al repositorio.

## Deploy

El workflow corre en cada push a `main` y tambien manualmente desde `Actions > Deploy > Run workflow`.

Durante el deploy ejecuta:

- `php artisan down`
- `git pull --ff-only`
- `composer install --no-dev`
- `php artisan migrate --force`
- cache de config, rutas y vistas
- `php artisan up`

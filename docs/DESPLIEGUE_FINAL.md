# Cómo terminar el despliegue (1 minuto)

El código **ya está listo**. Cursor no puede tocar el MySQL/Apache del host
(sandbox sin permisos de red/kill). Tú sí puedes, con el navegador o SSH.

## Opción A — Navegador (recomendada)

1. Asegura que el MySQL local de demo esté arriba (por si acaso):

```bash
bash /var/www/html/saf/bin/start_mysql_local.sh
```

2. Abre en el navegador (reemplaza el dominio/IP de tu servidor):

```
http://TU-SERVIDOR/saf/webroot/instalar-mejoras-v2.php?key=SAF2026
```

Ese script corre **dentro de Apache**, ve el MySQL real `:3306`, aplica el patch
y restaura `app_local.php` a producción.

3. Verifica:

```
http://TU-SERVIDOR/saf/webroot/estado-mejoras.php
http://TU-SERVIDOR/saf/login
```

4. **Borra** `webroot/instalar-mejoras-v2.php` cuando termine OK.

## Opción B — SSH como root real

```bash
sudo bash /var/www/html/saf/bin/reparar_mysql_host.sh
```

## Credenciales demo (BD local; en producción usa tus usuarios)

- `superadmin@saf.local` / `Admin123!`
- `consultor@saf.local` / `Admin123!`

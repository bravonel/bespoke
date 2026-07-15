# Deploy en cPanel

El dominio `bespokeadvertising.com.mx` no sirve directamente el checkout de Git.

## Rutas en producción

- Repositorio cPanel: `/home/bespokea/repositories/bespoke`
- App Laravel que sirve el dominio: `/home/bespokea/apps/bespoke-os`
- Front controller público: `/home/bespokea/public_html/index.php`
- Assets públicos: `/home/bespokea/public_html/build`

`public_html/index.php` carga Laravel desde `../apps/bespoke-os`, por eso los cambios de GitHub deben sincronizarse a esa carpeta antes de verse online.

## Flujo recomendado

1. Hacer merge a `master`.
2. En cPanel Git Version Control, actualizar el repo `bespoke`.
3. Ejecutar deploy sobre el repo, o correr:

```bash
cd /home/bespokea/repositories/bespoke
bash scripts/deploy-cpanel.sh
```

El script:

- respalda `.env`, `storage` y `database/database.sqlite` si existe;
- sincroniza el codigo del repo hacia `/home/bespokea/apps/bespoke-os`;
- preserva `.env`, `storage`, `vendor`, `node_modules` y la base SQLite;
- instala dependencias PHP;
- reconstruye assets de Vite cuando `npm` está disponible;
- copia `public/build` y `public/assets` hacia `public_html` para que el dominio sirva el CSS/JS correcto;
- reescribe `public_html/index.php` y `public_html/.htaccess` para que el dominio siempre cargue la app desde `/home/bespokea/apps/bespoke-os`;
- corre migraciones;
- limpia y recompila caches de Laravel.

Los respaldos quedan en `/home/bespokea/backups/bespoke-os`.

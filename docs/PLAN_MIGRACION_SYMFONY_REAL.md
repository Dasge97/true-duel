# Plan de Migracion a Symfony Real

> Estado: activo  
> Objetivo: convertir `backend/symfony` en una aplicacion Symfony real, usando su kernel, routing, contenedor, consola y migraciones, sin perder la arquitectura funcional ya construida.

## 1. Resultado objetivo

El backend debe quedar asi:

- `public/index.php` gobernado por el runtime de Symfony.
- `src/Kernel.php` como kernel real de la aplicacion.
- rutas declaradas por Symfony, no por router manual.
- servicios construidos por el contenedor, no por bootstrap manual.
- comandos ejecutados con `bin/console`.
- migraciones gestionadas por Doctrine Migrations.
- configuracion en `config/` y variables en `.env`.

La arquitectura funcional que queremos conservar:

- `Controller/`
- `Entity/`
- `Repository/`
- `Service/`
- `migrations/`

## 2. Lo que no queremos conservar

- `MvpApiKernel` como pieza central.
- `public/index.php` como bootstrap manual.
- `ApiBootstrap` como contenedor casero.
- `ApiRouter` como sistema oficial de rutas.
- scripts de migracion propios como mecanismo principal.

## 3. Estrategia de migracion

La migracion sera incremental, no un big bang ciego.

### Fase 0. Encender Symfony real

- instalar dependencias Symfony y Doctrine con Composer
- crear `Kernel`, `bundles.php`, `services.yaml`, `routes.yaml`, `bin/console`
- pasar `public/index.php` al runtime de Symfony
- dejar un controlador puente temporal para `/v1/*`

Objetivo:
- que el backend ya arranque como app Symfony aunque parte de la logica siga delegando en la capa legacy

### Fase 1. Sustituir la entrada HTTP legacy

- migrar `/health` a controlador Symfony
- reemplazar el bridge `/v1/*` por rutas Symfony reales
- mover auth, profile, gameplay, store, missions y admin a controladores HTTP Symfony

Objetivo:
- que la capa HTTP ya no dependa de `ApiApplication`, `ApiRouter` ni `ApiSecurity`

### Fase 2. Sustituir la composicion manual

- eliminar `ApiBootstrap`
- registrar PDO/DBAL y servicios en el contenedor
- autowire de controladores, servicios y repositorios
- mover configuracion de flags y claves a parametros/env

Objetivo:
- que el contenedor de Symfony construya la aplicacion

### Fase 3. Migraciones y consola oficiales

- reemplazar el runner propio por Doctrine Migrations
- portar las migraciones existentes al formato Doctrine
- mover `bootstrap_db.php`, `seed_product.php` y `run_competitive_job.php` a comandos Symfony

Objetivo:
- que esquema, seed y jobs se ejecuten con `bin/console`

### Fase 4. Endurecimiento final

- retirar el bridge legacy
- retirar `MvpApiKernel`, `ApiApplication`, `ApiRouter`, `ApiBootstrap` y soporte manual residual
- normalizar excepciones y errores con listener o subscriber Symfony
- preparar tests funcionales sobre `HttpKernel`

Objetivo:
- backend plenamente Symfony, sin doble stack

## 4. Regla de migracion

Cada fase debe:

- preservar el contrato API existente,
- dejar el proyecto ejecutable,
- y reducir superficie legacy, no aumentarla.

## 5. Decisiones importantes

### 5.1 Sobre las entidades

Las entidades actuales se conservaran como concepto y ubicacion, pero se revisaran para alinearlas con Doctrine ORM solo si aporta valor real.

No vamos a reescribir todo el dominio a ORM por reflejo.

### 5.2 Sobre la persistencia

El primer salto sera a Symfony + Doctrine Migrations + DBAL.

ORM se evaluara despues por modulo.  
No es obligatorio para considerar la migracion a Symfony completada.

### 5.3 Sobre el kernel legacy

`MvpApiKernel` dejara de ser una pieza viva del runtime.

Si queda temporalmente durante la transicion, sera solo como compatibilidad interna y con fecha de retirada.

## 6. Criterio de cierre

La migracion se considerara cerrada cuando:

1. `composer install` construya una app Symfony funcional.
2. `bin/console` sea la via oficial para comandos y mantenimiento.
3. las rutas `/v1/*` esten declaradas en Symfony.
4. las migraciones oficiales sean Doctrine Migrations.
5. la ejecucion ya no pase por infraestructura manual propia.

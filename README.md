# Finanzas API

Backend de finanzas personales con Laravel 13, Sanctum y tokens Bearer. Cada usuario administra sus ingresos, egresos y categorías; las categorías del sistema son compartidas y de solo lectura. El frontend React se administra en un repositorio separado.

## Requisitos

- PHP 8.4 o compatible, con BCMath, PDO MySQL, mbstring, XML y las extensiones que compruebe Composer.
- MySQL 8.
- Composer 2.
- Para ejecutar las pruebas aisladas: PDO SQLite.

Comprueba la plataforma con `php --version`, `php -m` y `composer check-platform-reqs`.

## Instalar desde cero

Reemplaza los marcadores antes de ejecutar:

```powershell
git clone <URL_REPOSITORIO_BACKEND> finanzas-api
cd finanzas-api
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Crea una base de datos MySQL vacía y configura el archivo `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=<URL_BACKEND>
DB_CONNECTION=mysql
DB_HOST=<HOST_MYSQL>
DB_PORT=<PUERTO_MYSQL>
DB_DATABASE=<BASE_DE_DATOS>
DB_USERNAME=<USUARIO_MYSQL>
DB_PASSWORD=<CONTRASENA_MYSQL>
```

`DB_HOST` y `DB_PORT` indican el servidor; `DB_DATABASE` identifica la base; usuario y contraseña autentican la conexión. No publiques `.env`, contraseñas, tokens ni `APP_KEY`. En producción usa `APP_DEBUG=false`, HTTPS y credenciales propias del entorno.

```powershell
php artisan migrate
php artisan db:seed --class=CategoriaSeeder
php artisan serve
```

Abre la dirección del servidor seguida de `/up` para comprobar que Laravel responde. `/api` por sí sola no tiene página; utiliza los endpoints documentados abajo. El servidor de React se inicia en el otro repositorio.

Si frontend y backend usan distintos orígenes, permite el origen exacto del frontend mediante CORS, incluyendo los encabezados `Authorization` y `Content-Type`. La autenticación de este proyecto utiliza Bearer, no cookies SPA.

## Endpoints

Todos los endpoints siguientes llevan el prefijo `/api`:

| Método | Ruta | Autenticación |
| --- | --- | --- |
| POST | `/register`, `/login` | Pública; limitada a 10 solicitudes por minuto por IP |
| POST | `/logout` | Bearer; revoca solamente el token actual |
| GET | `/user` | Bearer |
| GET/POST | `/ingresos`, `/egresos`, `/categorias` | Bearer |
| GET/PUT/PATCH/DELETE | `/ingresos/{id}`, `/egresos/{id}`, `/categorias/{id}` | Bearer |
| GET | `/dashboard/resumen?anio=<ANIO>&mes=<MES>` | Bearer |
| GET | `/dashboard/egresos-por-categoria?anio=<ANIO>&mes=<MES>` | Bearer |
| GET | `/dashboard/resumen-anual?anio=<ANIO>` | Bearer |

Envía `Accept: application/json`, `Content-Type: application/json` y `Authorization: Bearer <TOKEN>` en las peticiones protegidas. Login recibe `email` y `password`; registro también recibe `name`. Ambos devuelven `token` y los datos básicos del usuario.

Los listados de movimientos aceptan filtros opcionales `anio` (2000–2100) y `mes` (1–12). Categorías acepta `tipo=ingreso` o `tipo=egreso` e incluye subcategorías.

## Reglas y respuestas

- Los importes se guardan en `decimal(12,2)`; se validan como mayores que cero, con hasta dos decimales y máximo 9,999,999,999.99.
- Envía importes como cadenas decimales, por ejemplo `"10.25"`. Los totales del dashboard también se devuelven como cadenas decimales de dos posiciones. PHP calcula diferencias y porcentajes con BCMath, sin float.
- El acumulado corresponde de enero al mes consultado inclusive. El resumen anual siempre contiene doce meses, con ceros donde no hay movimientos.
- Las validaciones de entrada están en Form Requests. Un error devuelve HTTP 422 con `message` y `errors` por campo.
- Un recurso ajeno devuelve 404; una sesión ausente o inválida devuelve 401. No se serializan la contraseña ni su hash.
- Al cambiar de categoría de un egreso sin enviar subcategoría, se limpia la anterior. Si envías ambas, deben corresponderse.
- Crear o editar una categoría duplicada del mismo usuario y tipo devuelve 422. Las categorías del sistema no pueden editarse ni eliminarse.

## Pruebas y verificación

```powershell
php artisan test
php vendor/bin/pint --dirty --test
composer validate --no-check-publish
```

`phpunit.xml` utiliza SQLite **en memoria**; no borra la base MySQL de trabajo. Las pruebas cubren aislamiento entre dos usuarios, validación de filtros y montos, subcategorías, duplicados, doce meses, acumulados, orden de categorías, ocultación de contraseñas, límite de intentos y logout del token actual. Se proporciona en las pruebas el equivalente SQLite de `MONTH` de MySQL.

Para probar en MySQL, usa una base dedicada de pruebas y comprueba también los recorridos del frontend. Las pruebas SQLite no sustituyen todas las diferencias de comportamiento de MySQL.

El seeder opcional `DatosPruebaFinanzasSeeder` genera datos de demostración con junio vacío. **No lo ejecutes sobre usuarios con información que quieras conservar:** reemplaza los movimientos de sus usuarios de demostración. El seeder `CategoriaSeeder` se puede repetir sin duplicar su catálogo.

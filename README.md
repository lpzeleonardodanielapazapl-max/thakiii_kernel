# Thaki Voyage

La plataforma reúne dos actividades de la empresa: diseñar viajes a medida según las fechas, los intereses y el presupuesto del cliente, y gestionar el equipo de trekking que se utiliza en las excursiones.

## Equipo del proyecto

| Nombre completo | Rol en el proyecto | Usuario de GitHub |
|---|---|---|
| Leonardo Daniel Apaza Plata | Desarrollo del módulo de inventario | [@lpzeleonardodanielapazapl-max](https://github.com/lpzeleonardodanielapazapl-max) |
| Jose Luis Omar Centellas Portugal | Equipo de desarrollo · Fronted | [@centellasjoseluis2-lab](https://github.com/centellasjoseluis2-lab) |
| Boris Cristian Cuaquira Vargas | Equipo de desarrollo · Backend | [@lpzeboriscristiancuaquirava-dot](https://github.com/lpzeboriscristiancuaquirava-dot) |
| Eva Jhenny Tola Huanca | Equipo de desarrollo · base de datos, backend | [@lpzeevajhennytolahu-sys](https://github.com/lpzeevajhennytolahu-sys) |

## Arquitectura

El navegador solo habla con Laravel. Laravel no abre MySQL: reenvía cada operación a la API de Quarkus. Quarkus valida, aplica las reglas y consulta la base.

| Componente | Tecnología | Responsabilidad |
|---|---|---|
| Interfaz web | Laravel, PHP 8.3+ y Blade | Vistas, sesión del navegador y llamadas a la API. |
| API REST | Quarkus 3 y Java 25 | Acceso, viajes, inventario y catálogo. |
| Base de datos | MySQL 8.0, esquema `thaki_modificado` | Personas, viajes, hoteles, traslados, inventario y sesiones. |

La contraseña de MySQL no va en `application.properties`. Quarkus la lee de `thaki-voyage-backend/.env` (`THAKI_DB_PASSWORD`). Hay un ejemplo en `.env.example`. Laravel usa `QUARKUS_URL` (por defecto `http://localhost:8080`).

## Funcionalidades

- **Sitio público:** inspiración, destinos, alojamientos y traslados. El visitante puede elegir una habitación o un servicio antes de crear su cuenta.
- **Portal del cliente:** perfil, solicitud de viaje, chat con el asesor, propuestas, aceptación y solicitud de cancelación. La elección de hotel o traslado queda en la solicitud con el precio vigente del catálogo.
- **Personal:** atención (propuestas, calendario, pagos), inventario con unidades físicas, clasificación ABC, reposición y el directorio de personas, empleados, clientes, usuarios y roles.
- **Sesión:** token opaco guardado solo como hash, con caducidad de 8 horas. Las contraseñas usan PBKDF2.

## Pendiente

Inicio de sesión con Google, correo de confirmación y permisos finos por módulo. El anticipo del 50 % se coordina con el asesor; esta web no cobra en línea.

## Arranque local

1. Crear la base con `material_auxiliar/thaki_empresa.sql`, siguiendo [la guía del inventario de la empresa](material_auxiliar/BASE_INVENTARIO_EMPRESA.md). Incluye las 80 tablas, la procedencia y los datos de `Inventarios 2026 (1).xlsx`. **La importación elimina y recrea `thaki_modificado`. Respalda la base existente antes de ejecutarla.** Crea después el administrador desde `/configurar-administracion` con Laravel en local y Quarkus en desarrollo. Para una instalación con tablas operativas vacías está disponible `material_auxiliar/thaki_limpio.sql` con [su guía de importación](material_auxiliar/IMPORTAR_BASE_LIMPIA.md).
2. Copiar `thaki-voyage-backend/.env.example` a `.env` y completar `THAKI_DB_PASSWORD`. Luego `./mvnw quarkus:dev`.
3. En `web-laravel`, copiar `.env.example` a `.env`, generar la clave y dejar `QUARKUS_URL=http://localhost:8080`. Luego `php artisan serve`.

| Recurso | Dirección |
|---|---|
| Sitio público | http://localhost:8000 |
| Alojamientos | http://localhost:8000/alojamientos |
| Traslados | http://localhost:8000/traslados |
| Portal del cliente | http://localhost:8000/mi-viaje |
| Panel del personal | http://localhost:8000/admin |
| Inventario | http://localhost:8000/inventario |
| Salud de la API | http://localhost:8080/api/v1/health/ping |

El código web está en `web-laravel/` y la API en `thaki-voyage-backend/`.

## Arranque con Docker

Requiere Docker Desktop o Docker Engine con Docker Compose. Desde la raíz del repositorio, crea el archivo de configuración y define dos contraseñas distintas:

```powershell
Copy-Item .env.docker.example .env
notepad .env
```

Después, ejecuta:

```powershell
docker compose up --build -d
```

Abre la dirección definida por `APP_URL` (por defecto, http://localhost:8085). Laravel consulta la API Quarkus en `http://api:8080` dentro de la red Docker. La clave de Laravel se genera una vez y se conserva en el volumen de almacenamiento.

MySQL carga `thaki.sql` solo al inicializar por primera vez el volumen `mysql_data`. Los datos se conservan con `docker compose down`; `docker compose down -v` elimina permanentemente los volúmenes y la base. Para ver el estado usa `docker compose ps`; para ver registros usa `docker compose logs -f`.

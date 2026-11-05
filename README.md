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

Cuando el administrador abre el inventario, Laravel solicita los productos a la API de Quarkus mediante HTTP. Quarkus consulta MySQL y devuelve los resultados en JSON. Laravel usa esos datos para mostrar la tabla en el navegador.

| Componente | Tecnología | Responsabilidad |
|---|---|---|
| Interfaz web y BFF | Laravel, PHP 8.5 y Blade | Gestiona las vistas y las solicitudes del navegador. Consume la API sin conectarse directamente a MySQL. |
| API REST | Quarkus y Java 21 | Valida los datos de entrada, ejecuta las reglas de negocio y controla las transacciones. |
| Base de datos | MySQL 8.0 | El esquema `thaki_modificado` almacena productos, unidades físicas, precios, personas y demás registros del proyecto. |

## Funcionalidades implementadas

- **Panel administrativo:** muestra productos activos, unidades registradas, equipos pendientes de arreglo y unidades dadas de baja.
- **Inventario:** permite registrar productos y consultar sus cantidades. Un mismo producto, como el botellón de oxígeno portátil, agrupa varias unidades físicas, cada una identificada con su propio código, por ejemplo, `BOP1` y `BOP2`.



## Funcionalidades previstas

El cliente podrá iniciar sesión con Google y conversar con su asesor asignado para definir un itinerario con hospedaje, transporte y alimentación. Cuando el cliente acepte la propuesta, el asesor registrará la aceptación. El anticipo obligatorio será del 50 %; una vez verificado el pago, se enviará el correo de confirmación. El saldo se pagará al llegar a Bolivia.

En inventario, el siguiente paso es registrar unidades físicas desde la web y controlar sus salidas y recepciones por código individual. Al recibir cada equipo se registrarán la fecha y su condición.

## Accesos en desarrollo local

Con Laravel y Quarkus en ejecución y MySQL configurado:

| Recurso | Dirección |
|---|---|
| Panel administrativo | http://localhost:8000/admin |
| Inventario de equipos | http://localhost:8000/inventario |
| Clasificación ABC | http://localhost:8000/inventario/abc |
| API de productos | http://localhost:8080/api/inventario/productos |

El código web se encuentra en `web-laravel/` y el de la API en `thaki-voyage-backend/`.

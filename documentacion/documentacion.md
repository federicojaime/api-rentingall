# Documentación API de Alquiler de Vehículos

## Índice
1. [Introducción](#introducción)
2. [Configuración](#configuración)
3. [Autenticación](#autenticación)
4. [Estructura de Respuestas](#estructura-de-respuestas)
5. [Endpoints](#endpoints)
   - [Usuarios](#usuarios)
   - [Clientes](#clientes)
   - [Vehículos](#vehículos)
   - [Entregas](#entregas)
   - [Facturas](#facturas)
   - [Estadísticas](#estadísticas)
6. [Modelos de Datos](#modelos-de-datos)
7. [Códigos de Error](#códigos-de-error)

## Introducción

Esta API proporciona un sistema completo para la gestión de alquiler de vehículos. Permite administrar usuarios, clientes, vehículos, entregas y facturas, junto con estadísticas para análisis de datos. Está desarrollada utilizando Slim Framework 4, siguiendo principios RESTful.

### Tecnologías utilizadas

- PHP 8.0 o superior
- Slim Framework 4
- JWT para autenticación
- PHP-DI como contenedor de dependencias
- PDO para interacción con base de datos MySQL
- PHPMailer para envío de correos electrónicos

## Configuración

### Variables de entorno

La API utiliza un archivo `.env` para configurar variables sensibles. A continuación se muestran las variables necesarias:

```
# Base de datos
DB_HOST=localhost
DB_NAME=bd-renting-all
DB_USER=root
DB_PASS=

# JWT
JWT_SECRET_KEY=clave-secreta-muy-segura
JWT_ALGORITHM=HS256

# SMTP para envío de emails
SMTP_HOST=smtp.example.com
SMTP_USERNAME=user@example.com
SMTP_PASSWORD=password
SMTP_PORT=465
SMTP_SENDER_NAME="Sistema de Alquiler"

# URL de la aplicación web
APP_URL=http://localhost:3000/
```

### Instalación

1. Clone el repositorio
2. Ejecute `composer install`
3. Configure las variables de entorno en un archivo `.env`
4. Configure la base de datos MySQL
5. El punto de entrada es `index.php`

## Autenticación

La API utiliza JSON Web Tokens (JWT) para la autenticación. La mayoría de los endpoints requieren un token válido.

### Obtener un token

Para obtener un token, debe autenticarse a través del endpoint `/user/login`:

```
POST /user/login
{
    "email": "usuario@example.com",
    "password": "contraseña"
}
```

La respuesta incluirá un token JWT que debe incluirse en las solicitudes posteriores como encabezado:

```
Authorization: Bearer {token}
```

### Validación de token

Puede verificar la validez de un token con el endpoint:

```
GET /user/token/validate/{token}
```

## Estructura de Respuestas

Todas las respuestas de la API siguen un formato estándar:

```json
{
    "ok": true|false,
    "msg": "Mensaje descriptivo",
    "data": {...}
}
```

En caso de error, la estructura puede incluir detalles adicionales:

```json
{
    "ok": false,
    "msg": "Hay errores en los datos suministrados",
    "data": null,
    "errores": ["Campo X es requerido", "Campo Y no es válido"]
}
```

## Endpoints

### Usuarios

Gestión de usuarios del sistema y autenticación.

#### Obtener todos los usuarios

```
GET /users
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": [
        {
            "id": 1,
            "email": "admin@example.com",
            "firstname": "Admin",
            "lastname": "Sistema"
        },
        ...
    ]
}
```

#### Obtener usuario por ID

```
GET /user/{id}
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": {
        "id": 1,
        "email": "admin@example.com",
        "firstname": "Admin",
        "lastname": "Sistema"
    }
}
```

#### Crear usuario

```
POST /user
```

Requiere: Token JWT válido

Cuerpo:
```json
{
    "email": "nuevo@example.com",
    "firstname": "Nombre",
    "lastname": "Apellido",
    "password": "contraseña"
}
```

#### Eliminar usuario

```
DELETE /user/{id}
```

Requiere: Token JWT válido

#### Iniciar sesión

```
POST /user/login
```

Cuerpo:
```json
{
    "email": "usuario@example.com",
    "password": "contraseña"
}
```

Respuesta:
```json
{
    "ok": true,
    "msg": "Usuario autorizado.",
    "data": {
        "id": 1,
        "firstname": "Admin",
        "lastname": "Sistema",
        "email": "admin@example.com",
        "jwt": "Bearer eyJ0eXAiOiJKV1Qi..."
    }
}
```

#### Registro de usuario

```
POST /user/register
```

Cuerpo:
```json
{
    "email": "nuevo@example.com",
    "firstname": "Nombre",
    "lastname": "Apellido",
    "password": "contraseña"
}
```

Respuesta: Se enviará un correo electrónico con un token para confirmar el registro.

#### Confirmar registro

```
GET /user/register/temp/{token}
```

#### Recuperar contraseña

```
POST /user/password/recover
```

Cuerpo:
```json
{
    "email": "usuario@example.com"
}
```

Respuesta: Se enviará un correo electrónico con un token para recuperar la contraseña.

#### Confirmar recuperación de contraseña

```
GET /user/password/temp/{token}
```

#### Cambiar contraseña

```
PATCH /user/password
```

Requiere: Token JWT válido

Cuerpo:
```json
{
    "id": 1,
    "password": "nueva_contraseña"
}
```

### Clientes

Gestión de clientes, tanto personas como empresas.

#### Obtener todos los clientes

```
GET /clientes
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": [
        {
            "id": 1,
            "tipo_cliente": "persona",
            "nombre": "Juan Pérez",
            "razon_social": null,
            "dni_cuit": "30123456",
            "telefono": "1122334455",
            "email": "juan@example.com",
            "created_at": "2023-05-10 14:30:00"
        },
        ...
    ]
}
```

#### Obtener cliente por ID

```
GET /cliente/{id}
```

Requiere: Token JWT válido

#### Buscar cliente por DNI/CUIT

```
GET /cliente/buscar/{dni_cuit}
```

Requiere: Token JWT válido

#### Crear cliente

```
POST /cliente
```

Requiere: Token JWT válido

Cuerpo (persona):
```json
{
    "tipoCliente": "persona",
    "nombre": "Juan Pérez",
    "dniCuit": "30123456",
    "telefono": "1122334455",
    "email": "juan@example.com"
}
```

Cuerpo (empresa):
```json
{
    "tipoCliente": "empresa",
    "razonSocial": "Empresa S.A.",
    "dniCuit": "30123456789",
    "telefono": "1122334455",
    "email": "contacto@empresa.com"
}
```

#### Actualizar cliente

```
PATCH /cliente/{id}
```

Requiere: Token JWT válido

Cuerpo: Similar al de creación

#### Eliminar cliente

```
DELETE /cliente/{id}
```

Requiere: Token JWT válido

### Vehículos

Gestión de la flota de vehículos disponibles para alquiler.

#### Obtener todos los vehículos

```
GET /vehiculos
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": [
        {
            "id": 1,
            "nro_interno": "V001",
            "designacion": "Sedán 4 puertas",
            "marca": "Toyota",
            "modelo": "Corolla",
            "fecha_adquisicion": "2023-01-15",
            "nro_motor": "ABC123456",
            "nro_chasis": "XYZ987654",
            "patente": "AB123CD",
            "titulo": "Título propiedad",
            "estado": "DISPONIBLE",
            "responsable": "Departamento de Flota",
            "ministerio": "Ministerio de Transporte",
            "precio": 25000,
            "created_at": "2023-05-10 14:30:00"
        },
        ...
    ]
}
```

#### Obtener vehículo por ID

```
GET /vehiculo/{id}
```

Requiere: Token JWT válido

#### Crear vehículo

```
POST /vehiculo
```

Requiere: Token JWT válido

Cuerpo:
```json
{
    "nroInterno": "V001",
    "designacion": "Sedán 4 puertas",
    "marca": "Toyota",
    "modelo": "Corolla",
    "adquisicion": "2023-01-15",
    "motor": "ABC123456",
    "chasis": "XYZ987654",
    "patente": "AB123CD",
    "titulo": "Título propiedad",
    "estado": "DISPONIBLE",
    "responsable": "Departamento de Flota",
    "ministerio": "Ministerio de Transporte",
    "precio": 25000,
    "compania": "Seguros XYZ",
    "nroPoliza": "POL-12345",
    "vencimiento": "2024-01-15"
}
```

#### Actualizar vehículo

```
PATCH /vehiculo/{id}
```

Requiere: Token JWT válido

Cuerpo: Solo incluir los campos que se desean actualizar

#### Eliminar vehículo

```
DELETE /vehiculo/{id}
```

Requiere: Token JWT válido

### Entregas

Gestión de entregas y devoluciones de vehículos a clientes.

#### Obtener todas las entregas

```
GET /entregas
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": [
        {
            "id": 1,
            "vehiculo_id": 1,
            "cliente_id": 1,
            "funcionario_entrega": "María López",
            "funcionario_recibe": "Juan Pérez",
            "dni_entrega": "20123456",
            "dni_recibe": "30123456",
            "fecha_entrega": "2023-06-01",
            "fecha_devolucion": "2023-06-15",
            "lugar_entrega": "Oficina Central",
            "lugar_devolucion": "Oficina Central",
            "kilometraje_entrega": 5000,
            "kilometraje_devolucion": 5500,
            "nivel_combustible": "MEDIO",
            "observaciones": "Entrega en perfectas condiciones",
            "created_at": "2023-06-01 10:00:00",
            "patente": "AB123CD",
            "marca": "Toyota",
            "modelo": "Corolla",
            "designacion": "Sedán 4 puertas",
            "cliente_nombre": "Juan Pérez",
            "cliente_documento": "30123456",
            "luces_principales": 1,
            "luz_media": 1,
            ...
        },
        ...
    ]
}
```

#### Obtener entrega por ID

```
GET /entrega/{id}
```

Requiere: Token JWT válido

#### Obtener entregas por vehículo

```
GET /entregas/vehiculo/{id}
```

Requiere: Token JWT válido

#### Obtener entregas por cliente

```
GET /entregas/cliente/{id}
```

Requiere: Token JWT válido

#### Crear entrega

```
POST /entrega
```

Requiere: Token JWT válido

Cuerpo:
```json
{
    "vehiculo_id": 1,
    "cliente_id": 1,
    "funcionarioEntrega": "María López",
    "funcionarioRecibe": "Juan Pérez",
    "dniEntrega": "20123456",
    "dniRecibe": "30123456",
    "fechaEntrega": "2023-06-01",
    "lugarEntrega": "Oficina Central",
    "kilometrajeEntrega": 5000,
    "nivelCombustible": "MEDIO",
    "observaciones": "Entrega en perfectas condiciones",
    "inventario": {
        "lucesPrincipales": true,
        "luzMedia": true,
        "luzStop": true,
        "antenaRadio": true,
        "limpiaParabrisas": true,
        "espejoIzquierdo": true,
        "espejoDerecho": true,
        "vidriosLaterales": true,
        "parabrisas": true,
        "tapones": true,
        "taponGasolina": true,
        "carroceria": true,
        "parachoqueDelantero": true,
        "parachoqueTrasero": true,
        "placas": true,
        "calefaccion": true,
        "radioCd": true,
        "bocinas": true,
        "encendedor": true,
        "espejoRetrovisor": true,
        "ceniceros": true,
        "cinturones": true,
        "manijasVidrios": true,
        "pisosGoma": true,
        "tapetes": true,
        "fundaAsientos": true,
        "jaladorPuertas": true,
        "sujetadorManos": true,
        "gato": true,
        "llaveRueda": true,
        "estucheLlaves": true,
        "triangulo": true,
        "llantaAuxilio": true,
        "extintor": true,
        "botiquin": true,
        "otros": false,
        "soat": true,
        "inspeccionTecnica": true
    }
}
```

#### Finalizar entrega (registrar devolución)

```
PATCH /entrega/{id}/finalizar
```

Requiere: Token JWT válido

Cuerpo:
```json
{
    "fechaDevolucion": "2023-06-15",
    "lugarDevolucion": "Oficina Central",
    "kilometrajeDevolucion": 5500,
    "observaciones": "Devolución en buen estado"
}
```

#### Eliminar entrega

```
DELETE /entrega/{id}
```

Requiere: Token JWT válido

### Facturas

Gestión de facturas relacionadas con los alquileres de vehículos.

#### Obtener todas las facturas

```
GET /facturas
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": [
        {
            "id": 1,
            "vehiculo_id": 1,
            "fecha": "2023-06-15",
            "numero": "F-001",
            "monto": 1500,
            "pagado": 0,
            "notas": "Factura por alquiler del vehículo",
            "created_at": "2023-06-15 15:30:00",
            "patente": "AB123CD",
            "marca": "Toyota",
            "modelo": "Corolla"
        },
        ...
    ]
}
```

#### Obtener factura por ID

```
GET /factura/{id}
```

Requiere: Token JWT válido

#### Obtener facturas por vehículo

```
GET /facturas/vehiculo/{id}
```

Requiere: Token JWT válido

#### Obtener facturas por mes

```
GET /facturas/{year}/{month}
```

Requiere: Token JWT válido

#### Estadísticas de facturación

```
GET /facturas/estadisticas
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": {
        "total_facturas": 25,
        "monto_total": 38500,
        "monto_pagado": 32000,
        "monto_pendiente": 6500,
        "facturas_pagadas": 20,
        "facturas_pendientes": 5
    }
}
```

#### Estadísticas por mes

```
GET /facturas/estadisticas/{year}/{month}
```

Requiere: Token JWT válido

#### Crear factura

```
POST /factura
```

Requiere: Token JWT válido

Cuerpo:
```json
{
    "vehiculo_id": 1,
    "fecha": "2023-06-15",
    "numero": "F-001",
    "monto": 1500,
    "pagado": false,
    "notas": "Factura por alquiler del vehículo"
}
```

#### Actualizar factura

```
PATCH /factura/{id}
```

Requiere: Token JWT válido

Cuerpo: Similar al de creación

#### Actualizar estado de pago

```
PATCH /factura/{id}/pago
```

Requiere: Token JWT válido

Cuerpo:
```json
{
    "pagado": 1
}
```

#### Eliminar factura

```
DELETE /factura/{id}
```

Requiere: Token JWT válido

### Estadísticas

Endpoints para obtener datos estadísticos del sistema.

#### Estadísticas generales

```
GET /stats
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": {
        "total_vehiculos": 15,
        "entregas_activas": 3,
        "total_clientes": 25,
        "ingresos_totales": 45000
    }
}
```

#### Datos para gráficos

```
GET /stats/chart
```

Requiere: Token JWT válido

Respuesta:
```json
{
    "ok": true,
    "msg": "",
    "data": [
        {
            "mes": "2023-06",
            "total_entregas": 8,
            "ingresos": 12000
        },
        {
            "mes": "2023-05",
            "total_entregas": 6,
            "ingresos": 9000
        },
        ...
    ]
}
```

## Modelos de Datos

### Usuarios

```
- id: int (PK)
- email: string (unique)
- firstname: string
- lastname: string
- password: string (hash)
```

### Clientes

```
- id: int (PK)
- tipo_cliente: enum ('persona', 'empresa')
- nombre: string (para personas)
- razon_social: string (para empresas)
- dni_cuit: string (unique)
- telefono: string
- email: string
- created_at: datetime
```

### Vehículos

```
- id: int (PK)
- nro_interno: string
- designacion: string
- marca: string
- modelo: string
- fecha_adquisicion: date
- nro_motor: string
- nro_chasis: string
- patente: string
- titulo: string
- estado: enum ('DISPONIBLE', 'ALQUILADA', 'MANTENIMIENTO', 'BAJA')
- responsable: string
- ministerio: string
- precio: decimal
- created_at: datetime
```

### Seguros

```
- id: int (PK)
- vehiculo_id: int (FK)
- compania: string
- nro_poliza: string
- fecha_vencimiento: date
- created_at: datetime
```

### Entregas

```
- id: int (PK)
- vehiculo_id: int (FK)
- cliente_id: int (FK)
- funcionario_entrega: string
- funcionario_recibe: string
- dni_entrega: string
- dni_recibe: string
- fecha_entrega: date
- fecha_devolucion: date
- lugar_entrega: string
- lugar_devolucion: string
- kilometraje_entrega: int
- kilometraje_devolucion: int
- nivel_combustible: string
- observaciones: text
- created_at: datetime
```

### Inventario_Entrega

```
- id: int (PK)
- entrega_id: int (FK)
- luces_principales: boolean
- luz_media: boolean
- luz_stop: boolean
- antena_radio: boolean
- limpia_parabrisas: boolean
- espejo_izquierdo: boolean
- espejo_derecho: boolean
- vidrios_laterales: boolean
- parabrisas: boolean
- tapones: boolean
- tapon_gasolina: boolean
- carroceria: boolean
- parachoque_delantero: boolean
- parachoque_trasero: boolean
- placas: boolean
- calefaccion: boolean
- radio_cd: boolean
- bocinas: boolean
- encendedor: boolean
- espejo_retrovisor: boolean
- ceniceros: boolean
- cinturones: boolean
- manijas_vidrios: boolean
- pisos_goma: boolean
- tapetes: boolean
- funda_asientos: boolean
- jalador_puertas: boolean
- sujetador_manos: boolean
- gato: boolean
- llave_rueda: boolean
- estuche_llaves: boolean
- triangulo: boolean
- llanta_auxilio: boolean
- extintor: boolean
- botiquin: boolean
- otros: boolean
- soat: boolean
- inspeccion_tecnica: boolean
```

### Facturas

```
- id: int (PK)
- vehiculo_id: int (FK)
- fecha: date
- numero: string (unique)
- monto: decimal
- pagado: boolean
- notas: text
- created_at: datetime
```

## Códigos de Error

| Código HTTP | Descripción                       |
|-------------|-----------------------------------|
| 200         | OK - Solicitud exitosa            |
| 400         | Bad Request - Datos inválidos     |
| 401         | Unauthorized - No autenticado     |
| 403         | Forbidden - No autorizado         |
| 404         | Not Found - Recurso no encontrado |
| 409         | Conflict - Error en la operación  |
| 500         | Server Error - Error del servidor |

### Ejemplos de errores

```json
{
    "ok": false,
    "msg": "No se recibieron datos",
    "data": null
}
```

```json
{
    "ok": false,
    "msg": "Hay errores en los datos suministrados",
    "data": null,
    "errores": [
        "email [correo] no es una dirección eletrónica válida.",
        "password debe tener al menos 3 caracteres."
    ]
}
```

```json
{
    "ok": false,
    "msg": "No se puede eliminar el cliente porque tiene entregas asociadas",
    "data": null
}
```

```json
{
    "ok": false,
    "msg": "El token [abc123] no es válido.",
    "data": false
}
```
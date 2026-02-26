# 🚀 API IAC — Plataforma de Integración Node.js + Drupal + GLPI. 

Plataforma de integración que conecta **Drupal 10** como frontend web con una **API REST en Node.js** como backend, usando **PostgreSQL** como base de datos compartida e integrando datos en tiempo real desde **GLPI** (sistema de gestión IT).

---

## 📐 Arquitectura

```
                     ┌─────────────────────────────────────────────┐
                     │              Docker Network: mi-red-segura              │
                     │                                                         │
  Usuario  ─────────►│  Drupal 10 :8080  ──── HTTP interno ────►  API Node.js :3000  │
  (Navegador)        │      (Apache/PHP)              (Express + JWT)          │
                     │           │                          │                  │
                     │           └──────────┬───────────────┘                  │
                     │                      ▼                                  │
                     │              PostgreSQL :5432                           │
                     │              pgAdmin    :5050                           │
                     │                      │                                  │
                     │                      ▼                                  │
                     │           GLPI API (externo)                            │
                     │      https://rejo.ll.iac.es/glpi/apirest.php           │
                     └─────────────────────────────────────────────────────────┘
```

| Servicio | Puerto | Descripción |
|---|---|---|
| **API Node.js** | `3000` | Backend REST con autenticación JWT |
| **Drupal 10** | `8080` | Frontend CMS con módulo conector personalizado |
| **PostgreSQL 15** | `5432` | Base de datos compartida (prefijo de tablas: `api_`) |
| **pgAdmin 4** | `5050` | Interfaz visual para administrar la BD |

---

## ⚡ Inicio rápido

### Prerrequisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Docker Compose](https://docs.docker.com/compose/)

### Arrancar el proyecto

```bash
docker-compose up
```

Todos los servicios arrancarán en orden. La primera vez puede tardar unos minutos mientras se descargan las imágenes.

| URL | Servicio |
|---|---|
| `http://localhost:8080/api/entrar` | Formulario de login (Drupal) |
| `http://localhost:8080/api/informacion` | Página informativa sobre APIs |
| `http://localhost:8080/api/glpi/trabajadores` | Listado de trabajadores IAC |
| `http://localhost:3000` | API Node.js directa |
| `http://localhost:5050` | pgAdmin (admin / admin) |

### Parar el proyecto

```bash
docker-compose down
```

---

## ⚙️ Variables de entorno (`.env`)

El archivo `.env` en la raíz configura tanto la API Node.js como el contenedor de Drupal.

| Variable | Descripción | Ejemplo |
|---|---|---|
| `PORT` | Puerto de la API Node.js | `3000` |
| `JWT_SECRET` | Clave secreta para firmar tokens JWT | `<clave_secreta_larga_y_aleatoria>` |
| `DB_USER` | Usuario de PostgreSQL | `<usuario_bd>` |
| `DB_HOST` | Host de la BD (nombre del servicio Docker) | `postgres` |
| `DB_NAME` | Nombre de la base de datos | `<nombre_bd>` |
| `DB_PASSWORD` | Contraseña de PostgreSQL | `<contraseña_bd>` |
| `DB_PORT` | Puerto de PostgreSQL | `5432` |
| `GLPI_APP_TOKEN` | Token de aplicación de la API GLPI | `<app_token_glpi>` |
| `GLPI_USER_TOKEN` | Token de usuario de la API GLPI | `<user_token_glpi>` |
| `GLPI_BASE_URL` | URL base de la API REST de GLPI | `<url_base_glpi>/apirest.php` |

> ⚠️ **Seguridad:** El archivo `.env` está en `.gitignore`. **Nunca lo subas al repositorio.** Solicita los valores reales al administrador del proyecto.

---

## 🟢 API Node.js — Endpoints

La API escucha en `http://localhost:3000`. Todas las rutas protegidas requieren un header:
```
Authorization: Bearer <token_jwt>
```

### Autenticación

#### `POST /auth/login`

Autentica un usuario y devuelve un token JWT con validez de 1 hora.

**Body (JSON):**
```json
{
  "username": "lorena",
  "password": "1234"
}
```

**Respuesta exitosa (200):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Respuesta de error (401):**
```json
{
  "error": "Credenciales incorrectas"
}
```

---

### Rutas protegidas (requieren JWT)

#### `GET /api/ver-historial` 🔒

Devuelve el contenido del archivo `introducir-texto.txt`.

**Respuesta (200):** Texto plano con el historial.

---

#### `GET /api/saludar` 🔒

Devuelve un saludo personalizado con la hora del servidor y una imagen.

**Respuesta (200):**
```json
{
  "mensaje": "¡Hola lorena, bienvenido a la API!",
  "hora": "09:15:32",
  "foto": "https://images.unsplash.com/photo-1518770660439-4636190af475?w=500&q=80"
}
```

---

## 🟣 Módulo Drupal — `conector_api`

Módulo personalizado ubicado en `drupal-data/modules/custom/conector_api/`. Actúa como puente entre la interfaz web de Drupal y la API Node.js / GLPI.

### Hacer login en la plataforma

1. Ve a `http://localhost:8080/api/entrar`
2. Introduce las credenciales definidas en `authController.js`
3. Serás redirigido a la página de historial

### Rutas disponibles en Drupal

| Ruta Drupal | URL | Descripción |
|---|---|---|
| `conector_api.historial` | `http://localhost:8080/api/ver-txt` | Muestra el historial del archivo de texto (requiere sesión) |
| `conector_api.saludo` | `http://localhost:8080/api/saludar` | Muestra el saludo con hora e imagen (requiere sesión) |
| `conector_api.info_apis` | `http://localhost:8080/api/informacion` | Página educativa sobre qué es una API |
| `conector_api.buscar_glpi` | `http://localhost:8080/api/glpi/buscar/{email}` | Busca un usuario en GLPI por su email |
| `conector_api.tareas_usuario` | `http://localhost:8080/api/glpi/tareas/{id_usuario}` | Muestra las tareas asignadas a un técnico de GLPI |
| `conector_api.lista_trabajadores` | `http://localhost:8080/api/glpi/trabajadores` | Listado paginado de todos los trabajadores del IAC |

### Flujo de autenticación en Drupal

```
1. Usuario accede a /api/entrar
2. Drupal muestra LoginForm (usuario + contraseña)
3. Al enviar, Drupal llama a POST http://api:3000/auth/login
4. Si la API devuelve un token JWT → se guarda en la sesión de Drupal
5. El usuario es redirigido a /api/ver-txt
6. Las páginas protegidas usan el token de sesión para llamar a la API
7. Si el token caduca (401/403), limpia sesión y redirige al login
```

### Integración con GLPI

Las páginas de GLPI (`GlpiController`) usan el patrón **Sesión Efímera**:
1. `GET /initSession` → obtiene un `session_token` temporal
2. Realiza la consulta necesaria a la API REST de GLPI
3. `GET /killSession` → cierra la sesión inmediatamente

| Método GLPI | Campo | Descripción |
|---|---|---|
| `search/User` + campo `5` | Email | Búsqueda de usuario por correo |
| `search/User` + campos `2,5,34,80` | Lista paginada | ID, Email, Nombre, Departamento |
| `search/TicketTask` + campo `5` | Técnico asignado | Tareas por ID de técnico |

---

## 🗄️ Base de datos (PostgreSQL)

La base de datos `db-api-daw` contiene:
- Las tablas de Drupal (con prefijo `api_`)
- La tabla `api_logs` creada por la API Node.js al arrancar

**Credenciales de acceso:** las definidas en `docker-compose.yml` (`POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB`).

**pgAdmin:** `http://localhost:5050`
- Credenciales: las definidas en `docker-compose.yml` (`PGADMIN_DEFAULT_EMAIL`, `PGADMIN_DEFAULT_PASSWORD`)

---

## 🏗️ Estructura del proyecto

```
v3-api-iac/
├── app.js                          # Punto de entrada de la API Node.js
├── db.js                           # Configuración del pool de PostgreSQL
├── Dockerfile                      # Imagen Docker para la API Node.js
├── docker-compose.yml              # Orquestación de todos los servicios
├── .env                            # Variables de entorno (no subir a Git)
├── package.json                    # Dependencias Node.js
│
├── controllers/
│   ├── authController.js           # Login → genera JWT
│   └── fileController.js           # Historial y saludo (rutas protegidas)
│
├── routes/
│   ├── authRoutes.js               # POST /auth/login
│   └── fileRoutes.js               # GET /api/ver-historial, /api/saludar
│
├── middlewares/
│   └── authMiddleware.js           # Verificación de token JWT
│
├── introducir-texto.txt            # Archivo de historial (excluido de Git)
│
└── drupal-data/
    ├── modules/custom/conector_api/    # Módulo Drupal personalizado
    │   ├── conector_api.info.yml
    │   ├── conector_api.routing.yml    # 7 rutas Drupal
    │   ├── conector_api.libraries.yml
    │   ├── css/
    │   └── src/
    │       ├── Controller/
    │       │   ├── HistorialController.php  # Login, historial, saludo, infoAPIs
    │       │   └── GlpiController.php       # Integración con API GLPI
    │       └── Form/
    │           ├── LoginForm.php            # Formulario de autenticación
    │           └── TrabajadorFilterForm.php # Filtros de búsqueda de trabajadores
    ├── sites/default/
    │   ├── settings.php                # Configuración de Drupal
    │   └── services.yml                # Servicios del contenedor DI
    └── db_data/                        # Datos persistentes de PostgreSQL
```

---

## 🔧 Mantenimiento

### Limpiar caché de Drupal

Si Drupal muestra errores inesperados tras cambios en el módulo, ejecuta:

```bash
docker-compose exec drupal sh -c "cd /opt/drupal && vendor/bin/drush cache:rebuild"
```

### Ver logs en tiempo real

```bash
# Todos los servicios
docker-compose logs -f

# Solo la API Node.js
docker-compose logs -f api

# Solo Drupal
docker-compose logs -f drupal
```

### Reconstruir la imagen de la API tras cambios en el código

```bash
docker-compose up --build api
```

### Acceder a la shell de un contenedor

```bash
# API Node.js
docker-compose exec api sh

# Drupal
docker-compose exec drupal bash

# PostgreSQL
docker-compose exec postgres psql -U user -d db-api-daw
```

---

## 🛡️ Seguridad

- Los tokens JWT caducan en **1 hora**.
- Las rutas protegidas de la API rechazan peticiones sin token válido con `401 Unauthorized`.
- Los datos mostrados en Drupal se **sanitizan** con `Html::escape()` y `UrlHelper::filterBadProtocol()` para prevenir ataques XSS.
- La base de datos PostgreSQL **no está expuesta** al exterior; solo es accesible dentro de la red Docker interna.
- El archivo `.env` con las credenciales está excluido de Git mediante `.gitignore`.

---

## 🔗 Tecnologías utilizadas

| Tecnología | Versión | Rol |
|---|---|---|
| Node.js | 20 (Alpine) | Runtime de la API |
| Express.js | 4.x | Framework HTTP |
| jsonwebtoken | — | Autenticación JWT |
| pg (node-postgres) | — | Conexión a PostgreSQL |
| dotenv | 17.x | Gestión de variables de entorno |
| nodemon | 3.x | Recarga automática en desarrollo |
| Drupal | 10 (Apache) | CMS / Frontend |
| PHP | 8.4 | Runtime de Drupal |
| GuzzleHTTP | — | Cliente HTTP en Drupal |
| Drush | 13.7 | CLI de administración de Drupal |
| PostgreSQL | 15 | Base de datos relacional |
| pgAdmin | 4 | Administración de BD |
| Docker Compose | — | Orquestación de servicios |

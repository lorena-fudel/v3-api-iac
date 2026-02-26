v2
# 🚀 Drupal 10 & Node.js API Gateway Integration

Este proyecto consiste en una arquitectura de microservicios orquestada con **Docker**, que integra un CMS **Drupal 10**, una **API REST en Node.js**, una base de datos **PostgreSQL** y la integración funcional con la API externa de gestión de activos **GLPI**.

## 🏗️ Arquitectura del Sistema

El ecosistema se despliega en una red aislada (`mi-red-segura`) y se compone de cuatro contenedores interconectados:

* **Drupal 10 (CMS):** Actúa como la interfaz de usuario principal y "Hub" de datos, consumiendo APIs mediante el cliente Guzzle.
* **Node.js API:** Backend personalizado encargado de la lógica de negocio, autenticación de usuarios y gestión de archivos de sistema.
* **PostgreSQL 15:** Motor de base de datos relacional que da soporte tanto a Drupal como a la persistencia de la API.
* **pgAdmin 4:** Herramienta de administración visual para la gestión de tablas y monitorización de datos.

## 🛠️ Funcionalidades Implementadas

### 🔐 Seguridad y Gestión de Secretos
* **Autenticación JWT:** Implementación de tokens de seguridad para proteger el acceso a los endpoints del backend en Node.js.
* **Variables de Entorno:** Uso de un archivo `.env` centralizado para gestionar credenciales sensibles (Tokens, claves maestras y URLs), inyectándolas de forma segura a través de Docker Compose.

### 🔌 Integración Avanzada con GLPI
* **Handshake de Sesión:** Flujo automatizado de autenticación técnica: `initSession` -> `Request` -> `killSession`.
* **Búsqueda Parametrizada:** Localización dinámica de perfiles mediante correo electrónico y recuperación de tareas técnicas (`TicketTask`) vinculadas a un ID de técnico.
* **Mapeo de Metadatos:** Sincronización de identificadores de campos dinámicos mediante auditoría de esquemas con el endpoint `listSearchOptions`.

### 📂 Gestión de Datos y Persistencia
* **I/O de Archivos:** Lectura y sincronización de contenido entre archivos planos del servidor (`.txt`) y la interfaz visual de Drupal.
* **Administración SQL:** Configuración de pgAdmin para la monitorización de las tablas del core de Drupal y gestión de logs de actividad.

## 🚀 Instalación y Despliegue

1.  **Clonar el repositorio:**
    ```bash
    git clone [https://github.com/tu-usuario/tu-proyecto.git](https://github.com/tu-usuario/tu-proyecto.git)
    cd tu-proyecto
    ```

2.  **Configurar el archivo `.env`:**
    Crea un archivo `.env` en la raíz con tus credenciales reales:
    ```env
    # API & Auth (Node.js)
    JWT_SECRET=tu_clave_secreta_maestra
    
    # Database (PostgreSQL)
    POSTGRES_DB=nombre de la db
    POSTGRES_USER=nombre de ususario
    POSTGRES_PASSWORD= contraseña
    
    # GLPI Integration (IAC)
    GLPI_BASE_URL=url que proceda
    GLPI_APP_TOKEN=tu_app_token_generado
    GLPI_USER_TOKEN=tu_user_token_personal
    ```

3.  **Desplegar el Stack:**
    ```bash
    docker-compose up -d
    ```

## 📍 Endpoints Principales

### API Interna (Node.js)
| Método | Ruta | Descripción |
| :--- | :--- | :--- |
| `POST` | `/auth/login` | Autenticación y generación de Bearer Token (JWT). |
| `GET` | `/api/ver-historial` | Recuperación de datos desde el archivo `introducir-texto.txt`. |

### API Externa (Drupal ↔ GLPI)
| Ruta en Drupal | Acción |
| :--- | :--- |
| `/api/glpi/buscar/{email}` | Consulta perfiles de usuario en GLPI filtrando por correo. |
| `/api/glpi/tareas/{id}` | Lista todas las `TicketTask` asignadas a un técnico específico. |

## 🛠️ Tecnologías Utilizadas

* **Lenguajes:** PHP 8.4, JavaScript (Node.js), SQL.
* **Frameworks:** Drupal 10, Express.js.
* **Herramientas:** Docker & Docker Compose, Guzzle HTTP, Postman, pgAdmin 4, JWT.

---
📝 **Autor:** Lorena Fudel - *Desarrollo e Integración de Sistemas (IAC)*
# 📋 Guía de Inicialización — API IAC

> Sigue esta guía de forma secuencial la **primera vez** que arranques el proyecto.

---

## ✅ Requisitos previos

Asegúrate de tener instalado en tu máquina:

- **Docker Desktop** → [descargar aquí](https://www.docker.com/products/docker-desktop/)
- **Git** → [descargar aquí](https://git-scm.com/downloads)

Verifica que Docker esté corriendo antes de continuar:

```bash
docker --version
docker-compose --version
```

---

## Paso 1 — Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
cd v3-api-iac
```

---

## Paso 2 — Configurar las variables de entorno

Crea el archivo `.env` en la raíz del proyecto con el siguiente contenido:

```env
PORT=3000
JWT_SECRET=<clave_secreta_larga_y_aleatoria>
DB_USER=<usuario_bd>
DB_HOST=postgres
DB_NAME=<nombre_bd>
DB_PASSWORD=<contraseña_bd>
DB_PORT=5432
GLPI_APP_TOKEN=<app_token_glpi>
GLPI_USER_TOKEN=<user_token_glpi>
GLPI_BASE_URL=<url_de_tu_glpi>/apirest.php
```

> ⚠️ **Solicita los valores reales** de los tokens GLPI y las credenciales de BD al administrador del proyecto. **Nunca compartas este archivo ni lo subas a GitHub.**

---

## Paso 3 — Arrancar todos los servicios

```bash
docker-compose up
```

Este comando descarga las imágenes Docker necesarias, construye la imagen de la API Node.js e inicia los 4 servicios (API, Drupal, PostgreSQL, pgAdmin).

La primera ejecución puede tardar **3-5 minutos**.

Cuando veas estas líneas en el terminal, todo está listo:

```
api-1     | 🚀 Servidor escuchando en el puerto 3000
api-1     | ✅ Base de datos conectada y tabla lista
drupal-1  | AH00163: Apache/2.4.66 configured -- resuming normal operations
```

---

## Paso 4 — Instalar Drush (solo la primera vez)

Drush es la herramienta de administración de Drupal por línea de comandos. Es necesaria para limpiar la caché.

**Abre una nueva ventana de terminal** (deja `docker-compose up` corriendo) y ejecuta:

```bash
docker-compose exec drupal sh -c "cd /opt/drupal && composer require drush/drush:^13 --no-interaction"
```

Espera a que termine (puede tardar 1-2 minutos).

---

## Paso 5 — Reconstruir la caché de Drupal

Este paso es **obligatorio** la primera vez y cada vez que hagas cambios en el módulo `conector_api`:

```bash
docker-compose exec drupal sh -c "cd /opt/drupal && vendor/bin/drush cache:rebuild"
```

Deberías ver:

```
[success] Cache rebuild complete.
```

---

## Paso 6 — Verificar que todo funciona

Abre tu navegador y comprueba estas URLs:

| URL | Qué deberías ver |
|-----|-----------------|
| `http://localhost:8080/api/entrar` | 📝 Formulario de login |
| `http://localhost:8080/api/informacion` | 📖 Página explicativa sobre APIs |
| `http://localhost:8080/api/glpi/trabajadores` | 👥 Listado de trabajadores del IAC |
| `http://localhost:5050` | 🗄️ pgAdmin (admin / admin) |

### Hacer login en la plataforma

1. Ve a `http://localhost:8080/api/entrar`
2. Introduce las credenciales definidas en `authController.js` (pídeselas al administrador del proyecto)
3. Serás redirigido a la página de historial

---

## 🔄 Arranques posteriores

Una vez configurado el proyecto, para arrancar en el futuro solo necesitas:

```bash
docker-compose up
```

Y si Drupal muestra algún error tras un cambio en el código:

```bash
docker-compose exec drupal sh -c "cd /opt/drupal && vendor/bin/drush cache:rebuild"
```

---

## 🛑 Parar el proyecto

```bash
# Parar manteniendo los datos
docker-compose down

# Parar y eliminar todos los datos (BD incluida) ⚠️
docker-compose down -v
```

---

## 🆘 Solución de problemas comunes

### Drupal muestra "Error 500"

```bash
docker-compose exec drupal sh -c "cd /opt/drupal && vendor/bin/drush cache:rebuild"
```

### Ver qué está pasando en los contenedores

```bash
docker-compose logs -f
```

### La API Node.js no conecta con la BD

Comprueba que el valor de `DB_HOST` en `.env` sea exactamente `postgres` (el nombre del servicio en `docker-compose.yml`), no `localhost`.

### El puerto 8080 o 3000 ya está en uso

Cambia el puerto en `docker-compose.yml`:
```yaml
ports:
  - "8081:80"   # Cambia 8080 por 8081 en Drupal
```

---

## 📁 Estructura del proyecto (resumen)

```
v3-api-iac/
├── .env                        ← Variables de entorno (créalo en el Paso 2)
├── docker-compose.yml          ← Orquestación de servicios
├── Dockerfile                  ← Imagen de la API Node.js
├── app.js                      ← Punto de entrada de la API
├── controllers/                ← Lógica de la API (login, historial, saludo)
├── routes/                     ← Definición de rutas HTTP de la API
├── middlewares/                ← Verificación de JWT
└── drupal-data/
    └── modules/custom/
        └── conector_api/       ← Módulo Drupal personalizado
```

---

*Última actualización: febrero 2026*

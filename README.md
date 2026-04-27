# API REST — PHP MVC

API RESTful para gestión de usuarios, autenticación y contenido educativo, construida en PHP con el framework **Slim 4** siguiendo el patrón **MVC** y Composer para la gestión de dependencias. Incluye autenticación JWT, pipeline de middleware, sistema de suscripciones y panel administrativo.

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Lenguaje | PHP 8+ |
| Framework | Slim 4 |
| Arquitectura | MVC (Modelo - Vista - Controlador) |
| Autenticación | JWT (JSON Web Tokens) |
| Base de datos | MySQL / MariaDB |
| Dependencias | Composer |
| Estilo de API | REST (JSON) |

---

## Estructura del proyecto

```
.
├── src/
│   └── Modules/
│       ├── Controllers/       # Gestión de peticiones HTTP y respuestas
│       │   ├── AuthController
│       │   ├── UsuarioController
│       │   ├── BloqueController
│       │   ├── NivelController
│       │   ├── EjercicioController
│       │   ├── RespuestaEjercicioController
│       │   ├── AssessmentController
│       │   ├── TrialController
│       │   ├── ForoController
│       │   ├── ResenaController
│       │   ├── PlanSuscripcionController
│       │   ├── SuscripcionController
│       │   └── DifusionController
│       ├── Models/            # Entidades y consultas a la base de datos
│       ├── Services/          # Lógica de negocio
│       └── Middleware/        # Autenticación, validación y control de acceso
│           ├── AuthMiddleware
│           ├── AdminMiddleware
│           ├── SuscripcionMiddleware
│           ├── TokenBlacklistMiddleware
│           └── ArticulosMiddleware
├── public/
│   └── index.php              # Punto de entrada — todas las peticiones pasan por aquí
├── vendor/                    # Dependencias de Composer (no incluidas en el repo)
├── .env.example               # Plantilla de variables de entorno
├── composer.json
├── composer.lock
└── routes.php                 # Definición de todas las rutas
```

---

## Requisitos

- PHP 8.0 o superior
- Composer
- MySQL / MariaDB
- Apache (con `mod_rewrite` activado) o Nginx

---

## Instalación

**1. Clonar el repositorio**
```bash
git clone https://github.com/tu-usuario/tu-repo.git
cd tu-repo
```

**2. Instalar dependencias**
```bash
composer install
```

**3. Configurar las variables de entorno**
```bash
cp .env.example .env
```

Editar `.env` con los valores correspondientes:
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=nombre_base_datos
DB_USER=usuario
DB_PASS=contraseña

JWT_SECRET=clave_secreta_jwt
JWT_EXPIRATION=3600
```

**4. Importar la base de datos**
```bash
mysql -u usuario -p nombre_base_datos < database/schema.sql
```

**5. Configurar el servidor web**

Para Apache, asegurarse de que `.htaccess` está presente y `mod_rewrite` activado:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ public/index.php [QSA,L]
```

Para Nginx:
```nginx
location / {
    try_files $uri $uri/ /public/index.php?$query_string;
}
```

---

## Autenticación

La API utiliza **JWT (JSON Web Tokens)** para autenticación sin estado. Los tokens invalidados se almacenan en una blacklist para gestionar los cierres de sesión correctamente.

### Flujo

```
Cliente                              API
───────                              ───
POST /auth/login    ──────────────►  Valida credenciales
                    ◄──────────────  Devuelve token JWT
GET  /recurso       ──────────────►  TokenBlacklist → Auth → Controlador
  Authorization: Bearer <token>
                    ◄──────────────  Respuesta del recurso protegido
```

### Uso del token

Incluir el token en la cabecera `Authorization` en cada petición protegida:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## Endpoints

### 🔐 Autenticación `/auth`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `POST` | `/auth/login` | Iniciar sesión y obtener token JWT | No |
| `POST` | `/auth/register` | Registrar nuevo usuario | No |
| `POST` | `/auth/forgot-password` | Solicitar recuperación de contraseña | No |
| `POST` | `/auth/reset-password` | Restablecer contraseña con token | No |
| `GET` | `/auth/verify-email` | Comprobar disponibilidad de email | No |
| `POST` | `/auth/validate-register` | Validar campos del formulario de registro | No |
| `POST` | `/auth/logout` | Cerrar sesión e invalidar token | ✅ |
| `GET` | `/auth/me` | Obtener datos del usuario autenticado | ✅ |
| `GET` | `/auth/check-status` | Comprobar rol del usuario | ✅ |

### 👤 Usuarios `/usuarios`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/usuarios/perfil` | Obtener perfil del usuario autenticado | ✅ |
| `PUT` | `/usuarios/perfil` | Actualizar perfil | ✅ |
| `GET` | `/usuarios/profile/{username}` | Ver perfil público de un usuario | ✅ |
| `POST` | `/usuarios/imagen` | Actualizar imagen de perfil | ✅ |
| `PUT` | `/usuarios/password` | Cambiar contraseña | ✅ |
| `GET` | `/usuarios/estadisticas` | Obtener estadísticas del usuario | ✅ |
| `GET` | `/usuarios/suscripcion` | Ver suscripción activa | ✅ |
| `POST` | `/usuarios/suscripcion` | Suscribirse a un plan | ✅ |
| `POST` | `/usuarios/suscripcion/cancelar` | Cancelar suscripción | ✅ |
| `GET` | `/usuarios/buscar` | Buscar usuarios | ✅ 🔑 Admin |
| `POST` | `/usuarios/update-role` | Cambiar rol de un usuario | ✅ 🔑 Admin |
| `PUT` | `/usuarios/reset-password` | Restablecer contraseña de un usuario | ✅ 🔑 Admin |

### 📦 Bloques `/bloques`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/bloques` | Listar todos los bloques | ✅ |
| `GET` | `/bloques/{id}` | Ver un bloque | ✅ |
| `GET` | `/bloques/{id}/niveles` | Obtener niveles de un bloque | ✅ |
| `GET` | `/bloques/nivel/{nivelId}` | Obtener bloques por nivel | ✅ |
| `GET` | `/bloques/progreso` | Bloques con progreso del usuario | ✅ |
| `GET` | `/bloques/bloques-suscripcion` | Bloques según suscripción | ✅ |
| `GET` | `/bloques/progreso-niveles-ejercicios` | Progreso detallado por niveles y ejercicios | ✅ |
| `GET` | `/bloques/filtrar-ejercicios` | Filtrar ejercicios de un bloque | ✅ |
| `POST` | `/bloques/corregir` | Corregir test de un bloque | ✅ |
| `POST` | `/bloques` | Crear bloque | ✅ 🔑 Admin |
| `PUT` | `/bloques/{id}` | Editar bloque | ✅ 🔑 Admin |
| `DELETE` | `/bloques/{id}` | Eliminar bloque | ✅ 🔑 Admin |

### 🎯 Niveles `/niveles`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/niveles` | Listar niveles | ✅ |
| `GET` | `/niveles/{id}` | Ver un nivel | ✅ |
| `POST` | `/niveles` | Crear nivel | ✅ 🔑 Admin |
| `PUT` | `/niveles/{id}` | Editar nivel | ✅ 🔑 Admin |
| `DELETE` | `/niveles/{id}` | Eliminar nivel | ✅ 🔑 Admin |

### ✏️ Ejercicios `/ejercicios`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/ejercicios` | Listar ejercicios | ✅ |
| `GET` | `/ejercicios/{id}` | Ver un ejercicio | ✅ |
| `GET` | `/ejercicios/buscar` | Buscar ejercicios | ✅ 🔑 Admin |
| `POST` | `/ejercicios` | Crear ejercicio | ✅ 🔑 Admin |
| `PUT` | `/ejercicios/{id}` | Editar ejercicio | ✅ 🔑 Admin |
| `DELETE` | `/ejercicios/{id}` | Eliminar ejercicio | ✅ 🔑 Admin |
| `POST` | `/ejercicios/bulk` | Importar ejercicios en masa | ✅ 🔑 Admin |

### ✔️ Respuestas de ejercicio `/respuestas-ejercicio`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/respuestas-ejercicio` | Listar respuestas | ✅ |
| `GET` | `/respuestas-ejercicio/{id}` | Ver una respuesta | ✅ |
| `POST` | `/respuestas-ejercicio` | Crear respuesta | ✅ 🔑 Admin |
| `PUT` | `/respuestas-ejercicio/{id}` | Editar respuesta | ✅ 🔑 Admin |
| `DELETE` | `/respuestas-ejercicio/{id}` | Eliminar respuesta | ✅ 🔑 Admin |

### 📊 Assessment `/assessment`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/assessment/preguntas` | Obtener preguntas del assessment | No |
| `POST` | `/assessment/calcular` | Calcular resultado del assessment | No |
| `POST` | `/assessment/asignar-bloques` | Asignar bloques según resultado | ✅ |

### 🧪 Trial `/trial`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `POST` | `/trial/questions` | Obtener preguntas del modo prueba | No |
| `POST` | `/trial/check` | Comprobar respuesta | No |
| `POST` | `/trial/sync` | Sincronizar respuestas del trial | ✅ |

### 💬 Foro `/foro`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/foro/mensajes` | Listar mensajes del foro | ✅ |
| `POST` | `/foro/mensajes` | Publicar mensaje (requiere suscripción) | ✅ 💳 |
| `GET` | `/foro/usuario/{id}` | Ver mensajes de un usuario | ✅ |
| `DELETE` | `/foro/mensajes/{id}` | Eliminar mensaje | ✅ 🔑 Admin |

### ⭐ Reseñas `/resenas`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/resenas/aleatorias` | Obtener reseñas aleatorias | No |
| `POST` | `/resenas` | Crear reseña | ✅ |

### 💳 Planes de suscripción `/planes`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/planes-public` | Listar planes disponibles (público) | No |
| `GET` | `/planes` | Listar planes disponibles | ✅ |
| `GET` | `/planes/{id}` | Ver un plan | ✅ 🔑 Admin |
| `POST` | `/planes` | Crear plan | ✅ 🔑 Admin |
| `PUT` | `/planes/{id}` | Editar plan | ✅ 🔑 Admin |
| `DELETE` | `/planes/{id}` | Eliminar plan | ✅ 🔑 Admin |

### 📋 Suscripciones `/suscripciones`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `GET` | `/suscripciones` | Listar todas las suscripciones | ✅ 🔑 Admin |
| `GET` | `/suscripciones/{id}` | Ver una suscripción | ✅ 🔑 Admin |

### 📣 Difusión `/difusion`

| Método | Ruta | Descripción | Auth |
|--------|------|-------------|------|
| `POST` | `/difusion/send` | Enviar mensaje de difusión a usuarios | ✅ 🔑 Admin |

### 🟢 Health check

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/health` | Comprueba que la API está en línea |

---

## Middleware

Las peticiones pasan por un pipeline de middleware antes de llegar al controlador:

| Middleware | Propósito |
|---|---|
| `AuthMiddleware` | Valida el token JWT en rutas protegidas |
| `TokenBlacklistMiddleware` | Comprueba que el token no ha sido invalidado (logout) |
| `AdminMiddleware` | Restringe el acceso a usuarios con rol administrador |
| `SuscripcionMiddleware` | Verifica que el usuario tiene una suscripción activa |
| `ArticulosMiddleware` | Control de acceso específico para artículos |

### Orden de ejecución

```
Petición → TokenBlacklistMiddleware → AuthMiddleware → [AdminMiddleware / SuscripcionMiddleware] → Controlador
```

---

## Variables de entorno

| Variable | Descripción |
|---|---|
| `DB_HOST` | Host de la base de datos |
| `DB_PORT` | Puerto de la base de datos |
| `DB_NAME` | Nombre de la base de datos |
| `DB_USER` | Usuario de la base de datos |
| `DB_PASS` | Contraseña de la base de datos |
| `JWT_SECRET` | Clave secreta para firmar los tokens |
| `JWT_EXPIRATION` | Duración del token en segundos |

> ⚠️ El archivo `.env` nunca debe subirse al repositorio. Está incluido en `.gitignore`.

---

## Seguridad

- Las contraseñas se hashean con `password_hash()` usando `PASSWORD_BCRYPT`
- El secreto JWT se carga exclusivamente desde variables de entorno
- Los tokens invalidados se gestionan mediante una blacklist
- Se utilizan sentencias preparadas en todas las consultas para prevenir inyección SQL
- Las rutas administrativas están protegidas por un middleware de rol independiente

---

## Licencia

Este proyecto es privado. Todos los derechos reservados.

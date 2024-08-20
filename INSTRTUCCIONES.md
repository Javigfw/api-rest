### 1. CONFIGURAR EL CLIENTE EN EL ARCHIVO SFTP
- Dentro de la carpeta '.VSCODE' poner los parametros en 'sftp.josn'

### 2. CONFIGURAR LOS PARAMETROS DE API Y DE LA BASE DE DATOS
- Dentro de APP -> config.php.

### 3. CONFIGURAR LAS RUTAS DE ACCESO DE LOS METODOS AL API
- Dentro de APP -> routes.php.

### 4. CONFIGURAR COMPOSER.JSON
- Aqui definimos cada una de las carpetas que vamos a tener dentro de la carpeta de modules. Ejem: "App\\Authentication\\": "src/Modules/Authentication/"

- Por cada carpeta o ruta que definimos dentro de esta carpeta, debemos de hacer un composer update desde la terminal. Para que creé la carpeta Vendor de nuevo y se sincronice automaticamente en el servidor, mediante el plugin 'sftp.json'

### 5. CREAR LOS ARCHIBOS PHP NECESARIOS EN MODULES
- Por cada ruta creada en el archivo 'composer.json' debemos de crear una carpeta con el mismo nombre dentro de Modules.
- Dentro de cada carpeta debemos de crear un archivo controlador.php. Por ejemplo para usuario 'UsuariosController.php' en el cual se definiran cada unos de los metodos para las distintas peticiones del Api.
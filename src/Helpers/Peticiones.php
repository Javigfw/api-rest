<?php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use PDO;
use PDOException;
use Exception;

error_reporting(0);

class Peticiones
{
    /**
     * Function para comprobar el ApiKey y la clave
     * 
     * @param String $apiKey    numero del api
     * @param String $clave     clave del api
     * 
     * @return $datos[]         retorna 0 si no conincide y 1 si es valida
     */
    public static function comprobarApiClave($apiKey, $clave)
    {
        $sql = "SELECT COUNT(*) as cantidad FROM DISPOSITIVO WHERE api_key = :api_Key and clave = :clave";
        $datos = Queries::leer($sql, [
            'api_Key' => [$apiKey, PDO::PARAM_STR],
            'clave' => [$clave, PDO::PARAM_STR]
        ]);

        return $datos['data']['cantidad'];
    }


    /**
     * obtenerIdFamiliaPorNombre
     *
     * @param  mixed $nombreFamilia
     * @return Response
     */
    public static function obtenerIdFamiliaPorNombre($nombreFamilia)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM familia WHERE nombre = :nombreFamilia ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombreFamilia' => [$nombreFamilia, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }


    /**
     * obtenerIdSubfamiliaPorNombre
     *
     * @param  mixed $nombreSubfamilia
     * @return void
     */
    public static function obtenerIdSubfamiliaPorNombre($nombreSubfamilia)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM subfamilia WHERE nombre = :nombreSubfamilia ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombreSubfamilia' => [$nombreSubfamilia, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }



    /**
     * obtenerIdMarcaPorNombre
     *
     * @param  mixed $nombreMarca
     * @return void
     */
    public static function obtenerIdMarcaPorNombre($nombreMarca)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM marca WHERE nombre = :nombreMarca ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombreMarca' => [$nombreMarca, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }


    /**
     * obtenerIdIvaPorNombre
     *
     * @param  mixed $nombreIva
     * @return void
     */
    public static function obtenerIdIvaPorNombre($nombreIva)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM iva WHERE nombre = :nombreIva ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombreIva' => [$nombreIva, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }


    /**
     * obtenerIdPantallaPorNombre
     *
     * @param  mixed $nombrePantalla
     * @return void
     */
    public static function obtenerIdPantallaPorNombre($nombrePantalla)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM pantalla WHERE nombre = :nombrePantalla ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombrePantalla' => [$nombrePantalla, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }

    /**
     * obtenerIdUsuario
     *
     * @param  mixed $email
     * @return int
     */
    public static function obtenerIdUsuario($email): int
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM usuario WHERE email = :email ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'email' => [$email, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }

    /**
     * obtenerUltimoAutoIncrement
     *
     * @return int
     */
    public static function obtenerUltimoAutoIncrement(): int
    {
        // generamos la consulta
        $sql = "SELECT LAST_INSERT_ID() as codigo";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, []);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }

    /**
     * obtenerImagenArticulo
     *
     * @param  mixed $id
     * @param  mixed $tipo
     * @return mixed
     */
    public static function obtenerImagenArticulo(int $id, int $tipo = 0): mixed
    {
        // Definir las rutas para las imágenes pequeña y grande
        $rutaP = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/p/' . $id . '.jpg';
        $rutaG = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/g/' . $id . '.jpg';

        // Inicializar las variables de contenido
        $contenidoP = '';
        $contenidoG = '';

        // Verificar si se debe incluir la imagen pequeña y si existe
        if (($tipo == 0 || $tipo == 1) && file_exists($rutaP)) {
            $contenidoP = base64_encode(file_get_contents($rutaP));
        }

        // Verificar si se debe incluir la imagen grande y si existe
        if (($tipo == 0 || $tipo == 2) && file_exists($rutaG)) {
            $contenidoG = base64_encode(file_get_contents($rutaG));
        }

        // Retornar la imagen o las imágenes según el tipo
        if ($tipo == 0) {
            return [
                'p' => $contenidoP,
                'g' => $contenidoG
            ];
        } elseif ($tipo == 1) {
            return $contenidoP;
        } elseif ($tipo == 2) {
            return $contenidoG;
        } else {
            return '';
        }
    }

    /**
     * comprobarExisteUsuario
     *
     * @param  mixed $codigoUsuario
     * @return bool
     */
    public static function comprobarExisteUsuario($codigoUsuario): bool
    {
        // generamos la consulta
        $sql = "SELECT COUNT(codigo) as codigo FROM usuario WHERE codigo = :codigo ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'codigo' => [$codigoUsuario, PDO::PARAM_INT]
        ]);
        // devolvemos el resultado        
        return intval($datos['data']['codigo']) > 0;
    }


    /**
     * esUsuarioAdministrador
     *
     * @param  mixed $codigoUsuario
     * @return bool
     */
    public static function esUsuarioAdministrador($codigoUsuario): bool
    {
        // generamos la consulta
        $sql = "SELECT COUNT(codigo) as codigo FROM usuario WHERE codigo = :codigo and es_admin=1";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'codigo' => [$codigoUsuario, PDO::PARAM_INT]
        ]);
        // devolvemos el resultado        
        return intval($datos['data']['codigo']) > 0;
    }


    /**
     * comprobarExisteArticuloPlantilla
     *
     * @param  mixed $articulo
     * @return array
     */
    public static function comprobarExisteArticuloPlantilla($articulo): array
    {
        $resultado = [
            'codigo' => 0,
            'descripcion' => ''
        ];

        $sql = "SELECT codigo, descripcion FROM articulo WHERE codigo = :codigo";

        $datos = Queries::leer($sql, [
            'codigo' => [$articulo, PDO::PARAM_INT]
        ]);

        if ($datos['status'] == 'ok') {
            $resultado['codigo'] = intval($datos['data']['codigo']);
            $resultado['descripcion'] = $datos['data']['descripcion'];
        }

        return $resultado;
    }


    /**
     * crearTablasLocal
     *
     * @param  mixed $pdo
     * @param  mixed $localNumber
     * @return void
     */
    static function crearTablasLocal($pdo)
    {
        // obtenmos el autoincremental del ultimo valor insertado
        $ultimoId = Peticiones::obtenerUltimoAutoIncrement();

        // Formatear el número a cuatro dígitos
        $formattedLocal = Utils::formateaIdCuatroDigitos($ultimoId);

        // Script SQL con la cadena _{local} a reemplazar
        $sqlScript = "
        CREATE TABLE IF NOT EXISTS `articulo_cod_{local}` (
            `codigo` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
            `activo` tinyint(1) NOT NULL DEFAULT 1,
            `codigo_tpv` mediumint(8) UNSIGNED NULL,
            PRIMARY KEY(`codigo`)
        );
    
        CREATE TABLE IF NOT EXISTS `articulo_{local}` (
            `codigo` mediumint(8) UNSIGNED NOT NULL, 
            `descripcion` varchar(60) NOT NULL,
            `codigoBarras` varchar(50) NOT NULL,
            `compra` tinyint(1) NOT NULL DEFAULT 0,
            `venta` tinyint(1) NOT NULL DEFAULT 1,
            `familia` smallint(5) UNSIGNED NOT NULL, 
            `subfamilia` smallint(5) UNSIGNED DEFAULT NULL,
            `pantalla` smallint(5) UNSIGNED DEFAULT NULL, 
            `marca` smallint(5) UNSIGNED DEFAULT NULL,
            `ivaCompra` tinyint(3) UNSIGNED NOT NULL,
            `ivaVenta` tinyint(3) UNSIGNED NOT NULL,
            PRIMARY KEY(`codigo`),
            FOREIGN KEY (`codigo`) REFERENCES `articulo_cod_{local}`(`codigo`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`familia`) REFERENCES `familia`(`codigo`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`subfamilia`) REFERENCES `subfamilia`(`codigo`) ON DELETE SET NULL ON UPDATE CASCADE,
            FOREIGN KEY (`pantalla`) REFERENCES `pantalla` (`codigo`) ON DELETE SET NULL ON UPDATE CASCADE,
            FOREIGN KEY (`ivaCompra`) REFERENCES `iva` (`codigo`),
            FOREIGN KEY (`ivaVenta`) REFERENCES `iva` (`codigo`)
        );
    
        CREATE TABLE IF NOT EXISTS `articulo_sel_{local}` (
            `articulo` mediumint(8) UNSIGNED NOT NULL,
            `codigo` mediumint(8) UNSIGNED NOT NULL,
            PRIMARY KEY(`articulo`),
            FOREIGN KEY (`articulo`) REFERENCES `articulo`(`codigo`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`codigo`) REFERENCES `articulo_cod_{local}`(`codigo`) ON DELETE CASCADE ON UPDATE CASCADE
        );
    
        CREATE TABLE IF NOT EXISTS `tarifa_{local}` (
            `codigo` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(50) NOT NULL,
            PRIMARY KEY (`codigo`)
        );
    
        CREATE TABLE IF NOT EXISTS `tarifa_lin_{local}` (
            `articulo` mediumint(8) UNSIGNED NOT NULL,
            `tarifa` TINYINT UNSIGNED NOT NULL,
            precio decimal(6,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (`articulo`, `tarifa`),
            FOREIGN KEY (`articulo`) REFERENCES `articulo_cod_{local}`(`codigo`) ON DELETE CASCADE ON UPDATE CASCADE, 
            FOREIGN KEY (`tarifa`) REFERENCES `tarifa_{local}`(`codigo`) ON DELETE CASCADE ON UPDATE CASCADE
        );
    
        CREATE TABLE IF NOT EXISTS `pantalla_pant_{local}` (
            `pantalla` varchar(20) NOT NULL,
            `subpantalla` varchar(20) NOT NULL,
            `pagina` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `fila` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `columna` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`pantalla`, `subpantalla`)
        );
    
        CREATE TABLE IF NOT EXISTS `pantalla_art_{local}` (
            `pantalla` varchar(20) NOT NULL,
            `articulo` mediumint(8) UNSIGNED NOT NULL,
            `pagina` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `fila` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `columna` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`pantalla`, `articulo`),
            FOREIGN KEY (`articulo`) REFERENCES `articulo_cod_{local}`(`codigo`) ON DELETE CASCADE ON UPDATE CASCADE
        );
        ";

        // Reemplazar _{local} con el número formateado
        $sqlScript = str_replace('{local}', $formattedLocal, $sqlScript);

        // Ejecutar cada sentencia SQL
        try {
            $pdo->beginTransaction();
            $pdo->exec($sqlScript);
            $pdo->commit();
        } catch (Exception $e) {
            // Si ocurre un error, realizar el rollback
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }


    /*MIDDLEWARE */
    /*MIDDLEWARE */
    /*MIDDLEWARE */
    /*MIDDLEWARE */


    /**
     * getArticuloPlantilla
     *
     * @param  mixed $codigo
     * @param  mixed $campos
     * @return mixed
     */
    public static function getArticulo(string $codigo, array $campos = ['codigo']): mixed
    {
        $numCampos = sizeof($campos);
        if ($numCampos > 0) {
            $select = implode(",", $campos);
            $datos = Queries::leer('SELECT ' . $select . ' FROM articulo WHERE codigo=:codigo', [
                'codigo' => [$codigo, PDO::PARAM_INT],
            ]);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$select];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }


    /**
     * getUsuario
     *
     * @param  mixed $codigo
     * @param  mixed $campos
     * @return mixed
     */
    public static function getUsuario(string $codigo, array $campos = ['codigo']): mixed
    {
        $numCampos = sizeof($campos);
        if ($numCampos > 0) {
            $select = implode(",", $campos);
            $datos = Queries::leer('SELECT ' . $select . ' FROM usuario WHERE codigo=:codigo', [
                'codigo' => [$codigo, PDO::PARAM_INT],
            ]);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$select];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }


    /**
     * getFamilia
     *
     * @param  mixed $codigo
     * @param  mixed $campos
     * @return mixed
     */
    public static function getFamilia(string $codigo, array $campos = ['codigo']): mixed
    {
        $numCampos = sizeof($campos);
        if ($numCampos > 0) {
            $select = implode(",", $campos);
            $datos = Queries::leer('SELECT ' . $select . ' FROM familia WHERE codigo=:codigo', [
                'codigo' => [$codigo, PDO::PARAM_INT],
            ]);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$select];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }

    /**
     * getSubfamilia
     *
     * @param  mixed $codigo
     * @param  mixed $campos
     * @return mixed
     */
    public static function getSubfamilia(string $codigo, array $campos = ['codigo']): mixed
    {
        $numCampos = sizeof($campos);
        if ($numCampos > 0) {
            $select = implode(",", $campos);
            $datos = Queries::leer('SELECT ' . $select . ' FROM subfamilia WHERE codigo=:codigo', [
                'codigo' => [$codigo, PDO::PARAM_INT],
            ]);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$select];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }

    /**
     * getIva
     *
     * @param  mixed $codigo
     * @param  mixed $campos
     * @return mixed
     */
    public static function getIva(string $codigo, array $campos = ['codigo']): mixed
    {
        $numCampos = sizeof($campos);
        if ($numCampos > 0) {
            $select = implode(",", $campos);
            $datos = Queries::leer('SELECT ' . $select . ' FROM iva WHERE codigo=:codigo', [
                'codigo' => [$codigo, PDO::PARAM_INT],
            ]);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$select];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }

    /**
     * getPantalla
     *
     * @param  mixed $codigo
     * @param  mixed $campos
     * @return mixed
     */
    public static function getPantalla(string $codigo, array $campos = ['codigo']): mixed
    {
        $numCampos = sizeof($campos);
        if ($numCampos > 0) {
            $select = implode(",", $campos);
            $datos = Queries::leer('SELECT ' . $select . ' FROM pantalla WHERE codigo=:codigo', [
                'codigo' => [$codigo, PDO::PARAM_INT],
            ]);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$select];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }

    /**
     * getMarca
     *
     * @param  mixed $codigo
     * @param  mixed $campos
     * @return mixed
     */
    public static function getMarca(string $codigo, array $campos = ['codigo']): mixed
    {
        $numCampos = sizeof($campos);
        if ($numCampos > 0) {
            $select = implode(",", $campos);
            $datos = Queries::leer('SELECT ' . $select . ' FROM marca WHERE codigo=:codigo', [
                'codigo' => [$codigo, PDO::PARAM_INT],
            ]);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$select];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }


    /**
     * getLocal
     *
     * @param  mixed $codigo
     * @param  mixed $campos
     * @return mixed
     */
    public static function getLocal(string $codigo, array $campos = ['codigo']): mixed
    {
        $numCampos = sizeof($campos);
        if ($numCampos > 0) {
            $select = implode(",", $campos);
            $datos = Queries::leer('SELECT ' . $select . ' FROM local WHERE codigo=:codigo', [
                'codigo' => [$codigo, PDO::PARAM_INT],
            ]);

            if ($datos['status'] == 'ok' && isset($datos['data'][$campos[0]])) {
                return ($numCampos > 1) ? $datos['data'] : $datos['data'][$select];
            }
        }
        return ($numCampos > 1) ? [] : 0;
    }
}

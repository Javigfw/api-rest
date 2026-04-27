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

}

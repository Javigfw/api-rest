<?php

namespace App\Helpers;

use PDO;
use PDOException;
use App\Helpers\Database;


class Queries
{

    //**************************************************************************
    // ATRIBUTOS                                                               *
    //**************************************************************************
    public static $db_config = [
        'db_host' => DB_HOST,
        'db_name' => DB_NAME,
        'db_user' => DB_USER,
        'db_pass' => DB_PASS
    ];



    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
    }



    /**
     * leer
     *
     * @param  mixed $sql
     * @param  mixed $datos
     * @return array
     */
    public static function leer(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => '',);

            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros            
            // obtenemos y devolvemos parametros que no vienen por get y post            
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Obtenemos los datos y los guardamos en el array $respuesta
            $respuesta['data'] = $query->fetch(PDO::FETCH_ASSOC);
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'error' => Utils::extraerError($e->getMessage())];
        }
    }



    /**
     * listar
     *
     * @param  mixed $sql
     * @param  mixed $datos
     * @return array
     */
    public static function listar(string $sql, array $datos): array
    {
        try {

            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => '');

            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros                        
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Obtenemos los datos y los guardamos en el array $respuesta
            $respuesta['data'] = $query->fetchAll(PDO::FETCH_ASSOC);
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'error' => Utils::extraerError($e->getMessage())];
        }
    }



    /**
     * crear
     *
     * @param  mixed $sql
     * @param  mixed $datos
     * @return array
     */
    public static function crear(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => 'Recurso creado correctamente.');
            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'error' => Utils::extraerError($e->getMessage())];
        }
    }



    /**
     * actualizar
     *
     * @param  mixed $sql
     * @param  mixed $datos
     * @return array
     */
    public static function actualizar(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => 'Recurso actualizado correctamente.');
            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros                        
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'error' => Utils::extraerError($e->getMessage())];
        }
    }


    /**
     * borrar
     *
     * @param  mixed $sql
     * @param  mixed $datos
     * @return array
     */
    public static function borrar(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => 'Recurso borrado correctamente.');
            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros                        
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();
            // Devolvemos los datos
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'error' => Utils::extraerError($e->getMessage())];
        }
    }



    /**
     * call
     *
     * @param  mixed $sql
     * @param  mixed $sqlSelect
     * @param  mixed $datos
     * @return array
     */
    public static function call(string $sql, string $sqlSelect, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => 'Procedimiento ejecutado correctamente');
            // Comprobamos si se le ha pasado un token a la petición. Si se le ha
            // pasado, comporobamos que sea válido y añadimos a la respuesta un
            // token nuevo. Si no es válido, parámos el script.

            // Preparamos el SQL
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros
            foreach ($datos as $clave => $valor) {
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }
            // Ejecutamos la sentencia SQL
            $query->execute();

            // Preparamos el SQL del select
            if ($sqlSelect != '') {
                $query = Database::instance()->prepare($sqlSelect);
                // Devolvemos los datos
                $query->execute();
                // Obtenemos los datos y los guardamos en el array $respuesta
                $respuesta['data'] = $query->fetch(PDO::FETCH_ASSOC);
            }
            // Devolvemos los datos            
            return $respuesta;
        } catch (PDOException $e) {
            return ['status' => 'error', 'error' => Utils::extraerError($e->getMessage())];
        }
    }

    public static function call_multiple(string $sql, array $datos): array
    {
        try {
            // Definimos el array de la respuesta
            $respuesta = array('status' => 'ok', 'data' => '');
            // Comprobamos si se le ha pasado un token a la petición. Si se le ha
            // pasado, comporobamos que sea válido y añadimos a la respuesta un
            // token nuevo. Si no es válido, parámos el script.

            // Preparamos el SQL             
            $query = Database::instance()->prepare($sql);
            // Enlazamos los parámetros
            foreach ($datos as $clave => $valor) {                
                $query->bindValue(':' . $clave, $valor[0], $valor[1]);
            }            
            // Ejecutamos la sentencia SQL
            $query->execute();

            $resultado= $query->fetchAll(PDO::FETCH_ASSOC);
            $maximo = $resultado[0]['cantidad'];                               
            $array = [];
            for($i=0; $i<$maximo; $i++){                        
                $query->nextRowset();
                $resultado = $query->fetchAll(PDO::FETCH_ASSOC);                
                foreach ($resultado as $key => $value) {
                    array_push($array, $value);
                }
            }
            $datos = $array; 
            $respuesta['data']= $datos;
            return $respuesta;
        } catch (PDOException $e) {
            // unset($respuesta['data']);
            $respuesta['status']='error';
            $respuesta['error']=Utils::extraerError($e->getMessage());
            return $respuesta;
        }
    }    
}

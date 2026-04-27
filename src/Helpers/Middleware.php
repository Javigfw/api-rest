<?php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use PDO;
use PDOException;
use Exception;

error_reporting(0);

class Middleware
{

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
}

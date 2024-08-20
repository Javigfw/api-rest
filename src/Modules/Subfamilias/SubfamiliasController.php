<?php

namespace App\Subfamilias;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class SubfamiliasController extends Controller
{

    /**
     * listarSubfamilias
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listarSubfamilias(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        $sql = "SELECT codigo, nombre, familia FROM pl_subfamilia";
        // generamos la respuesta
        $respuesta = Queries::listar($sql, []);

        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            return Utils::responseJsonOk($response, $respuesta['data'], 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }

    /**
     * listarSubfamiliasCodigo
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listarSubfamiliasCodigo(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $codigo = $args['codigo'];

        // generamos la consulta
        $sql = "SELECT codigo, nombre, familia FROM pl_subfamilia WHERE familia = :codigo";
        // generamos la respuesta
        $respuesta = Queries::listar($sql, [
            'codigo' => [$codigo, PDO::PARAM_INT]
        ]);

        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            return Utils::responseJsonOk($response, $respuesta['data'], 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }


    /**
     * insertarSubfamilias carga inicial
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarSubfamilia(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombreFamilia', 'nombreSubfamilia'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // obtenemos el codigo de la familia por el nombre
        $codigoFamilia = Utils::obtenerIdFamiliaPorNombre($params['nombreFamilia']);

        // generamos la consulta
        $sql = "INSERT INTO pl_subfamilia (familia, nombre) VALUES (:familia, :nombre)";

        $respuesta = Queries::crear($sql, [
            'familia' => [$codigoFamilia, PDO::PARAM_INT],
            'nombre' => [$params['nombreSubfamilia'], PDO::PARAM_STR]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Subfamilia insertada', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }



    /**
     * insertarSubfamilia
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarSubfamiliaPlaning(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        // parametros requeridos
        $mensaje = Utils::requiredParams(['codigoFamilia', 'nombreSubfamilia'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO pl_subfamilia (familia, nombre) VALUES (:familia, :nombre)";

        $respuesta = Queries::crear($sql, [
            'familia' => [$params['codigoFamilia'], PDO::PARAM_INT],
            'nombre' => [$params['nombreSubfamilia'], PDO::PARAM_STR]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Subfamilia insertada', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }


    /**
     * updateSubfamilia
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function updateSubfamilia(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre', 'codigo'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "UPDATE pl_subfamilia SET nombre = :nombre WHERE codigo = :codigo";

        $respuesta = Queries::actualizar($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'codigo' => [$params['codigo'], PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Subfamilia actualizada', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }


    /**
     * deleteSubfamilia
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function deleteSubfamilia(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $codigo = $args['codigo'];
        // generamos la consulta
        $sql = "DELETE FROM pl_subfamilia WHERE codigo = :codigo";

        $respuesta = Queries::borrar($sql, [
            'codigo' => [$codigo, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Subfamilia eliminada correctamente', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }
}

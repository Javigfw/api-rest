<?php

namespace App\Familias;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;


class FamiliasController extends Controller
{

    /**
     * listarfamilias
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listar(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        $sql = "SELECT codigo, nombre FROM familia";
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
     * insertarFamilia
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertar(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO familia (nombre) VALUES (:nombre)";

        $respuesta = Queries::crear($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Familia insertada', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }

    /**
     * updateFamiliaPlaning
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function actualizar(Request $request, Response $response, array $args): Response
    {

        // obetnemos el id
        $id = $args['familia'];

        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "UPDATE familia SET nombre = :nombre WHERE codigo = :codigo";

        $respuesta = Queries::actualizar($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'codigo' => [$id, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Familia actualizada', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }


    /**
     * deleteFamilia
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function borrar(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $codigo = $args['familia'];
        // generamos la consulta
        $sql = "DELETE FROM familia WHERE codigo = :codigo";

        $respuesta = Queries::borrar($sql, [
            'codigo' => [$codigo, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Familia eliminada correctamente', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }
}

<?php

namespace App\Marcas;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class MarcasController extends Controller
{


    /**
     * listarMarcas
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listarMarcas(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        $sql = "SELECT codigo, nombre FROM pl_marca";
        // generamos la respuesta
        $respuesta = Queries::listar($sql, []);

        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            return Utils::responseJsonOk($response, $respuesta['data'], 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }


    /**
     * insertarMarca
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarMarca(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO pl_marca (nombre) VALUES (:nombre)";

        $respuesta = Queries::crear($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Marca insertada', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }


    /**
     * updateMarca
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function updateMarca(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre', 'codigo'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "UPDATE pl_marca SET nombre = :nombre WHERE codigo = :codigo";

        $respuesta = Queries::actualizar($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'codigo' => [$params['codigo'], PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Marca actualizada', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    /**
     * borrarMarca
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function borrarMarca(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $codigo = $args['codigo'];

        // generamos la consulta
        $sql = "DELETE FROM pl_marca WHERE codigo = :codigo";

        $respuesta = Queries::borrar($sql, [
            'codigo' => [$codigo, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Marca eliminada correctamente', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }
}

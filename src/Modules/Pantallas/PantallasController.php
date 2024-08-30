<?php

namespace App\Pantallas;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PantallasController extends Controller
{

    /**
     * listarPantallas
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listar(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        $sql = "SELECT codigo, nombre FROM pantalla";
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
     * insertarPantallas
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
        $sql = "INSERT INTO pantalla (nombre) VALUES (:nombre)";

        $respuesta = Queries::crear($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Pantalla insertada', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }


    /**
     * updatePantalla
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function actualizar(Request $request, Response $response, array $args): Response
    {
        // obtenemos el id
        $id = $args['pantalla'];

        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "UPDATE pantalla SET nombre = :nombre WHERE codigo = :codigo";

        $respuesta = Queries::actualizar($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'codigo' => [$id, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Pantalla actualizada', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    
    /**
     * borrarPantalla
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function borrar(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $codigo = $args['pantalla'];

        // generamos la consulta
        $sql = "DELETE FROM pantalla WHERE codigo = :codigo";

        $respuesta = Queries::borrar($sql, [
            'codigo' => [$codigo, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Pantalla eliminada correctamente', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }
}

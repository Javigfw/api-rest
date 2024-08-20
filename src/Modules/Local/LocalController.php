<?php

namespace App\Local;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;


class LocalController extends Controller
{

    /**
     * listarLocales
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listarLocalesAdmin(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        $sql = "SELECT codigo, nombre FROM local";
        // generamos la respuesta
        $respuesta = Queries::listar($sql, []);

        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            return Utils::responseJsonOk($response, $respuesta['data'], 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }

    // listar locales para usuario expecifico
    public function listarLocalesUsuario(Request $request, Response $response, array $args): Response
    {

        // obtenemos el codigo
        $usuario = $args['usuario'];

        // obtenemos el id del usuario
        $idUsuario = Utils::obtenerIdUsuario($usuario);

        // generamos la consulta 
        $sql = "SELECT l.nombre, a.local FROM local l JOIN acceso a ON l.codigo = a.local where a.usuario = :idUsuario";

        // lanzamos la consulta
        $respuesta = Queries::listar($sql, [
            'idUsuario' => [$idUsuario, PDO::PARAM_INT],
        ]);
        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            return Utils::responseJsonOk($response, $respuesta['data'], 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }
}

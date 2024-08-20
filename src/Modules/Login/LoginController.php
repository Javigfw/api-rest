<?php

namespace App\Login;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;


class LoginController extends Controller
{

    /**
     * login
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function login(Request $request, Response $response, array $args): Response
    {
        // requerimos los parametros
        $params = $request->getParsedBody();

        // parametros
        $mensaje = Utils::requiredParams(['usuario', 'pass'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // codificamos la contraseña
        $password = base64_encode($params['pass']);

        // generamos la consulta
        $sql = "SELECT count(usuario) as 'existe', es_admin, usuario FROM usuario WHERE usuario = :usuario and contraseña = :pass ";

        // obtenemos la respuesta
        $respuesta = Queries::leer($sql, [
            'usuario' => [$params['usuario'], PDO::PARAM_STR],
            'pass' => [$password, PDO::PARAM_STR]
        ]);

        // comprobamos el resultado
        if ($respuesta['status'] == 'ok') {
            // comprobamos si existe el usuario
            if ($respuesta['data']['existe'] == 1) {
                // codificamos la respuesta
                $datos = [
                    'usuario' => $respuesta['data']['usuario'],
                    'es_admin' => $respuesta['data']['es_admin']
                ];
                return Utils::responseJsonOk($response, $datos);
            } else {
                return Utils::responseJsonError($response, 'El usuario o contraseña son incorrectos');
            }
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }
}

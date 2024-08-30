<?php

namespace App\Usuarios;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Helpers\Peticiones;

class UsuariosController extends Controller
{


    /**
     * listar
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listar(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        $sql = "SELECT codigo, nombre, email, es_admin FROM usuario";
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
     * insertar
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
        $mensaje = Utils::requiredParams(['nombre', 'email', 'pass'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO usuario (nombre, email, contrasena, es_admin) VALUES (:nombre, :email, :pass, 0)";

        $respuesta = Queries::crear($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'email' => [$params['email'], PDO::PARAM_STR],
            'pass' => [base64_encode($params['pass']), PDO::PARAM_STR],
        ]);


        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Usuario insertado', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    /**
     * actualizar
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function actualizar(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $id = $args['usuario'];

        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre', 'email'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "UPDATE usuario SET nombre = :nombre, email = :email WHERE codigo = :codigo";

        $respuesta = Queries::actualizar($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'email' => [$params['email'], PDO::PARAM_STR],
            'codigo' => [$id, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Usuario actualizado', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    /**
     * borrar
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function borrar(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $id = $args['usuario'];

        // generamos la consulta
        $sql = "DELETE FROM usuario WHERE codigo = :codigo";

        $respuesta = Queries::borrar($sql, [
            'codigo' => [$id, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Usuario eliminado correctamente', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }
    
    /**
     * listarLocalesUsuario
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listarLocalesUsuario(Request $request, Response $response, array $args): Response
    {

        // obtengo el local
        $usuario = $args['usuario'];

        // generamos la consulta
        $sql = "SELECT u.codigo, u.nombre, IF(ISNULL(a.usuario), 0 , 1) acceso
                FROM local u
                LEFT OUTER JOIN acceso a ON u.codigo = a.local AND a.usuario = :usuario
                ORDER BY 3 desc, 2 asc
                ";

        // lanzamos la consulta
        $respuesta = Queries::listar($sql, [
            'usuario' => [$usuario, PDO::PARAM_INT]
        ]);
        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            return Utils::responseJsonOk($response, $respuesta['data'], 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }


    // fin clase
}

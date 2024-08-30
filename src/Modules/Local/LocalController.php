<?php

namespace App\Local;

use App\Helpers\Controller;
use App\Helpers\Peticiones;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use PDOException;


class LocalController extends Controller
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

        // obtenemos el id del usuario        
        $idUsuario = $args['usuario'];

        // a traves del Middleware podemos saber si el usuario existe o no
        $datosUsuario = Peticiones::getUsuario($idUsuario, ['es_admin']);

        // parametros consulta
        $parametrosSql = [];

        // comprobamos si es adminstrador
        if ($datosUsuario == '1') {
            // generamos la consulta
            $sql = "SELECT codigo, nombre FROM local where activo = 1";
        } else {
            // generamos la consulta 
            $sql = "SELECT l.nombre as nombre, a.local as codigo FROM local l JOIN acceso a ON l.codigo = a.local where a.usuario = :idUsuario and l.activo = 1";
            $parametrosSql = [
                'idUsuario' => [$idUsuario, PDO::PARAM_INT],
            ];
        }


        // lanzamos la consulta
        $respuesta = Queries::listar($sql, $parametrosSql);
        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            return Utils::responseJsonOk($response, $respuesta['data'], 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }
    // fin clase

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
        $mensaje = Utils::requiredParams(['nombre'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO local (nombre, activo) VALUES (:nombre, 1)";

        $respuesta = Queries::crear($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR]
        ]);

        if ($respuesta['status'] == 'ok') {

            // cargamos el script perteneciente al local, para su base de datos
            try {
                // Conexión a la base de datos
                $conexion = new PDO('mysql:host=' . DB_HOST . '; dbname=' . DB_NAME, DB_USER, DB_PASS);
                $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Crear tablas para el local 10
                Peticiones::crearTablasLocal($conexion);

                // excepciones
            } catch (PDOException $e) {
                return Utils::responseJsonError($response, 'Error de conexión: ' . $e->getMessage());
            }

            return Utils::responseJsonOk($response, 'Local insertado', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor. Local insertar API');
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
        $id = $args['local'];

        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "UPDATE local SET nombre = :nombre WHERE codigo = :codigo";

        $respuesta = Queries::actualizar($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'codigo' => [$id, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Local actualizado', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    /**
     * desactivar
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function desactivar(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $id = $args['local'];

        $params = $request->getParsedBody();

        // generamos la consulta
        $sql = "UPDATE local SET activo = 0 WHERE codigo = :codigo";

        $respuesta = Queries::actualizar($sql, [
            'codigo' => [$id, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Local desctivado', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    /**
     * desactivarAcceso
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function desactivarAcceso(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $local = $args['local'];
        $usuario = $args['usuario'];

        // generamos la consulta
        $sql = "DELETE FROM acceso WHERE local = :local AND usuario = :usuario";

        // hacmeos la consulta
        $respuesta = Queries::actualizar($sql, [
            'local' => [$local, PDO::PARAM_INT],
            'usuario' => [$usuario, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Acesso desactivado', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    /**
     * activarAcceso
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function activarAcceso(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        // parametros requeridos
        $mensaje = Utils::requiredParams(['local', 'usuario'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO acceso (local, usuario) VALUES (:local, :usuario)";

        $respuesta = Queries::crear($sql, [
            'local' => [$params['local'], PDO::PARAM_INT],
            'usuario' => [$params['usuario'], PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Acesso activado', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor. Local insertar API');
        }
    }

    /**
     * listarUsuariosLocal
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listarUsuariosLocal(Request $request, Response $response, array $args): Response
    {

        // obtengo el local
        $local = $args['local'];

        // generamos la consulta
        $sql = "SELECT u.codigo, u.nombre, u.email, IF(ISNULL(a.local), 0 , 1) acceso
                FROM usuario u
                LEFT OUTER JOIN acceso a ON u.codigo = a.usuario AND a.local = :local
                ORDER BY 4 desc, 3 asc
                ";

        // lanzamos la consulta
        $respuesta = Queries::listar($sql, [
            'local' => [$local, PDO::PARAM_INT]
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

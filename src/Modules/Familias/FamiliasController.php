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
    public function listarfamilias(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        $sql = "SELECT codigo, nombre FROM pl_familia";
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
     * listarfamiliasSubfamilias
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listarFamiliasSubfamilia(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        // $sql = "SELECT 1 tipo, f.codigo famcodigo, f.nombre famnombre, null sfamcodigo, null sfamnombre
        //         FROM pl_familia f
        //         UNION ALL
        //         SELECT 2 tipo, null famcodigo, null famnombre, s.codigo, s.nombre
        //         FROM pl_familia f
        //         JOIN pl_subfamilia s on f.codigo=s.familia
        //         order by 2 asc, 1 asc, 4 asc";
        $sql = "SELECT 1 tipo, f.codigo famcodigo, f.nombre famnombre, null sfamcodigo, null sfamnombre
                FROM pl_familia f
                UNION ALL
                SELECT 2 tipo, f.codigo famcodigo, f.nombre famnombre, s.codigo, s.nombre
                FROM pl_familia f
                JOIN pl_subfamilia s on f.codigo=s.familia
                order by 2 asc, 1 asc, 4 asc";
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
     * insertarFamilias iniciales
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarFamilia(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO pl_familia (nombre) VALUES (:nombre)";

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
     * insertarFamilia
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarFamiliaPlaning(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO pl_familia (nombre) VALUES (:nombre)";

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
    public function updateFamilia(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre', 'codigo'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "UPDATE pl_familia SET nombre = :nombre WHERE codigo = :codigo";

        $respuesta = Queries::actualizar($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'codigo' => [$params['codigo'], PDO::PARAM_INT]
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
    public function deleteFamilia(Request $request, Response $response, array $args): Response
    {
        // obtenemos el codigo
        $codigo = $args['codigo'];
        // generamos la consulta
        $sql = "DELETE FROM pl_familia WHERE codigo = :codigo";

        $respuesta = Queries::borrar($sql, [
            'codigo' => [$codigo, PDO::PARAM_INT]
        ]);

        echo json_encode($respuesta);
        exit();

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Familia eliminada correctamente', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }
}

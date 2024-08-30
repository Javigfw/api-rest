<?php

namespace App\CargaInicial;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use App\Helpers\Peticiones;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use Exception;


class CargaInicialController extends Controller
{

    /**
     * insertarArticulos config inicial
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarArticulo(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams([
            'nombreFamilia',
            'nombreSubfamilia',
            'nombreMarca',
            'nombreIvaCompra',
            'nombreIvaVenta',
            'nombrePantalla',
            'descripcion',
            'codigoBarras',
            'compra',
            'venta',
            'precio',
            'imagen'
        ], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // obtenemos el id de la familia
        $idFamilia = Peticiones::obtenerIdFamiliaPorNombre($params['nombreFamilia']);

        // obtenemos el id de la subfamilia
        $idSubfamilia = Peticiones::obtenerIdSubfamiliaPorNombre($params['nombreSubfamilia']);

        // obtenemos el id de la marca
        $idMarca = Peticiones::obtenerIdMarcaPorNombre($params['nombreMarca']);

        // obtenemos el iva de compra y venta
        $idIvaCompra = Peticiones::obtenerIdIvaPorNombre($params['nombreIvaCompra']);
        $idIvaVenta = Peticiones::obtenerIdIvaPorNombre($params['nombreIvaVenta']);

        // obtenemos el nombre de pantalla
        $idPantalla = Peticiones::obtenerIdPantallaPorNombre($params['nombrePantalla']);

        // generamos la consulta
        $sql = "INSERT INTO articulo (familia, subfamilia, pantalla, descripcion, codigoBarras, compra, venta, marca,
        ivaCompra, ivaVenta, precio) VALUES (:familia, :subfamilia, :pantalla, :descripcion, :codigoBarras, :compra,
        :venta, :marca, :ivaCompra, :ivaVenta, :precio)";

        $respuesta = Queries::crear($sql, [
            'familia' => [$idFamilia, PDO::PARAM_INT],
            'subfamilia' => [$idSubfamilia, PDO::PARAM_INT],
            'pantalla' => [$idPantalla, PDO::PARAM_INT],
            'descripcion' => [$params['descripcion'], PDO::PARAM_STR],
            'codigoBarras' => [$params['codigoBarras'], PDO::PARAM_STR],
            'compra' => [$params['compra'], PDO::PARAM_INT],
            'venta' => [$params['venta'], PDO::PARAM_INT],
            'marca' => [$idMarca, PDO::PARAM_INT],
            'ivaCompra' => [$idIvaCompra, PDO::PARAM_INT],
            'ivaVenta' => [$idIvaVenta, PDO::PARAM_INT],
            'precio' => [$params['precio'], PDO::PARAM_STR]
        ]);

        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            $maxCodigo = Peticiones::obtenerUltimoAutoIncrement();
            // guardamos la imagen en el servidor
            $base64Image = $params['imagen'];
            $newName = $maxCodigo;
            $ruta = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/p/';

            // guardamos la imagen
            $procesado = Utils::procesarImagen($base64Image, $newName, $ruta);

            return Utils::responseJsonOk($response, 'Articulo insertado. ' . $procesado['msg'], 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
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
        $codigoFamilia = Peticiones::obtenerIdFamiliaPorNombre($params['nombreFamilia']);

        // generamos la consulta
        $sql = "INSERT INTO subfamilia (familia, nombre) VALUES (:familia, :nombre)";

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
     * insertarPantallas
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarPantallas(Request $request, Response $response, array $args): Response
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
        $sql = "INSERT INTO marca (nombre) VALUES (:nombre)";

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
     * insertarIva
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarIva(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        // parametros requeridos
        $mensaje = Utils::requiredParams(['nombre', 'iva'], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        // generamos la consulta
        $sql = "INSERT INTO iva (nombre, iva) VALUES (:nombre, :iva)";

        $respuesta = Queries::crear($sql, [
            'nombre' => [$params['nombre'], PDO::PARAM_STR],
            'iva' => [$params['iva'], PDO::PARAM_STR] // es double pero hay que ponerlo como String
        ]);

        if ($respuesta['status'] == 'ok') {

            return Utils::responseJsonOk($response, 'Iva insertado', 200);
        } else {
            return Utils::responseJsonError($response, 'Error de conexión con el servidor');
        }
    }
}

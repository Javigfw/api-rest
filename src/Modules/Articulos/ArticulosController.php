<?php

namespace App\Articulos;

use App\Helpers\Controller;
use App\Helpers\Queries;
use App\Helpers\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use Exception;


class ArticulosController extends Controller
{

    /**
     * listarArticulos
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function listarArticulos(Request $request, Response $response, array $args): Response
    {





        // generamos la consulta
        $sql = "SELECT a.codigo, f.nombre as familia, s.nombre as subfamilia, p.nombre as pantalla, m.nombre as marca,
         i.nombre as ivaCompra, iv.nombre as ivaVenta, a.descripcion,
        a.codigoBarras, a.compra, a.venta, a.precio
        FROM pl_articulo a
        JOIN pl_familia f ON f.codigo = a.familia
        LEFT OUTER JOIN pl_subfamilia s ON s.codigo = a.subfamilia
        LEFT OUTER JOIN pl_pantalla p ON p.codigo = a.pantalla
        LEFT OUTER JOIN pl_marca m ON m.codigo = a.marca
        JOIN pl_iva i ON i.codigo = a.ivaCompra
        JOIN pl_iva iv ON iv.codigo = a.ivaVenta
        ORDER BY 1 ";
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
        $idFamilia = Utils::obtenerIdFamiliaPorNombre($params['nombreFamilia']);

        // obtenemos el id de la subfamilia
        $idSubfamilia = Utils::obtenerIdSubfamiliaPorNombre($params['nombreSubfamilia']);

        // obtenemos el id de la marca
        $idMarca = Utils::obtenerIdMarcaPorNombre($params['nombreMarca']);

        // obtenemos el iva de compra y venta
        $idIvaCompra = Utils::obtenerIdIvaPorNombre($params['nombreIvaCompra']);
        $idIvaVenta = Utils::obtenerIdIvaPorNombre($params['nombreIvaVenta']);

        // obtenemos el nombre de pantalla
        $idPantalla = Utils::obtenerIdPantallaPorNombre($params['nombrePantalla']);

        // generamos la consulta
        $sql = "INSERT INTO pl_articulo (familia, subfamilia, pantalla, descripcion, codigoBarras, compra, venta, marca,
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
            $maxCodigo = Utils::obtenerUltimoAutoIncrement();
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
     * insertarArticuloPlantilla
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function insertarArticuloPlantilla(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // parametros requeridos
        $mensaje = Utils::requiredParams([
            'idFamilia',
            'idIvaCompra',
            'idIvaVenta',
            'descripcion',
            'codigoBarras',
            'compra',
            'venta',
            'precio'
        ], $params);

        // si no tenemos parametros mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        $valoresSQL = [
            'familia' => [$params['idFamilia'], PDO::PARAM_INT],
            'descripcion' => [$params['descripcion'], PDO::PARAM_STR],
            'codigoBarras' => [$params['codigoBarras'], PDO::PARAM_STR],
            'compra' => [$params['compra'], PDO::PARAM_INT],
            'venta' => [$params['venta'], PDO::PARAM_INT],
            'ivaCompra' => [$params['idIvaCompra'], PDO::PARAM_INT],
            'ivaVenta' => [$params['idIvaVenta'], PDO::PARAM_INT],
            'precio' => [$params['precio'], PDO::PARAM_STR]
        ];
        // generamos la consulta
        $sql1 = "INSERT INTO pl_articulo (familia, descripcion, codigoBarras, compra, venta, ivaCompra, ivaVenta, precio";
        $sql2 = "VALUES(:familia, :descripcion, :codigoBarras, :compra, :venta, :ivaCompra, :ivaVenta, :precio";
        if (isset($params['idSubfamilia'])) {
            $sql1 .= ', subfamilia';
            $sql2 .= ', :subfamilia';
            $valoresSQL['subfamilia'] = [$params['idSubfamilia'], PDO::PARAM_INT];
        }

        if (isset($params['idMarca'])) {
            $sql1 .= ', marca';
            $sql2 .= ', :marca';
            $valoresSQL['marca'] = [$params['idMarca'], PDO::PARAM_INT];
        }

        if (isset($params['idPantalla'])) {
            $sql1 .= ', pantalla';
            $sql2 .= ', :pantalla';
            $valoresSQL['pantalla'] = [$params['idPantalla'], PDO::PARAM_INT];
        }


        $sql1 = $sql1 . ')' . $sql2 . ')';

        $respuesta = Queries::crear($sql1, $valoresSQL);

        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {

            // guardamos la imagen en el servidor
            Utils::processAndSaveImages($params, API_BASE_PATH, IMAGE_SIZE_SMALL, IMAGE_SIZE_BIG);

            return Utils::responseJsonOk($response, 'Articulo insertado. ' /*. $procesado['msg']*/, 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    /**
     * actualizarArticuloPlantilla
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function actualizarArticuloPlantilla(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // Parámetros requeridos
        $mensaje = Utils::requiredParams([
            'idArticulo',
            'idFamilia',
            'idIvaCompra',
            'idIvaVenta',
            'descripcion',
            'codigoBarras',
            'compra',
            'venta',
            'precio'
        ], $params);

        // Si no tenemos parámetros, mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        $valoresSQL = [
            'familia' => [$params['idFamilia'], PDO::PARAM_INT],
            'descripcion' => [$params['descripcion'], PDO::PARAM_STR],
            'codigoBarras' => [$params['codigoBarras'], PDO::PARAM_STR],
            'compra' => [$params['compra'], PDO::PARAM_INT],
            'venta' => [$params['venta'], PDO::PARAM_INT],
            'ivaCompra' => [$params['idIvaCompra'], PDO::PARAM_INT],
            'ivaVenta' => [$params['idIvaVenta'], PDO::PARAM_INT],
            'precio' => [$params['precio'], PDO::PARAM_STR],
            'idArticulo' => [$params['idArticulo'], PDO::PARAM_INT],  // Identificador del artículo
        ];

        // Generamos la consulta de actualización
        $sql = "UPDATE pl_articulo SET
        familia = :familia,
        descripcion = :descripcion,
        codigoBarras = :codigoBarras,
        compra = :compra,
        venta = :venta,
        ivaCompra = :ivaCompra,
        ivaVenta = :ivaVenta,
        precio = :precio";

        // Campos opcionales
        if (isset($params['idSubfamilia'])) {
            $sql .= ', subfamilia = :subfamilia';
            $valoresSQL['subfamilia'] = [$params['idSubfamilia'], PDO::PARAM_INT];
        } else {
            $sql .= ', subfamilia = null';
        }

        if (isset($params['idMarca'])) {
            $sql .= ', marca = :marca';
            $valoresSQL['marca'] = [$params['idMarca'], PDO::PARAM_INT];
        } else {
            $sql .= ', marca = null';
        }

        if (isset($params['idPantalla'])) {
            $sql .= ', pantalla = :pantalla';
            $valoresSQL['pantalla'] = [$params['idPantalla'], PDO::PARAM_INT];
        } else {
            $sql .= ', pantalla = null';
        }

        // Añadimos la condición para identificar qué artículo actualizar
        $sql .= " WHERE codigo = :idArticulo";

        $respuesta = Queries::actualizar($sql, $valoresSQL);

        // Si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            // Verificamos si existen imágenes antes de intentar guardarlas
            if (!empty($params['imagenP']) || !empty($params['imagenG'])) {
                Utils::processAndSaveImages($params, API_BASE_PATH, IMAGE_SIZE_SMALL, IMAGE_SIZE_BIG, true, $params['idArticulo']);
            }
            return Utils::responseJsonOk($response, 'Artículo actualizado', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }



    /**
     * deleteArticulo
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function deleteArticulo(Request $request, Response $response, array $args)
    {
        // obtenemos el codigo
        $codigo = $args['codigo'];
        // generamos la consulta
        $sql = "DELETE FROM pl_articulo WHERE codigo = :codigo";

        $respuesta = Queries::borrar($sql, [
            'codigo' => [$codigo, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {
            try {
                if (Utils::borrarImagenServidor($codigo, API_BASE_PATH)) {
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }

            return Utils::responseJsonOk($response, 'Articulo eliminado correctamente', 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }

    /**
     * obtenerImagenArticuloPlantilla
     *
     * @param  mixed $request
     * @param  mixed $response
     * @param  mixed $args
     * @return Response
     */
    public function obtenerImagenArticuloPlantilla(Request $request, Response $response, array $args): Response
    {
        $tipo = intval(Utils::existeVariable($args['tipo'], 0));
        $dato = Utils::obtenerImagenArticulo($args['codigo'], $tipo);
        return Utils::responseJsonOk($response, $dato, 200);
    }

    // fin clase
}

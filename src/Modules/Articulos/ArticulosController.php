<?php

namespace App\Articulos;

use App\Helpers\Controller;
use App\Helpers\Peticiones;
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
    public function listar(Request $request, Response $response, array $args): Response
    {
        // generamos la consulta
        $sql = "SELECT a.codigo, f.nombre as familia, s.nombre as subfamilia, p.nombre as pantalla, m.nombre as marca,
         i.nombre as ivaCompra, iv.nombre as ivaVenta, a.descripcion,
        a.codigoBarras, a.compra, a.venta, a.precio
        FROM articulo a
        JOIN familia f ON f.codigo = a.familia
        LEFT OUTER JOIN subfamilia s ON s.codigo = a.subfamilia
        LEFT OUTER JOIN pantalla p ON p.codigo = a.pantalla
        LEFT OUTER JOIN marca m ON m.codigo = a.marca
        JOIN iva i ON i.codigo = a.ivaCompra
        JOIN iva iv ON iv.codigo = a.ivaVenta
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
     * insertarArticuloPlantilla
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
        $sql1 = "INSERT INTO articulo (familia, descripcion, codigoBarras, compra, venta, ivaCompra, ivaVenta, precio";
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
            // obtengo id generado del articulo
            $id = Peticiones::obtenerUltimoAutoIncrement();
            // guardamos la imagen en el servidor
            if (!empty($params['imagenP']) && !empty($params['mimeTypeP'])) {
                Utils::processAndSaveImages($params['imagenP'], $params['mimeTypeP'], $_SERVER[''] . $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/p/', $id, IMAGE_SIZE_SMALL);
            }
            if (!empty($params['imagenG']) && !empty($params['mimeTypeG'])) {
                Utils::processAndSaveImages($params['imagenG'], $params['mimeTypeG'], $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/g/', $id, IMAGE_SIZE_BIG);
            }

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
    public function actualizar(Request $request, Response $response, array $args): Response
    {

        // obtenemos el id
        $id = $args['articulo'];

        $params = $request->getParsedBody();

        // Parámetros requeridos
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

        // Si no tenemos parámetros, mostramos el mensaje
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        $valoresSQL = [
            'articulo' => [$id, PDO::PARAM_INT],
            'familia' => [$params['idFamilia'], PDO::PARAM_INT],
            'descripcion' => [$params['descripcion'], PDO::PARAM_STR],
            'codigoBarras' => [$params['codigoBarras'], PDO::PARAM_STR],
            'compra' => [$params['compra'], PDO::PARAM_INT],
            'venta' => [$params['venta'], PDO::PARAM_INT],
            'ivaCompra' => [$params['idIvaCompra'], PDO::PARAM_INT],
            'ivaVenta' => [$params['idIvaVenta'], PDO::PARAM_INT],
            'precio' => [$params['precio'], PDO::PARAM_STR],
        ];

        // Generamos la consulta de actualización
        $sql = "UPDATE articulo SET
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
        $sql .= " WHERE codigo = :articulo";

        $respuesta = Queries::actualizar($sql, $valoresSQL);

        // Si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            // Verificamos si existen imágenes antes de intentar guardarlas
            if (!isset($params['imagenP']) || !isset($params['mimeTypeP'])) {
                // borramos la imagen
                // borramos la imagen
                $filePathP = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/p/' . $params['idArticulo'] . '.jpg';
                if (file_exists($filePathP)) {
                    unlink($filePathP);
                }
            } elseif (!empty($params['imagenP']) || !empty($params['mimeTypeP'])) {
                Utils::processAndSaveImages($params['imagenP'], $params['mimeTypeP'], $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/p/', $params['idArticulo'], IMAGE_SIZE_SMALL);
            }

            if (!isset($params['imagenG']) || !isset($params['mimeTypeG'])) {
                // borramos la imagen
                $filePathG = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/g/' . $params['idArticulo'] . '.jpg';
                if (file_exists($filePathG)) {
                    unlink($filePathG);
                }
            } elseif (!empty($params['imagenG']) || !empty($params['mimeTypeG'])) {
                Utils::processAndSaveImages($params['imagenG'], $params['mimeTypeG'], $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/g/', $params['idArticulo'], IMAGE_SIZE_BIG);
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
    public function borrar(Request $request, Response $response, array $args)
    {
        // obtenemos el codigo
        $codigo = $args['articulo'];
        // generamos la consulta
        $sql = "DELETE FROM articulo WHERE codigo = :codigo";

        $respuesta = Queries::borrar($sql, [
            'codigo' => [$codigo, PDO::PARAM_INT]
        ]);

        if ($respuesta['status'] == 'ok') {
            $filePathP = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/p/' . $codigo . '.jpg';
            if (file_exists($filePathP)) {
                unlink($filePathP);
            }
            $filePathG = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/g/' . $codigo . '.jpg';
            if (file_exists($filePathG)) {
                unlink($filePathG);
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
    public function leer(Request $request, Response $response, array $args): Response
    {
        // obtenido a traves del Middleware
        $idArticulo = $request->getAttribute('paramArticuloId');
        
        $tipo = intval(Utils::existeVariable($args['tipo'], 0));
        $dato = Peticiones::obtenerImagenArticulo($args['articulo'], $tipo);
        return Utils::responseJsonOk($response, $dato, 200);
    }

    // fin clase
}

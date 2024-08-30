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


class ArticuloslocalController extends Controller
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

        // obtenemos el id del local a traves de los argumentos
        $idLocal = $args['local'];

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
        WHERE a.local = :idLocal
        ORDER BY 1 ";
        // generamos la respuesta
        $respuesta = Queries::listar($sql, [
            'idLocal' => [$idLocal, PDO::PARAM_INT]
        ]);

        // si la respuesta es correcta
        if ($respuesta['status'] == 'ok') {
            return Utils::responseJsonOk($response, $respuesta['data'], 200);
        } else {
            return Utils::responseJsonError($response, $respuesta['error']);
        }
    }


    // fin clase
}

<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\ResenaModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResenaController extends Controller
{
    protected ResenaModel $resenaModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->resenaModel = new ResenaModel();
    }

    /**
     * Obtener reseñas aleatorias
     * GET /resenas/aleatorias
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function aleatorias(Request $request, Response $response, array $args): Response
    {
        // 1. Obtener reseñas del modelo
        $result = $this->resenaModel->findRandomWithUsuario(6);

        if ($result['status'] !== 'ok') {
            return Utils::responseJsonError($response, 'Error al obtener las reseñas', 500);
        }

        $reviews = $result['data'];

        // 2. Formatear datos (lógica traída del código legacy)
        $formattedReviews = array_map(function ($review) {
            // Crear iniciales del nombre
            $nameParts = explode(' ', $review['nombre']);
            $initials = '';
            foreach ($nameParts as $part) {
                if (!empty($part)) {
                    $initials .= strtoupper(mb_substr($part, 0, 1));
                }
            }
            // Limitar a 2 iniciales
            $initials = mb_substr($initials, 0, 2);

            return [
                'id' => (int) $review['idReseña'], // Model usa idReseña
                'nombre' => $review['nombre'],
                'username' => $review['username'],
                'initials' => $initials,
                'valoracion' => (int) $review['valoracion'],
                'texto' => $review['textoOpinion']
            ];
        }, $reviews);

        // 3. Retornar respuesta
        // El formato pedido es { success: true, reviews: [...] }
        // Utils::responseJsonOk devuelve { status: 'ok', message: '', data: ... }
        // Adaptamos para cumplir estrictamente el formato solicitado o usamos el estándar.
        // Usaremos el estándar del framework actual pero poniendo la data dentro.

        return Utils::responseJsonOk($response, $formattedReviews);
    }

    /**
     * Crear una nueva reseña
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function crear(Request $request, Response $response): Response
    {
        // 1. Obtener datos del usuario desde el JWT (AuthMiddleware ya lo validó)
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }

        $usuarioId = (int) $jwt->data->id;

        // 2. Obtener y validar datos de la solicitud
        $input = $request->getParsedBody();
        $valoracion = $input['valoracion'] ?? null;
        $textoOpinion = $input['textoOpinion'] ?? null;

        if ($valoracion === null || !is_numeric($valoracion) || $valoracion < 1 || $valoracion > 5) {
            return Utils::responseJsonError($response, 'La valoración debe ser un número entre 1 y 5', '', 400);
        }

        if ($textoOpinion && mb_strlen($textoOpinion) > 100) {
            return Utils::responseJsonError($response, 'El texto de opinión no puede superar los 100 caracteres', '', 400);
        }

        // 3. Crear reseña en el modelo
        $data = [
            'idUsuario' => $usuarioId,
            'valoracion' => (int) $valoracion,
            'textoOpinion' => $textoOpinion ? trim($textoOpinion) : null
        ];

        $result = $this->resenaModel->create($data);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Reseña creada correctamente',
                'idReseña' => $result['data']
            ], 201);
        }

        return Utils::responseJsonError($response, 'Error al guardar la reseña: ' . ($result['error'] ?? ''));
    }
}

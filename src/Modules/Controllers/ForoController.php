<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\MensajeForoModel;
use App\Modules\Models\SuscripcionModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador del Foro
 */
class ForoController extends Controller
{
    protected MensajeForoModel $foroModel;
    protected SuscripcionModel $suscripcionModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->foroModel = new MensajeForoModel();
        $this->suscripcionModel = new SuscripcionModel();
    }

    /**
     * Obtener los últimos mensajes del foro
     * Soporta query params: filter (all|24h), search (texto)
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getMensajes(Request $request, Response $response, array $args): Response
    {
        // 1. Obtener datos del usuario desde el JWT (AuthMiddleware)
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }

        $usuarioId = (int)$jwt->data->id;

        // 2. Obtener query params
        $queryParams = $request->getQueryParams();
        $filter = $queryParams['filter'] ?? 'all';
        $search = $queryParams['search'] ?? '';

        // 3. Obtener mensajes del modelo
        $result = $this->foroModel->getMensajesForo($usuarioId, $filter, $search);

        if ($result['status'] !== 'ok') {
            return Utils::responseJsonError($response, 'Error al obtener mensajes del foro');
        }

        // 4. Mapear respuesta al formato esperado por el frontend
        $messages = array_map(function($msg) use ($usuarioId) {
            return [
                'id' => $msg['idMensaje'],
                'username' => $msg['username'],
                'nombreCompleto' => $msg['nombre'] ?? $msg['username'],
                'tiempo' => $msg['fechaCreacion'],
                'mensaje' => $msg['contenido'],
                'url_imagen' => $msg['url_imagen'] ?? null,
                'esMio' => (int)$msg['idUsuario'] === $usuarioId
            ];
        }, $result['data'] ?? []);

        // 5. Verificar suscripción activa (opcional, para compatibilidad)
        $isSuscrito = $this->suscripcionModel->usuarioTieneSuscripcionActiva($usuarioId);

        // 6. Retornar respuesta con 'mensajes' (plural en español para el cliente)
        return Utils::responseJsonOk($response, [
            'success' => true,
            'mensajes' => $messages,
            'isSuscrito' => $isSuscrito
        ]);
    }

    /**
     * Obtener los mensajes de un usuario específico
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getMensajesUsuario(Request $request, Response $response, array $args): Response
    {
        $usuarioId = (int)$args['id'];

        // Obtener mensajes del modelo
        $result = $this->foroModel->findByUsuario($usuarioId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'mensajes' => $result['data']
            ]);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al obtener mensajes del usuario');
    }

    /**
     * Crear un nuevo mensaje en el foro
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function createMensaje(Request $request, Response $response, array $args): Response
    {
        // 1. Obtener datos del usuario desde el JWT (AuthMiddleware)
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return Utils::responseJsonError($response, 'Usuario no autenticado', '', 401);
        }

        $usuarioId = (int)$jwt->data->id;

        // 2. Obtener contenido del mensaje
        $body = $request->getParsedBody();
        $contenido = $body['mensaje'] ?? $body['contenido'] ?? '';

        if (empty($contenido)) {
            return Utils::responseJsonError($response, 'El contenido del mensaje es requerido', '', 400);
        }

        // 3. Crear mensaje en el modelo
        $result = $this->foroModel->createMensaje($usuarioId, $contenido);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Mensaje publicado correctamente',
                'id' => $result['data']
            ], 201);
        }

        return Utils::responseJsonError($response, 'Error al publicar el mensaje: ' . ($result['error'] ?? ''));
    }

    /**
     * Eliminar un mensaje del foro (Admin)
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function eliminarMensaje(Request $request, Response $response, array $args): Response
    {
        // El ID viene como MSG123, extraer el número
        $idParam = $args['id'] ?? '';
        
        // Si viene con prefijo MSG, quitarlo
        if (str_starts_with($idParam, 'MSG')) {
            $id = (int)substr($idParam, 3);
        } else {
            $id = (int)$idParam;
        }

        if ($id <= 0) {
            return Utils::responseJsonError($response, 'ID de mensaje inválido', '', 400);
        }

        // Eliminar mensaje
        $result = $this->foroModel->deleteById($id);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Mensaje eliminado correctamente'
            ]);
        }

        return Utils::responseJsonError($response, 'Error al eliminar el mensaje: ' . ($result['error'] ?? ''));
    }
}

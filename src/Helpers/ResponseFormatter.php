<?php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Clase para formatear respuestas JSON de forma estandarizada
 */
class ResponseFormatter
{
    /**
     * Crear una respuesta exitosa
     *
     * @param Response $response Objeto Response de PSR-7
     * @param mixed $data Datos a devolver
     * @param string $message Mensaje opcional
     * @param int $statusCode Código de estado HTTP (por defecto 200)
     * @return Response
     */
    public static function success(Response $response, $data = null, string $message = '', int $statusCode = 200): Response
    {
        $payload = [
            'status' => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if ($message) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return self::jsonResponse($response, $payload, $statusCode);
    }

    /**
     * Crear una respuesta de error
     *
     * @param Response $response Objeto Response de PSR-7
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP (por defecto 400)
     * @param array $errors Errores detallados opcionales
     * @return Response
     */
    public static function error(Response $response, string $message, int $statusCode = 400, array $errors = []): Response
    {
        $payload = [
            'status' => 'error',
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $message,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return self::jsonResponse($response, $payload, $statusCode);
    }

    /**
     * Crear una respuesta paginada
     *
     * @param Response $response Objeto Response de PSR-7
     * @param array $data Datos a devolver
     * @param int $total Total de registros
     * @param int $page Página actual
     * @param int $perPage Registros por página
     * @return Response
     */
    public static function paginated(Response $response, array $data, int $total, int $page = 1, int $perPage = 10): Response
    {
        $totalPages = ceil($total / $perPage);

        $payload = [
            'status' => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ];

        return self::jsonResponse($response, $payload, 200);
    }

    /**
     * Crear una respuesta JSON
     *
     * @param Response $response Objeto Response de PSR-7
     * @param array $payload Datos a devolver
     * @param int $statusCode Código de estado HTTP
     * @return Response
     */
    private static function jsonResponse(Response $response, array $payload, int $statusCode): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Convertir respuesta de modelo al formato estándar (compatibilidad con código existente)
     *
     * @param Response $response Objeto Response de PSR-7
     * @param array $modelResponse Respuesta del modelo
     * @param string $successMessage Mensaje en caso de éxito
     * @param int $successCode Código HTTP en caso de éxito
     * @return Response
     */
    public static function fromModel(Response $response, array $modelResponse, string $successMessage = '', int $successCode = 200): Response
    {
        if ($modelResponse['status'] === 'ok') {
            $data = $modelResponse['data'] ?? null;
            return self::success($response, $data, $successMessage, $successCode);
        } else {
            $errorMessage = $modelResponse['error'] ?? 'Error desconocido';
            return self::error($response, $errorMessage, 400);
        }
    }
}

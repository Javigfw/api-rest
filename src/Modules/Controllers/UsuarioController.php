<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\Utils;
use App\Modules\Models\UsuarioModel;
use App\Modules\Models\SuscripcionModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

/**
 * Controlador de Usuarios
 */
class UsuarioController extends Controller
{
    protected UsuarioModel $usuarioModel;
    protected SuscripcionModel $suscripcionModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->usuarioModel = new UsuarioModel();
        $this->suscripcionModel = new SuscripcionModel();
    }

    /**
     * Buscar usuarios (Admin)
     * Endpoint: GET /usuarios/buscar?search=xxx&roleFilter=admins|all
     */
    public function buscar(Request $request, Response $response, array $args): Response
    {
        // Obtener parámetros de búsqueda
        $queryParams = $request->getQueryParams();
        $search = $queryParams['search'] ?? '';
        $roleFilter = $queryParams['roleFilter'] ?? 'all';

        // Usar el método del modelo
        $result = $this->usuarioModel->buscarUsuarios($search, $roleFilter);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al buscar usuarios');
    }

    /**
     * Actualizar rol de usuario (Admin)
     * Endpoint: POST /usuarios/update-role
     */
    public function updateRole(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        $userId = $params['userId'] ?? null;
        $esAdmin = isset($params['esAdmin']) ? (int) $params['esAdmin'] : null;

        if (!$userId || $esAdmin === null) {
            return Utils::responseJsonError($response, 'userId y esAdmin son requeridos', '', 400);
        }

        $result = $this->usuarioModel->update((int) $userId, ['esAdmin' => $esAdmin]);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, ['success' => true, 'message' => 'Rol actualizado']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al actualizar rol');
    }

    /**
     * Restablecer contraseña de usuario (Admin)
     * Endpoint: PUT /usuarios/reset-password
     */
    public function adminResetPassword(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        $userId = $params['userId'] ?? null;
        $newPassword = $params['newPassword'] ?? null;

        if (!$userId || !$newPassword) {
            return Utils::responseJsonError($response, 'userId y newPassword son requeridos', '', 400);
        }

        if (strlen($newPassword) < 6) {
            return Utils::responseJsonError($response, 'La contraseña debe tener al menos 6 caracteres', '', 400);
        }

        // Hashear la contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $result = $this->usuarioModel->update((int) $userId, ['password' => $hashedPassword]);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, ['success' => true, 'message' => 'Contraseña actualizada']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al actualizar contraseña');
    }

    /**
     * Obtener perfil del usuario actual
     * Endpoint: GET /usuarios/perfil
     */
    public function getPerfil(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');

        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;
        $result = $this->usuarioModel->find($userId);

        if ($result['status'] === 'ok') {
            // No devolver la contraseña
            unset($result['data']['password']);

            // Verificar suscripción para compatibilidad legacy
            $isSuscrito = $this->suscripcionModel->usuarioTieneSuscripcionActiva($userId);
            $esAdmin = (bool)($jwt->data->esAdmin ?? false);

            return Utils::responseJsonOk($response, [
                'perfil' => $result['data'],
                'isSuscrito' => ($isSuscrito || $esAdmin)
            ]);
        }

        return Utils::responseJsonError($response, 'Usuario no encontrado', '', 404);
    }

    /**
     * Actualizar perfil de usuario (Restringido)
     * Endpoint: PUT /usuarios/perfil
     */
    public function updateProfile(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $params = $request->getParsedBody();

        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;

        // Validar datos permitidos
        $allowedFields = ['username', 'telefono', 'nombre'];
        $dataToUpdate = [];

        foreach ($allowedFields as $field) {
            if (isset($params[$field])) {
                $dataToUpdate[$field] = trim($params[$field]);
            }
        }

        if (empty($dataToUpdate)) {
            return Utils::responseJsonError($response, 'No se enviaron datos para actualizar', '', 400);
        }

        // Prevenir vaciar username
        if (isset($datatoupdate['username']) && empty($datatoupdate['username'])) {
            return utils::responsejsonerror($response, 'el username no puede estar vacío', '', 400);
        }

        // Prevenir vaciar usuario
        if (isset($datatoupdate['nombre']) && empty($datatoupdate['nombre'])) {
            return utils::responsejsonerror($response, 'el nombre de usuario no puede estar vacío', '', 400);
        }
        // Intentar actualizar
        $result = $this->usuarioModel->update($userId, $dataToUpdate);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, ['success' => true, 'message' => 'Perfil actualizado correctamente']);
        }

        // Manejar error de duplicado (asumiendo mensaje de error de MySQL 'Duplicate entry')
        $errorMsg = $result['error'] ?? 'Error al actualizar perfil';
        if (strpos($errorMsg, 'Duplicate') !== false) {
             return Utils::responseJsonError($response, 'El username ya está en uso', '', 409);
        }

        return Utils::responseJsonError($response, $errorMsg);
    }

    /**
     * Obtener perfil público de un usuario
     * Endpoint: GET /usuarios/profile/{username}
     */
    public function getPublicProfile(Request $request, Response $response, array $args): Response
    {
        $username = $args['username'] ?? null;

        if (!$username) {
            return Utils::responseJsonError($response, 'Username requerido', '', 400);
        }

        // Buscar por username o nombre formateado (parity con legacy)
        $result = $this->usuarioModel->findByUsernameOrFormattedName($username);

        if ($result['status'] === 'ok') {
            $user = $result['data'];
            $userId = (int) $user['idUsuario'];

            // Obtener estadísticas
            $statsResult = $this->usuarioModel->getEstadisticas($userId);
            $stats = ($statsResult['status'] === 'ok') ? $statsResult['data'] : null;

            // Formatear respuesta (parity con legacy)
            return Utils::responseJsonOk($response, [
                'success' => true,
                'usuario' => [
                    'nombre' => $user['nombre'],
                    'username' => $user['username'],
                    'fechaRegistro' => Utils::formatearFechaEspanol($user['fechaRegistro']),
                    'url_imagen' => $user['url_imagen'] ?? null
                ],
                'estadisticas' => $stats ? [
                    'testCompletados' => (int) $stats['testCompletados'],
                    'bloquesCompletados' => (int) $stats['bloquesCompletados'],
                    'mensajesForo' => (int) $stats['mensajesForo'],
                    'porcentajeGlobal' => (int) $stats['porcentajeGlobal']
                ] : null
            ]);
        }

        return Utils::responseJsonError($response, 'Usuario no encontrado', '', 404);
    }

    /**
     * Actualizar contraseña del usuario actual
     * Endpoint: PUT /usuarios/password
     */
    public function updatePassword(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $params = $request->getParsedBody();

        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;
        $currentPassword = $params['currentPassword'] ?? null;
        $newPassword = $params['newPassword'] ?? null;

        if (!$currentPassword || !$newPassword) {
            return Utils::responseJsonError($response, 'Contraseñas requeridas', '', 400);
        }

        // Verificar contraseña actual
        $user = $this->usuarioModel->find($userId);
        if ($user['status'] !== 'ok' || !password_verify($currentPassword, $user['data']['password'])) {
            return Utils::responseJsonError($response, 'Contraseña actual incorrecta', '', 400);
        }

        // Actualizar contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $this->usuarioModel->update($userId, ['password' => $hashedPassword]);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, ['success' => true, 'message' => 'Contraseña actualizada']);
        }

        return Utils::responseJsonError($response, 'Error al actualizar contraseña');
    }

    /**
     * Obtener suscripción del usuario actual
     * Endpoint: GET /usuarios/suscripcion
     */
    public function getSuscripcion(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');

        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;
        $result = $this->suscripcionModel->getActiveSuscripcion($userId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonOk($response, null); // Sin suscripción activa
    }

    /**
     * Suscribirse a un plan
     * Endpoint: POST /usuarios/suscripcion
     */
    public function suscribirse(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $params = $request->getParsedBody();

        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;
        $planId = $params['planId'] ?? null;

        if (!$planId) {
            return Utils::responseJsonError($response, 'planId requerido', '', 400);
        }

        $result = $this->suscripcionModel->createSuscripcion($userId, (int) $planId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data'], 201);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al crear suscripción');
    }

    /**
     * Cancelar suscripción
     * Endpoint: POST /usuarios/suscripcion/cancelar
     */
    public function cancelarSuscripcion(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');

        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;
        $result = $this->suscripcionModel->cancelActiveSuscripcion($userId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al cancelar suscripción');
    }

    /**
     * Actualizar imagen de perfil
     * Endpoint: POST /usuarios/imagen
     */
    public function updateImagen(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');

        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;
        $uploadedFiles = $request->getUploadedFiles();

        // Si no hay archivos, checkear si se envió una URL (retrocompatibilidad opcional)
        if (empty($uploadedFiles['imagen'])) {
            $params = $request->getParsedBody();
            $urlImagen = $params['url_imagen'] ?? null;

            if ($urlImagen) {
                $result = $this->usuarioModel->update($userId, ['url_imagen' => $urlImagen]);
                if ($result['status'] === 'ok') {
                    return Utils::responseJsonOk($response, ['success' => true, 'url_imagen' => $urlImagen]);
                }
            }
            return Utils::responseJsonError($response, 'No se ha enviado ninguna imagen', '', 400);
        }

        $file = $uploadedFiles['imagen'];

        // Validar errores de subida
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return Utils::responseJsonError($response, 'Error al subir el archivo', '', 400);
        }

        // Validar tipo de archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $contentType = $file->getClientMediaType();

        if (!in_array($contentType, $allowedTypes)) {
            return Utils::responseJsonError($response, 'Tipo de archivo no permitido', '', 400);
        }

        // Validar tamaño (5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return Utils::responseJsonError($response, 'El archivo es demasiado grande (máximo 5MB)', '', 400);
        }

        // Directorio de destino (Relativo a la raíz del proyecto public_html)
        // El API REST suele estar en public_html/api_rest/public/index.php
        // Assets suele estar en public_html/assets/
        $uploadDir = __DIR__ . '/../../../../assets/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generar nombre único
        $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = explode('/', $contentType)[1];
        }
        $filename = "avatar_{$userId}_" . time() . ".{$extension}";
        $file->moveTo($uploadDir . $filename);

        // Ruta para la BD
        $dbUrl = "assets/avatars/{$filename}";

        // Actualizar BD
        $result = $this->usuarioModel->update($userId, ['url_imagen' => $dbUrl]);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, [
                'success' => true,
                'message' => 'Imagen actualizada correctamente',
                'url' => $dbUrl
            ]);
        }

        return Utils::responseJsonError($response, 'Error al actualizar imagen en base de datos');
    }

    /**
     * Obtener estadísticas del usuario
     * Endpoint: GET /usuarios/estadisticas
     */
    public function getEstadisticas(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');

        if (!$jwt || !isset($jwt->data->id)) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $userId = (int) $jwt->data->id;
        $result = $this->usuarioModel->getEstadisticas($userId);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, 'Error al obtener estadísticas');
    }
}

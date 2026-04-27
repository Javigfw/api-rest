<?php

namespace App\Modules\Controllers;

use App\Helpers\Controller;
use App\Helpers\ResponseFormatter;
use App\Helpers\Utils;
use App\Modules\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

/**
 * Controlador de Autenticación Unificado
 * Maneja login, registro, logout, recuperación de contraseña y perfil
 */
class AuthController extends Controller
{
    protected AuthService $authService;
    protected \App\Modules\Models\UsuarioModel $usuarioModel;
    protected \App\Modules\Models\SuscripcionModel $suscripcionModel;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->authService = new AuthService();
        $this->usuarioModel = new \App\Modules\Models\UsuarioModel();
        $this->suscripcionModel = new \App\Modules\Models\SuscripcionModel();
    }

    /**
     * Registrar un nuevo usuario
     */
    public function registrar(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        // Validar parámetros requeridos
        $mensaje = Utils::requiredParams([
            'nombre',
            'email',
            'confirmar_email',
            'contrasena',
            'confirmar_contrasena'
        ], $params);

        if ($mensaje != '') {
            return ResponseFormatter::error($response, $mensaje, 400);
        }

        // Validar datos de registro
        $erroresValidacion = $this->authService->validarDatosRegistro($params);

        if (!empty($erroresValidacion)) {
            return ResponseFormatter::error(
                $response,
                'Errores de validación',
                422,
                $erroresValidacion
            );
        }

        // Preparar datos del usuario
        $userData = [
            'nombre' => trim($params['nombre']),
            'email' => strtolower(trim($params['email'])),
            'password' => $params['contrasena'],
            'username' => $params['username'] ?? explode('@', $params['email'])[0],
            'esAdmin' => 0,
            'telefono' => $params['telefono'] ?? null
        ];

        $result = $this->authService->registrarUsuario($userData);

        if ($result['status'] === 'ok') {
            // Obtener ID del usuario recién creado (idUsuario o codigo)
            $userId = $result['data']['idUsuario'] ?? $result['data']['codigo'] ?? null;
            // Si no se obtuvo el ID, buscar el usuario por email
            if (empty($userId)) {
                $userLookup = $this->usuarioModel->findByEmail($userData['email']);
                if ($userLookup['status'] === 'ok') {
                    $userId = $userLookup['data']['idUsuario'] ?? $userLookup['data']['codigo'] ?? null;
                }
            }


            // Procesar trialData si existe - ASIGNAR BLOQUES
            $trialData = $params['trialData'] ?? null;
            $bloquesAsignados = [];

            // Log user ID and trial data for debugging
            error_log('[AuthController] User ID for block assignment: ' . var_export($userId, true));
            error_log('[AuthController] Trial data received: ' . var_export($trialData, true));
            // Proceed with block assignment if applicable
            if ($userId && $trialData && !empty($trialData['blocks'])) {
                $bloques = $trialData['blocks'];

                // Filtrar IDs válidos (1-12)
                $bloques = array_map('intval', $bloques);
                $bloques = array_filter($bloques, function ($id) {
                    return $id > 0 && $id <= 12;
                });

                if (!empty($bloques)) {
                    try {
                        $pdo = \App\Helpers\Database::getConnection();

                        // Insertar asignaciones de bloques
                        $insertStmt = $pdo->prepare("
                            INSERT INTO bloque_usuario (idUsuario, idBloque, fechaDesbloqueo, fechaFin, razonDesbloqueo) 
                            VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 'registro_trial')
                        ");

                        foreach ($bloques as $bloqueId) {
                            $insertStmt->execute([$userId, $bloqueId]);
                        }

                        $bloquesAsignados = $bloques;
                    } catch (\Exception $e) {
                        // Log error pero no fallar el registro
                        error_log('[AuthController] Error asignando bloques: ' . $e->getMessage());
                    }
                }
            }

            // Generar token JWT para el nuevo usuario
            // Ensure we have user data for token generation
            $userDataForToken = $result['data'];
            if (empty($userDataForToken)) {
                // Fallback: retrieve user by email using controller's UsuarioModel
                $fallbackUser = $this->usuarioModel->findByEmail($userData['email']);
                if ($fallbackUser['status'] === 'ok') {
                    $userDataForToken = $fallbackUser['data'];
                }
            }
            $token = $this->authService->generateToken($userDataForToken);

            return ResponseFormatter::success(
                $response,
                [
                    'token' => $token,
                    'usuario' => $userDataForToken,
                    'bloques_asignados' => $bloquesAsignados
                ],
                $result['message'] ?? 'Usuario registrado exitosamente',
                201
            );
        } else {
            return ResponseFormatter::error(
                $response,
                $result['error'] ?? 'Error al registrar usuario',
                400
            );
        }
    }

    /**
     * Login de usuario
     */
    public function login(Request $request, Response $response, array $args): Response
    {
        try {
            $params = $request->getParsedBody();

            // Aceptar tanto 'password' como 'pass'
            $password = $params['password'] ?? $params['pass'] ?? null;
            $email = $params['email'] ?? $params['username'] ?? null; // Aceptar username tmb

            if (empty($email) || empty($password)) {
                return Utils::responseJsonError($response, 'Usuario/Email y contraseña son requeridos');
            }

            $result = $this->authService->autenticar($email, $password);

            if ($result['status'] === 'ok') {
                $user = $result['data'];
                $token = $this->authService->generateToken($user);
                
                // Verificar suscripción
                $isSuscrito = $this->suscripcionModel->usuarioTieneSuscripcionActiva((int)$user['idUsuario']);
                $esAdmin = (int)($user['esAdmin'] ?? 0) === 1;

                $datos = [
                    'token' => $token,
                    'usuario' => [
                        'idUsuario' => $user['idUsuario'],
                        'email' => $user['email'],
                        'nombre' => $user['nombre'],
                        'esAdmin' => $esAdmin,
                        'username' => $user['username'] ?? explode('@', $user['email'])[0],
                        'url_imagen' => $user['url_imagen'] ?? null,
                        'isSuscrito' => ($isSuscrito || $esAdmin)
                    ]
                ];

                return Utils::responseJsonOk($response, $datos);
            } else {
                $errorDetail = isset($result['error']) ? ': ' . $result['error'] : '';
                return Utils::responseJsonError($response, 'El usuario o contraseña son incorrectos' . $errorDetail);
            }
        } catch (\Exception $e) {
            return Utils::responseJsonError($response, 'Error en login: ' . $e->getMessage(), '', 500);
        }
    }

    /**
     * Cerrar sesión (logout)
     */
    public function logout(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        $token = $params['token'] ?? $request->getHeaderLine('Authorization');

        if (preg_match('/Bearer\s(\S+)/', $token, $matches)) {
            $token = $matches[1];
        }

        $usuarioId = $params['idUsuario'] ?? $params['codigo'] ?? null;

        if ($usuarioId) {
            $result = $this->authService->logout((int) $usuarioId, $token);
            if ($result['status'] === 'ok') {
                return Utils::responseJsonOk($response, $result['message'], 200);
            } else {
                return Utils::responseJsonError($response, $result['error'] ?? 'Error al cerrar sesión');
            }
        }

        return Utils::responseJsonOk($response, 'Sesión cerrada exitosamente', 200);
    }

    /**
     * Obtener usuario actual (me)
     */
    public function me(Request $request, Response $response, array $args): Response
    {
        $token = $request->getAttribute('authToken');

        if (!$token) {
            return Utils::responseJsonError($response, 'No autenticado', '', 401);
        }

        $result = $this->authService->getUserFromToken($token);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['data']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Sesión inválida', '', 401);
    }

    /**
     * Iniciar recuperación de contraseña
     */
    public function forgotPassword(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        if (empty($params['email'])) {
            return Utils::responseJsonError($response, 'El email es requerido');
        }

        $result = $this->authService->initiatePasswordReset($params['email']);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result, $result['message']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error desconocido');
    }

    /**
     * Completar restablecimiento de contraseña
     */
    public function resetPassword(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();

        $mensaje = Utils::requiredParams(['token', 'password', 'confirm_password'], $params);
        if ($mensaje != '') {
            return Utils::responseJsonError($response, $mensaje);
        }

        if ($params['password'] !== $params['confirm_password']) {
            return Utils::responseJsonError($response, 'Las contraseñas no coinciden');
        }

        if (strlen($params['password']) < 8) {
            return Utils::responseJsonError($response, 'La contraseña debe tener al menos 8 caracteres');
        }

        $result = $this->authService->resetPassword($params['token'], $params['password']);

        if ($result['status'] === 'ok') {
            return Utils::responseJsonOk($response, $result['message']);
        }

        return Utils::responseJsonError($response, $result['error'] ?? 'Error al restablecer contraseña');
    }

    /**
     * Verificar si un email ya está registrado
     */
    public function verificarEmail(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();

        if (empty($params['email'])) {
            return ResponseFormatter::error($response, 'El email es requerido', 400);
        }

        $existe = $this->authService->emailExiste($params['email']);

        return ResponseFormatter::success(
            $response,
            ['existe' => $existe, 'disponible' => !$existe],
            'Verificación completada'
        );
    }

    /**
     * Validar datos de registro (sin registrar)
     */
    public function validar(Request $request, Response $response, array $args): Response
    {
        $params = $request->getParsedBody();
        $errores = $this->authService->validarDatosRegistro($params);

        if (empty($errores)) {
            return ResponseFormatter::success($response, ['valido' => true], 'Datos válidos');
        } else {
            return ResponseFormatter::error($response, 'Errores de validación', 422, $errores);
        }
    }
    /**
     * Verificar estado de autenticación y privilegios de administrador
     */
    public function checkAdminStatus(Request $request, Response $response, array $args): Response
    {
        // 1. El token ya ha sido verificado por el AuthMiddleware
        $jwt = $request->getAttribute('jwt');

        if (!$jwt) {
            // Este caso en teoría no debería ocurrir si el middleware funciona correctamente
            return Utils::responseJson($response, [
                'authenticated' => false,
                'isAdmin' => false,
                'message' => 'No session active'
            ], 401);
        }

        // 2. Extraer datos del JWT
        $isAdmin = (!empty($jwt->data->esAdmin) && (int) $jwt->data->esAdmin === 1);
        $userId = $jwt->data->id;
        $email = $jwt->data->email;

        // 3. Buscar datos adicionales en DB
        $userRes = $this->usuarioModel->find((int) $userId);
        $username = 'User';
        $nombre = 'User';

        if ($userRes['status'] === 'ok') {
            $username = $userRes['data']['username'] ?? $email;
            $nombre = $userRes['data']['nombre'] ?? $username;
        }

        // 4. Si no es admin, 403
        if (!$isAdmin) {
            return Utils::responseJson($response, [
                'authenticated' => true,
                'isAdmin' => false,
                'user' => [
                    'id' => $userId,
                    'username' => $username
                ],
                'message' => 'User is not an administrator'
            ], 403);
        }

        // 5. Es Admin, 200
        return Utils::responseJson($response, [
            'authenticated' => true,
            'isAdmin' => true,
            'user' => [
                'id' => $userId,
                'username' => $username,
                'nombre' => $nombre
            ]
        ], 200);
    }
}

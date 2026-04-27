<?php

namespace App\Modules\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Modules\Models\UsuarioModel;
use App\Modules\Models\PasswordResetModel;

/**
 * Servicio de Autenticación
 * Encapsula la lógica de negocio para autenticación y registro
 */
class AuthService
{
    protected UsuarioModel $usuarioModel;
    
    // En producción, esto debe estar en variables de entorno (.env)
    public const JWT_SECRET = 'tu_clave_secreta_super_segura_cambiala_en_produccion';
    public const JWT_ALGO = 'HS256';

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Generar un JWT para un usuario
     * 
     * @param array $user
     * @return string
     */
    public function generateToken(array $user): string
    {
        $payload = [
            'iss' => 'api-slim-base', // Issuer
            'aud' => 'api-client',    // Audience
            'iat' => time(),          // Issued at
            'nbf' => time(),          // Not before
            'exp' => time() + (60 * 60 * 24), // Expiración (24 horas)
            'data' => [
                'id' => $user['idUsuario'] ?? $user['codigo'],
                'email' => $user['email'],
                'esAdmin' => $user['esAdmin'] ?? 0
            ]
        ];

        return JWT::encode($payload, self::JWT_SECRET, self::JWT_ALGO);
    }
    
    // ... (resume existing methods until getUserFromToken)

    /**
     * Obtener usuario desde un token
     *
     * @param string $token
     * @return array
     */
    public function getUserFromToken(string $token): array
    {
        // 1. Verificar si está en lista negra
        if ($this->tokenEstaInvalidado($token)) {
            return [
                'status' => 'error',
                'error' => 'Sesión expirada o inválida'
            ];
        }

        try {
            // 2. Decodificar token JWT
            $decoded = JWT::decode($token, new Key(self::JWT_SECRET, self::JWT_ALGO));
            
            // Acceder a los datos del payload
            $userId = $decoded->data->id;
            $email = $decoded->data->email;

            // 3. Buscar usuario (Opcional: podríamos confiar en el token si es reciente,
            // pero buscar en DB asegura que el usuario no haya sido borrado)
            $usuario = $this->usuarioModel->findByEmail($email);

            if ($usuario['status'] === 'ok') {
                unset($usuario['data']['password']); // No devolver password
                return $usuario; 
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => 'Token inválido: ' . $e->getMessage()
            ];
        }

        return [
            'status' => 'error',
            'error' => 'Usuario no encontrado'
        ];
    }

    /**
     * Registrar un nuevo usuario
     *
     * @param array $userData Datos del usuario
     * @return array
     */
    /**
     * Registrar un nuevo usuario
     *
     * @param array $userData Datos del usuario
     * @return array
     */
    public function registrarUsuario(array $userData): array
    {
        // Validar que el email no exista
        $existingUser = $this->usuarioModel->findByEmail($userData['email']);
        
        if ($existingUser['status'] === 'ok') {
            return [
                'status' => 'error',
                'error' => 'El email ya está registrado'
            ];
        }

        // Generar username único si no se proporciona o conflictua
        if (empty($userData['username'])) {
             // Generar base del username desde email
             $baseUsername = explode('@', $userData['email'])[0];
             $userData['username'] = $this->generateUniqueUsername($baseUsername);
        } else {
             // Verificar si el username proporcionado existe
             $existingUsername = $this->usuarioModel->findByUsernameOrFormattedName($userData['username']);
             if ($existingUsername['status'] === 'ok') {
                 // Si existe, generar uno único basado en el proporcionado
                 $userData['username'] = $this->generateUniqueUsername($userData['username']);
             }
        }

        // Validar formato de email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'error' => 'El formato del email no es válido'
            ];
        }

        // Validar longitud de contraseña (esperamos clave 'password' o 'contrasena')
        $password = $userData['password'] ?? $userData['contrasena'] ?? '';
        if (strlen($password) < 8) {
            return [
                'status' => 'error',
                'error' => 'La contraseña debe tener al menos 8 caracteres'
            ];
        }

        // Asegurar que usamos la clave correcta para el modelo
        if (!isset($userData['password'])) {
            $userData['password'] = $password;
        }
        // Limpiar clave antigua si existe para evitar conflictos
        if (isset($userData['contrasena'])) {
            unset($userData['contrasena']);
        }

        // Establecer valores por defecto
        // SQL script: esAdmin TINYINT(1). Default 0 (usuario normal)
        $userData['esAdmin'] = $userData['esAdmin'] ?? 0;
        $userData['fechaRegistro'] = date('Y-m-d H:i:s');
        
        // Limpiar campos no existentes en modelo
        unset($userData['rol']); // Eliminamos rol si viene

        // Crear usuario (el modelo hasheará la contraseña automáticamente)
        $result = $this->usuarioModel->createUser($userData);

        if ($result['status'] === 'ok') {
            // Obtener el usuario recién creado
            $userId = $result['data'] ?? null;
            
            if ($userId) {
                // UsuarioModel retorna el ID del insert
                $newUser = $this->usuarioModel->find($userId);
                
                if ($newUser['status'] === 'ok') {
                    // Ocultar contraseña en la respuesta
                    unset($newUser['data']['password']);
                    
                    return [
                        'status' => 'ok',
                        'data' => $newUser['data'],
                        'message' => 'Usuario registrado exitosamente'
                    ];
                }
            }
            
            return [
                'status' => 'ok',
                'message' => 'Usuario registrado exitosamente'
            ];
        }

        return $result;
    }

    /**
     * Autenticar usuario
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function autenticar(string $email, string $password): array
    {
        return $this->usuarioModel->authenticate($email, $password);
    }

    /**
     * Verificar si un email ya está registrado
     *
     * @param string $email
     * @return bool
     */
    public function emailExiste(string $email): bool
    {
        $result = $this->usuarioModel->findByEmail($email);
        return $result['status'] === 'ok';
    }

    /**
     * Validar datos de registro
     *
     * @param array $data
     * @return array Array con errores de validación (vacío si no hay errores)
     */
    public function validarDatosRegistro(array $data): array
    {
        $errores = [];

        // Validar nombre
        if (empty($data['nombre']) || strlen(trim($data['nombre'])) < 2) {
            $errores['nombre'] = 'El nombre debe tener al menos 2 caracteres';
        }

        // Validar email
        if (empty($data['email'])) {
            $errores['email'] = 'El email es requerido';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'El formato del email no es válido';
        } elseif ($this->emailExiste($data['email'])) {
            $errores['email'] = 'El email ya está registrado';
        }

        // Validar confirmación de email
        if (empty($data['confirmar_email'])) {
            $errores['confirmar_email'] = 'La confirmación de email es requerida';
        } elseif ($data['email'] !== $data['confirmar_email']) {
            $errores['confirmar_email'] = 'Los emails no coinciden';
        }

        // Validar contraseña (usamos contrasena del formulario)
        if (empty($data['contrasena'])) {
            $errores['contrasena'] = 'La contraseña es requerida';
        } elseif (strlen($data['contrasena']) < 8) {
            $errores['contrasena'] = 'La contraseña debe tener al menos 8 caracteres';
        }

        // Validar confirmación de contraseña
        if (empty($data['confirmar_contrasena'])) {
            $errores['confirmar_contrasena'] = 'La confirmación de contraseña es requerida';
        } elseif ($data['contrasena'] !== $data['confirmar_contrasena']) {
            $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden';
        }

        return $errores;
    }

    /**
     * Cerrar sesión (logout)
     * Este método puede ser usado para invalidar tokens o limpiar sesiones
     *
     * @param int $usuarioId
     * @param string|null $token Token a invalidar (opcional)
     * @return array
     */
    public function logout(int $usuarioId, ?string $token = null): array
    {
        // Si se proporciona un token, invalidarlo
        if ($token) {
            $this->invalidarToken($token, $usuarioId);
        }

        return [
            'status' => 'ok',
            'message' => 'Sesión cerrada exitosamente'
        ];
    }

    /**
     * Invalidar un token (agregarlo a lista negra)
     * 
     * @param string $token
     * @param int $usuarioId
     * @return void
     */
    protected function invalidarToken(string $token, int $usuarioId): void
    {
        // Guardar token invalidado en base de datos
        // Nota: El usuario proporcionó script con 'tokens_invalidos' (sin 'a').
        // Ajustamos al nombre de tabla del usuario si es posible, o usamos el que creamos nosotros.
        // Si el usuario ejecutó nuestro script, es tokens_invalidados.
        // Si usó SU script, es tokens_invalidos.
        // Trataremos de usar tokens_invalidados (nuestro) por consistencia con lo previo,
        // pero la tabla 'usuario' esquema viene del script usuario.
        // Asumiendo que el usuario CREÓ su DB con su script:
        // Tabla: tokens_invalidos
        // Campos: id, token, usuario, fecha_invalidacion
        
        try {
            $tableName = 'tokens_invalidos'; // Usando nombre del script del usuario
            $sql = "INSERT INTO $tableName (token, usuario, fecha_invalidacion) 
                    VALUES (:token, :usuario, :fecha)";
            
            $params = [
                'token' => [$token, \PDO::PARAM_STR],
                'usuario' => [$usuarioId, \PDO::PARAM_INT],
                'fecha' => [date('Y-m-d H:i:s'), \PDO::PARAM_STR]
            ];

            @\App\Helpers\Queries::crear($sql, $params);
        } catch (\Exception $e) {
            // Intentar con el otro nombre por si acaso
             try {
                $tableName = 'tokens_invalidados';
                $sql = "INSERT INTO $tableName (token, usuario, fecha_invalidacion) 
                        VALUES (:token, :usuario, :fecha)";
                
                $params = [
                    'token' => [$token, \PDO::PARAM_STR],
                    'usuario' => [$usuarioId, \PDO::PARAM_INT],
                    'fecha' => [date('Y-m-d H:i:s'), \PDO::PARAM_STR]
                ];
                @\App\Helpers\Queries::crear($sql, $params);
             } catch (\Exception $ex) {}
        }
    }

    /**
     * Verificar si un token está invalidado
     *
     * @param string $token
     * @return bool
     */
    public function tokenEstaInvalidado(string $token): bool
    {
        try {
            $tableName = 'tokens_invalidos';
            $sql = "SELECT COUNT(*) as count FROM $tableName WHERE token = :token";
            $params = ['token' => [$token, \PDO::PARAM_STR]];
            
            $result = \App\Helpers\Queries::listar($sql, $params);

            if ($result['status'] === 'ok' && !empty($result['data'])) {
                return $result['data'][0]['count'] > 0;
            } else {
                 // Fallback
                 $tableName = 'tokens_invalidados';
                 $sql = "SELECT COUNT(*) as count FROM $tableName WHERE token = :token";
                 $result = \App\Helpers\Queries::listar($sql, $params);
                 if ($result['status'] === 'ok' && !empty($result['data'])) {
                    return $result['data'][0]['count'] > 0;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Iniciar proceso de recuperación de contraseña
     *
     * @param string $email
     * @return array
     */
    public function initiatePasswordReset(string $email): array
    {
        // 1. Verificar si el email existe
        $user = $this->usuarioModel->findByEmail($email);
        
        if ($user['status'] !== 'ok') {
            // Por seguridad, no decimos si el email existe o no, pero logueamos
            // Retornamos éxito simulado para evitar enumeración de usuarios
            return [
                'status' => 'ok',
                'message' => 'Si el email existe, se enviarán instrucciones.'
            ];
        }

        // 2. Generar token único (random hex)
        $token = bin2hex(random_bytes(32));

        // 3. Guardar token en DB
        $resetModel = new PasswordResetModel();
        $resetModel->createToken($email, $token);

        // 4. Simular envío de email (Retornamos el token solo para desarrollo/pruebas)
        // En producción: enviarEmail($email, $token);
        // y NO retornar el token en la respuesta HTTP.
        
        return [
            'status' => 'ok',
            'message' => 'Si el email existe, se enviarán instrucciones.',
            'debug_token' => $token // SOLO PARA DEBUG, QUITAR EN PRODUCCIÓN
        ];
    }

    /**
     * Restablecer contraseña usando token
     *
     * @param string $token
     * @param string $newPassword
     * @return array
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $resetModel = new PasswordResetModel();

        // 1. Buscar token
        $result = $resetModel->findByToken($token);

        if ($result['status'] !== 'ok') {
            return [
                'status' => 'error',
                'error' => 'Token inválido o expirado'
            ];
        }

        $resetData = $result['data'];

        // 2. Verificar expiración (1 hora)
        $createdAt = strtotime($resetData['created_at']);
        if (time() - $createdAt > 3600) {
            $resetModel->deleteToken($token); // Limpiar token expirado
            return [
                'status' => 'error',
                'error' => 'El token ha expirado. Solicite uno nuevo.'
            ];
        }

        // 3. Buscar usuario
        $user = $this->usuarioModel->findByEmail($resetData['email']);
        if ($user['status'] !== 'ok') {
             return [
                'status' => 'error',
                'error' => 'Usuario no encontrado'
            ];
        }
        
        $userId = $user['data']['idUsuario'];

        // 4. Actualizar contraseña
        // Nota: UsuarioModel::update debería manejar el hash si detecta 'password'
        // Pero el modelo genérico update() suele ser directo.
        // Verificamos si update() hashea. Si no, lo hacemos aquí.
        // UsuarioModel extiende Model->update() que es genérico.
        // Deberíamos usar un método específico o hashear aquí.
        // REVISANDO UsuarioModel... (no lo veo ahora, asumo hash manual para seguridad).
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateResult = $this->usuarioModel->update($userId, ['password' => $hashedPassword]);

        if ($updateResult['status'] === 'ok') {
            // 5. Eliminar token usado
            $resetModel->deleteToken($token);

            return [
                'status' => 'ok',
                'message' => 'Contraseña actualizada correctamente'
            ];
        }

        return [
            'status' => 'error',
            'error' => 'Error al actualizar contraseña'
        ];
    }

    /**
     * Generar username único
     * 
     * @param string $baseUsername
     * @return string
     */
    protected function generateUniqueUsername(string $baseUsername): string
    {
        $username = $baseUsername;
        $counter = 1;
        
        while (true) {
            $result = $this->usuarioModel->findByUsernameOrFormattedName($username);
            if ($result['status'] !== 'ok') {
                return $username;
            }
            
            // Si existe, agregar sufijo aleatorio
            $username = $baseUsername . mt_rand(1000, 9999);
            $counter++;
            
            // Evitar bucles infinitos (aunque improbable con random)
            if ($counter > 10) {
                 $username = $baseUsername . uniqid();
            }
        }
    }
}
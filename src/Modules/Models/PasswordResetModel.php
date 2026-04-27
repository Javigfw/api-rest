<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para la gestión de tokens de restablecimiento de contraseña
 */
class PasswordResetModel extends Model
{
    protected string $table = 'password_resets';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'email',
        'token',
        'created_at'
    ];

    /**
     * Crear un nuevo token de restablecimiento
     *
     * @param string $email
     * @param string $token
     * @return array
     */
    public function createToken(string $email, string $token): array
    {
        // Primero eliminar tokens anteriores para este email
        $this->deleteByEmail($email);

        $data = [
            'email' => $email,
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    /**
     * Buscar un token válido
     *
     * @param string $token
     * @return array
     */
    public function findByToken(string $token): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE token = :token LIMIT 1";
        $params = [
            'token' => [$token, PDO::PARAM_STR]
        ];

        $result = $this->query($sql, $params);

        if ($result['status'] === 'ok' && !empty($result['data'])) {
            return [
                'status' => 'ok',
                'data' => $result['data'][0]
            ];
        }

        return [
            'status' => 'error',
            'error' => 'Token inválido o no encontrado'
        ];
    }

    /**
     * Eliminar tokens por email
     *
     * @param string $email
     * @return void
     */
    public function deleteByEmail(string $email): void
    {
        $sql = "DELETE FROM {$this->table} WHERE email = :email";
        $params = [
            'email' => [$email, PDO::PARAM_STR]
        ];
        
        \App\Helpers\Queries::borrar($sql, $params);
    }
    
    /**
     * Eliminar un token específico
     *
     * @param string $token
     * @return void
     */
    public function deleteToken(string $token): void
    {
        $sql = "DELETE FROM {$this->table} WHERE token = :token";
        $params = [
            'token' => [$token, PDO::PARAM_STR]
        ];
        
        \App\Helpers\Queries::borrar($sql, $params);
    }
}

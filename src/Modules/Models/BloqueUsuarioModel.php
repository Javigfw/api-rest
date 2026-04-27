<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para la relación entre bloques y usuarios
 */
class BloqueUsuarioModel extends Model
{
    protected string $table = 'bloque_usuario';
    protected string $primaryKey = 'idBloqueUsuario';

    protected array $fillable = [
        'idBloque',
        'idUsuario',
        'fechaDesbloqueo',
        'fechaFin',
        'razonDesbloqueo'
    ];

    /**
     * Obtener bloques asignados a un usuario
     *
     * @param int $usuarioId
     * @return array
     */
    public function findByUsuario(int $usuarioId): array
    {
        $sql = "SELECT bu.*, b.nombre, b.descripcion
                FROM {$this->table} bu
                JOIN bloque b ON b.idBloque = bu.idBloque
                WHERE bu.idUsuario = :usuario
                ORDER BY b.idBloque ASC";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Asignar bloque a usuario
     *
     * @param int $bloqueId
     * @param int $usuarioId
     * @param string $razon
     * @return array
     */
    public function asignarBloque(int $bloqueId, int $usuarioId, string $razon = 'Compra'): array
    {
        // Verificar si ya está asignado
        $sqlCheck = "SELECT idBloqueUsuario FROM {$this->table} WHERE idBloque = :bloque AND idUsuario = :usuario";
        $checkResult = $this->query($sqlCheck, [
            'bloque' => [$bloqueId, PDO::PARAM_INT],
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ]);

        if ($checkResult['status'] === 'ok' && ! empty($checkResult['data'])) {
            return [
                'status' => 'error',
                'error' => 'El bloque ya está asignado al usuario'
            ];
        }

        $data = [
            'idBloque' => $bloqueId,
            'idUsuario' => $usuarioId,
            'fechaDesbloqueo' => date('Y-m-d H:i:s'),
            'razonDesbloqueo' => $razon
        ];

        return $this->create($data);
    }

    /**
     * Desasignar bloque de usuario
     *
     * @param int $bloqueId
     * @param int $usuarioId
     * @return array
     */
    public function desasignarBloque(int $bloqueId, int $usuarioId): array
    {
        $sql = "DELETE FROM {$this->table} WHERE idBloque = :bloque AND idUsuario = :usuario";
        
        $params = [
            'bloque' => [$bloqueId, PDO::PARAM_INT],
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ];

        return \App\Helpers\Queries::borrar($sql, $params);
    }

    /**
     * Verifica si un usuario tiene acceso a un bloque específico (trial o asignado)
     * El acceso se considera válido si:
     * - Existe un registro en bloque_usuario para ese usuario y bloque
     * - fechaFin es NULL o es mayor a la fecha actual
     *
     * @param int $usuarioId
     * @param int $bloqueId
     * @return bool
     */
    public function usuarioTieneAccesoBloque(int $usuarioId, int $bloqueId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} 
                WHERE idUsuario = :usuario 
                AND idBloque = :bloque 
                AND (fechaFin IS NULL OR fechaFin > NOW())
                LIMIT 1";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT],
            'bloque' => [$bloqueId, PDO::PARAM_INT]
        ];

        $result = $this->query($sql, $params);

        return $result['status'] === 'ok' && !empty($result['data']);
    }
}

<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para la gestión de niveles
 */
class NivelModel extends Model
{
    protected string $table = 'nivel';
    protected string $primaryKey = 'idNivel';

    protected array $fillable = [
        'idBloque',
        'nivel'
    ];

    /**
     * Obtener niveles de un bloque
     *
     * @param int $bloqueId
     * @return array
     */
    public function findByBloque(int $bloqueId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE idBloque = :bloque ORDER BY nivel ASC";
        $params = [
            'bloque' => [$bloqueId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Buscar o crear un nivel en un bloque específico
     *
     * @param int $idBloque
     * @param int $numeroNivel
     * @return array
     */
    public function findOrCreateByBloqueAndNumero(int $idBloque, int $numeroNivel): array
    {
        // Primero intentar encontrar el nivel
        $sql = "SELECT * FROM {$this->table} 
                WHERE idBloque = :idBloque AND nivel = :nivel 
                LIMIT 1";
        
        $params = [
            'idBloque' => [$idBloque, PDO::PARAM_INT],
            'nivel' => [$numeroNivel, PDO::PARAM_INT]
        ];

        $result = $this->query($sql, $params);

        // Si existe, retornar el ID
        if ($result['status'] === 'ok' && !empty($result['data'])) {
            return [
                'status' => 'ok',
                'data' => $result['data'][0]['idNivel']
            ];
        }

        // Si no existe, crearlo
        $createData = [
            'idBloque' => $idBloque,
            'nivel' => $numeroNivel
        ];

        $createResult = $this->create($createData);

        if ($createResult['status'] === 'ok') {
            // Obtener el ID del nivel recién creado
            $lastId = $this->db->lastInsertId();
            return [
                'status' => 'ok',
                'data' => (int)$lastId
            ];
        }

        return [
            'status' => 'error',
            'error' => $createResult['error'] ?? 'Error al crear nivel'
        ];
    }
}


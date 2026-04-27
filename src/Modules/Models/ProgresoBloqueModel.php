<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para el progreso de usuarios en bloques
 */
class ProgresoBloqueModel extends Model
{
    protected string $table = 'progreso_bloque';
    protected string $primaryKey = 'idProgreso';

    protected array $fillable = [
        'idBloque',
        'idUsuario',
        'porcentajeCompletado'
    ];

    /**
     * Obtener progreso de un usuario en un bloque
     *
     * @param int $bloqueId
     * @param int $usuarioId
     * @return array
     */
    public function findByBloqueUsuario(int $bloqueId, int $usuarioId): array
    {
        $sql = "SELECT pb.*, b.nombre as bloque_nombre
                FROM {$this->table} pb
                JOIN bloque b ON b.idBloque = pb.idBloque
                WHERE pb.idBloque = :bloque AND pb.idUsuario = :usuario";

        $params = [
            'bloque' => [$bloqueId, PDO::PARAM_INT],
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ];

        $result = $this->query($sql, $params);

        if ($result['status'] == 'ok' && !empty($result['data'])) {
            return [
                'status' => 'ok',
                'data' => $result['data'][0]
            ];
        }

        return [
            'status' => 'error',
            'error' => 'Progreso no encontrado'
        ];
    }

    /**
     * Obtener todos los progresos de un usuario
     *
     * @param int $usuarioId
     * @return array
     */
    public function findByUsuario(int $usuarioId): array
    {
        // En nuevo esquema Bloque no tiene nivel directamente, se obtiene por tabla Nivel
        // Ajustamos la query para evitarJOIN con nivel si no es estrictamente necesario o ajustarlo
        $sql = "SELECT pb.*, b.nombre as bloque_nombre
                FROM {$this->table} pb
                JOIN bloque b ON b.idBloque = pb.idBloque
                WHERE pb.idUsuario = :usuario
                ORDER BY pb.idProgreso DESC";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Iniciar progreso en un bloque
     *
     * @param int $bloqueId
     * @param int $usuarioId
     * @return array
     */
    public function iniciarProgreso(int $bloqueId, int $usuarioId): array
    {
        // Verificar si ya existe progreso
        $existing = $this->findByBloqueUsuario($bloqueId, $usuarioId);
        
        if ($existing['status'] === 'ok') {
            return [
                'status' => 'ok',
                'data' => $existing['data'],
                'message' => 'Progreso ya existe'
            ];
        }

        $data = [
            'idBloque' => $bloqueId,
            'idUsuario' => $usuarioId,
            'porcentajeCompletado' => 0
        ];

        return $this->create($data);
    }

    /**
     * Actualizar porcentaje de progreso
     *
     * @param int $bloqueId
     * @param int $usuarioId
     * @param float $porcentaje
     * @return array
     */
    public function actualizarPorcentaje(int $bloqueId, int $usuarioId, float $porcentaje): array
    {
        $progreso = $this->findByBloqueUsuario($bloqueId, $usuarioId);

        if ($progreso['status'] !== 'ok') {
            return $progreso;
        }

        $progresoId = $progreso['data']['idProgreso'];
        $data = ['porcentajeCompletado' => $porcentaje];

        // SQL script no tiene campos fecha_fin ni completado, solo actualizamos porcentaje.
        
        return $this->update($progresoId, $data);
    }

    /**
     * Marcar bloque como completado
     *
     * @param int $bloqueId
     * @param int $usuarioId
     * @return array
     */
    public function completar(int $bloqueId, int $usuarioId): array
    {
        return $this->actualizarPorcentaje($bloqueId, $usuarioId, 100);
    }
    /**
     * Actualizar o insertar progreso de un usuario en un bloque
     *
     * @param int $usuarioId
     * @param int $bloqueId
     * @param float $porcentaje
     * @return array
     */
    public function actualizarProgreso(int $usuarioId, int $bloqueId, float $porcentaje): array
    {
        $idProgresoRes = $this->query(
            "SELECT idProgreso FROM {$this->table} WHERE idUsuario = :usuario AND idBloque = :bloque",
            [
                'usuario' => [$usuarioId, PDO::PARAM_INT],
                'bloque' => [$bloqueId, PDO::PARAM_INT]
            ]
        );

        if ($idProgresoRes['status'] === 'ok' && !empty($idProgresoRes['data'])) {
            return $this->update($idProgresoRes['data'][0]['idProgreso'], ['porcentajeCompletado' => $porcentaje]);
        } else {
            return $this->create([
                'idUsuario' => $usuarioId,
                'idBloque' => $bloqueId,
                'porcentajeCompletado' => $porcentaje
            ]);
        }
    }
}

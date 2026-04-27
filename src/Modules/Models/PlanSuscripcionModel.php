<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para la gestión de planes de suscripción
 */
class PlanSuscripcionModel extends Model
{
    protected string $table = 'plan_suscripcion';
    protected string $primaryKey = 'idPlan';

    protected array $fillable = [
        'nombre',
        'precio',
        'duracion_dias',
        'descripcion'
    ];

    /**
     * Obtener planes (todos son visibles, no hay campo activo en SQL script)
     *
     * @return array
     */
    public function findActivos(): array
    {
        // El script SQL no tiene campo 'activo', así que retornamos todos
        return $this->findAll([], 'precio ASC');
    }

    /**
     * Obtener plan con número de suscriptores
     *
     * @param int $planId
     * @return array
     */
    public function findWithSuscriptores(int $planId): array
    {
        // Ajustado a claves idPlan, idSuscripcion, idUsuario
        $sql = "SELECT ps.*, COUNT(s.idSuscripcion) as total_suscriptores
                FROM {$this->table} ps
                LEFT JOIN suscripcion s ON s.idPlan = ps.idPlan AND s.estado = 'Activa'
                WHERE ps.idPlan = :plan
                GROUP BY ps.idPlan";

        $params = [
            'plan' => [$planId, PDO::PARAM_INT]
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
            'error' => 'Plan no encontrado'
        ];
    }
}

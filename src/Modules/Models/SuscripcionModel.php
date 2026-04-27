<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para la gestión de suscripciones
 */
class SuscripcionModel extends Model
{
    protected string $table = 'suscripcion';
    protected string $primaryKey = 'idSuscripcion';

    protected array $fillable = [
        'idUsuario',
        'idPlan',
        'fechaInicio',
        'fechaFin',
        'estado'
    ];

    /**
     * Obtener suscripción activa de un usuario
     *
     * @param int $usuarioId
     * @return array
     */
    public function findActivaByUsuario(int $usuarioId): array
    {
        // Ajuste de campos: idSuscripcion, idUsuario, idPlan, fechaInicio, fechaFin, estado
        // Estado 'Activa' se asume como valor para suscripción válida
        $sql = "SELECT s.*, p.nombre as plan_nombre, p.precio, p.duracion_dias
                FROM {$this->table} s
                JOIN plan_suscripcion p ON p.idPlan = s.idPlan
                WHERE s.idUsuario = :usuario AND s.estado = 'Activa'
                LIMIT 1";

        $params = [
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
            'error' => 'No se encontró suscripción activa'
        ];
    }

    /**
     * Alias para findActivaByUsuario
     */
    public function getActiveSuscripcion(int $usuarioId): array
    {
        return $this->findActivaByUsuario($usuarioId);
    }

    /**
     * Obtener todas las suscripciones con detalles de usuario y plan
     *
     * @param string $search Búsqueda por nombre o email de usuario
     * @param string|null $dateFrom Fecha desde (formato Y-m-d)
     * @param string|null $dateTo Fecha hasta (formato Y-m-d)
     * @return array
     */
    public function findAllWithDetails(string $search = '', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT 
                    s.idSuscripcion,
                    s.fechaInicio,
                    s.fechaFin,
                    s.estado,
                    u.nombre AS usuario_nombre,
                    u.email AS usuario_email,
                    p.nombre AS plan_nombre,
                    p.precio
                FROM {$this->table} s
                JOIN usuario u ON u.idUsuario = s.idUsuario
                JOIN plan_suscripcion p ON p.idPlan = s.idPlan
                WHERE 1=1";

        $params = [];

        // Filtro de búsqueda por nombre o email
        if (!empty($search)) {
            $sql .= " AND (u.nombre LIKE :search OR u.email LIKE :search)";
            $params['search'] = ["%$search%", \PDO::PARAM_STR];
        }

        // Filtro por fecha desde
        if ($dateFrom) {
            $sql .= " AND s.fechaInicio >= :dateFrom";
            $params['dateFrom'] = [$dateFrom, \PDO::PARAM_STR];
        }

        // Filtro por fecha hasta
        if ($dateTo) {
            $sql .= " AND s.fechaInicio <= :dateTo";
            $params['dateTo'] = [$dateTo, \PDO::PARAM_STR];
        }

        $sql .= " ORDER BY s.fechaInicio DESC";

        return $this->query($sql, $params);
    }

    /**
     * Obtener detalles completos de una suscripción por ID
     *
     * @param int $id ID de la suscripción
     * @return array
     */
    public function findByIdWithDetails(int $id): array
    {
        $sql = "SELECT 
                    s.idSuscripcion,
                    s.fechaInicio,
                    s.fechaFin,
                    s.estado,
                    u.idUsuario,
                    u.nombre AS usuario_nombre,
                    u.email AS usuario_email,
                    u.username AS usuario_username,
                    p.idPlan,
                    p.nombre AS plan_nombre,
                    p.precio,
                    p.duracion_dias,
                    p.descripcion AS plan_descripcion
                FROM {$this->table} s
                JOIN usuario u ON u.idUsuario = s.idUsuario
                JOIN plan_suscripcion p ON p.idPlan = s.idPlan
                WHERE s.idSuscripcion = :id
                LIMIT 1";

        $params = [
            'id' => [$id, \PDO::PARAM_INT]
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
            'error' => 'Suscripción no encontrada'
        ];
    }

    /**
     * Crear nueva suscripción para un usuario
     *
     * @param int $usuarioId
     * @param int $planId
     * @return array
     */
    public function createSuscripcion(int $usuarioId, int $planId): array
    {
        // Obtener duración del plan
        $sqlPlan = "SELECT duracion_dias FROM plan_suscripcion WHERE idPlan = :plan";
        $planResult = $this->query($sqlPlan, ['plan' => [$planId, PDO::PARAM_INT]]);

        if ($planResult['status'] !== 'ok' || empty($planResult['data'])) {
            return [
                'status' => 'error',
                'error' => 'Plan no encontrado'
            ];
        }

        $duracionDias = $planResult['data'][0]['duracion_dias'];
        $fechaInicio = date('Y-m-d H:i:s');
        $fechaFin = date('Y-m-d H:i:s', strtotime("+$duracionDias days"));

        $data = [
            'idUsuario' => $usuarioId,
            'idPlan' => $planId,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'estado' => 'Activa'
        ];

        return $this->create($data);
    }

    /**
     * Cancelar suscripción
     *
     * @param int $suscripcionId
     * @return array
     */
    public function cancelar(int $suscripcionId): array
    {
        return $this->update($suscripcionId, [
            'estado' => 'Cancelada',
            'fechaFin' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Cancelar la suscripción activa de un usuario (Logic from legacy script)
     *
     * @param int $userId
     * @return array
     */
    public function cancelActiveSuscripcion(int $userId): array
    {
        // 1. Buscar la suscripción activa
        $activeRes = $this->getActiveSuscripcion($userId);

        if ($activeRes['status'] !== 'ok') {
            return [
                'status' => 'error',
                'error' => 'No tienes una suscripción activa para cancelar.'
            ];
        }

        $idSuscripcion = $activeRes['data']['idSuscripcion'];

        // 2. Actualizar estado a cancelada
        $result = $this->cancelar((int)$idSuscripcion);

        if ($result['status'] === 'ok') {
            return [
                'status' => 'ok',
                'message' => 'Suscripción cancelada correctamente.'
            ];
        }

        return [
            'status' => 'error',
            'error' => 'Error al cancelar la suscripción (DB update falló).'
        ];
    }

    /**
     * Verificar si un usuario tiene suscripción activa
     *
     * @param int $usuarioId
     * @return bool
     */
    public function usuarioTieneSuscripcionActiva(int $usuarioId): bool
    {
        $result = $this->findActivaByUsuario($usuarioId);
        return $result['status'] === 'ok';
    }

    /**
     * Obtener suscripciones que vencen pronto (próximos 7 días)
     *
     * @return array
     */
    public function findProximasAVencer(): array
    {
        $sql = "SELECT s.*, u.nombre, u.email, p.nombre as plan_nombre
                FROM {$this->table} s
                JOIN usuario u ON u.idUsuario = s.idUsuario
                JOIN plan_suscripcion p ON p.idPlan = s.idPlan
                WHERE s.estado = 'Activa' 
                AND s.fechaFin BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                ORDER BY s.fechaFin ASC";

        return $this->query($sql, []);
    }


}

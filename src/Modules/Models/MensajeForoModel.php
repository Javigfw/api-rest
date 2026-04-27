<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para la gestión de mensajes del foro
 */
class MensajeForoModel extends Model
{
    protected string $table = 'mensaje_foro';
    protected string $primaryKey = 'idMensaje';

    protected array $fillable = [
        'idUsuario',
        'contenido',
        'fechaCreacion'
    ];

    /**
     * Obtener todos los mensajes con información del usuario
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findAllWithUsuario(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT m.*, u.nombre, u.email, u.username, u.url_imagen
                FROM {$this->table} m
                JOIN usuario u ON u.idUsuario = m.idUsuario
                ORDER BY m.fechaCreacion ASC
                LIMIT :limit OFFSET :offset";

        $params = [
            'limit' => [$limit, PDO::PARAM_INT],
            'offset' => [$offset, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Obtener mensajes de un usuario
     *
     * @param int $usuarioId
     * @return array
     */
    public function findByUsuario(int $usuarioId): array
    {
        $sql = "SELECT m.*, u.nombre, u.username
                FROM {$this->table} m
                JOIN usuario u ON u.idUsuario = m.idUsuario
                WHERE m.idUsuario = :usuario
                ORDER BY m.fechaCreacion ASC";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Crear mensaje del foro
     *
     * @param int $usuarioId
     * @param string $contenido
     * @return array
     */
    public function createMensaje(int $usuarioId, string $contenido): array
    {
        $data = [
            'idUsuario' => $usuarioId,
            'contenido' => $contenido,
            'fechaCreacion' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    /**
     * Obtener mensajes del foro con filtros (para Admin panel)
     *
     * @param int $usuarioId Usuario que hace la petición (para contexto)
     * @param string $filter 'all' o '24h'
     * @param string $search Búsqueda por nombre, username o ID
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getMensajesForo(int $usuarioId = 0, string $filter = 'all', string $search = '', int $limit = 100, int $offset = 0): array
    {
        try {
            $whereClauses = [];
            $params = [];

            // Filtro por tiempo (últimas 24h)
            if ($filter === '24h') {
                $whereClauses[] = "m.fechaCreacion >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            }

            // Búsqueda por nombre, username o ID de mensaje
            if (!empty($search)) {
                $whereClauses[] = "(u.nombre LIKE :search OR u.username LIKE :search OR CAST(m.idMensaje AS CHAR) LIKE :searchId)";
                $params['search'] = ['%' . $search . '%', \PDO::PARAM_STR];
                $params['searchId'] = ['%' . $search . '%', \PDO::PARAM_STR];
            }

            $whereSQL = '';
            if (!empty($whereClauses)) {
                $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
            }

            // Usar LIMIT directamente para evitar problemas con parámetros
            $limitInt = (int)$limit;
            $offsetInt = (int)$offset;

            $sql = "SELECT m.idMensaje, m.idUsuario, m.contenido, m.fechaCreacion, 
                           u.nombre, u.username, u.url_imagen
                    FROM {$this->table} m
                    JOIN usuario u ON u.idUsuario = m.idUsuario
                    {$whereSQL}
                    ORDER BY m.fechaCreacion ASC
                    LIMIT {$limitInt} OFFSET {$offsetInt}";

            return $this->query($sql, $params);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar mensaje por ID
     *
     * @param int $id
     * @return array
     */
    public function deleteById(int $id): array
    {
        return $this->delete($id);
    }
}

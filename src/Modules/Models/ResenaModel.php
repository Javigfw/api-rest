<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para las reseñas de usuarios
 */
class ResenaModel extends Model
{
    protected string $table = 'reseña';
    protected string $primaryKey = 'idReseña';

    protected array $fillable = [
        'idUsuario',
        'valoracion',
        'textoOpinion'
    ];

    /**
     * Obtener todas las reseñas con info de usuario
     *
     * @return array
     */
    public function findAllWithUsuario(): array
    {
        $sql = "SELECT r.*, u.nombre, u.username, u.url_imagen
                FROM {$this->table} r
                JOIN usuario u ON u.idUsuario = r.idUsuario
                ORDER BY r.idReseña DESC";

        return $this->query($sql, []);
    }

    /**
     * Obtener reseñas por valoración
     *
     * @param int $valoracion
     * @return array
     */
    public function findByValoracion(int $valoracion): array
    {
        $sql = "SELECT r.*, u.nombre, u.username
                FROM {$this->table} r
                JOIN usuario u ON u.idUsuario = r.idUsuario
                WHERE r.valoracion = :valoracion
                ORDER BY r.idReseña DESC";

        $params = [
            'valoracion' => [$valoracion, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }
    /**
     * Obtener reseñas aleatorias con info de usuario
     *
     * @param int $limit
     * @return array
     */
    public function findRandomWithUsuario(int $limit = 6): array
    {
        $sql = "SELECT r.idReseña, r.valoracion, r.textoOpinion, u.nombre, u.username
                FROM {$this->table} r
                INNER JOIN usuario u ON r.idUsuario = u.idUsuario
                ORDER BY RAND()
                LIMIT :limit";

        $params = [
            'limit' => [$limit, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }
}

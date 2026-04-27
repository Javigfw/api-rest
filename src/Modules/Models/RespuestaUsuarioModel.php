<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para las respuestas de usuarios a ejercicios
 */
class RespuestaUsuarioModel extends Model
{
    protected string $table = 'respuesta_usuario';
    protected string $primaryKey = 'idRespuestaUsuario';

    protected array $fillable = [
        'idUsuario',
        'idEjercicio',
        'idRespuesta',
        'esCorrecta',
        'fecha'
    ];

    /**
     * Guardar respuesta de usuario a un ejercicio
     *
     * @param int $usuarioId
     * @param int $ejercicioId
     * @param int $respuestaId
     * @param bool $esCorrecta
     * @return array
     */
    public function guardarRespuesta(int $usuarioId, int $ejercicioId, int $respuestaId, bool $esCorrecta): array
    {
        $data = [
            'idUsuario' => $usuarioId,
            'idEjercicio' => $ejercicioId,
            'idRespuesta' => $respuestaId,
            'esCorrecta' => $esCorrecta ? 1 : 0,
            'fecha' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    /**
     * Obtener respuestas de un usuario para un ejercicio
     *
     * @param int $usuarioId
     * @param int $ejercicioId
     * @return array
     */
    public function findByUsuarioEjercicio(int $usuarioId, int $ejercicioId): array
    {
        $sql = "SELECT ru.*, re.texto
                FROM {$this->table} ru
                JOIN respuesta_ejercicio re ON re.idRespuesta = ru.idRespuesta
                WHERE ru.idUsuario = :usuario AND ru.idEjercicio = :ejercicio
                ORDER BY ru.fecha DESC";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT],
            'ejercicio' => [$ejercicioId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Obtener última respuesta de un usuario para un ejercicio
     *
     * @param int $usuarioId
     * @param int $ejercicioId
     * @return array
     */
    public function findUltimaRespuesta(int $usuarioId, int $ejercicioId): array
    {
        $sql = "SELECT ru.*, re.texto
                FROM {$this->table} ru
                JOIN respuesta_ejercicio re ON re.idRespuesta = ru.idRespuesta
                WHERE ru.idUsuario = :usuario AND ru.idEjercicio = :ejercicio
                ORDER BY ru.fecha DESC
                LIMIT 1";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT],
            'ejercicio' => [$ejercicioId, PDO::PARAM_INT]
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
            'error' => 'No se encontraron respuestas'
        ];
    }

    /**
     * Obtener estadísticas de respuestas de un usuario
     *
     * @param int $usuarioId
     * @return array
     */
    public function getEstadisticasUsuario(int $usuarioId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_respuestas,
                    SUM(esCorrecta) as respuestas_correctas,
                    AVG(esCorrecta) * 100 as porcentaje_acierto
                FROM {$this->table}
                WHERE idUsuario = :usuario";

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
            'error' => 'No se encontraron estadísticas'
        ];
    }
    /**
     * Contar ejercicios distintos respondidos por un usuario en un bloque
     *
     * @param int $usuarioId
     * @param int $bloqueId
     * @return int
     */
    public function getRespondidosPorBloque(int $usuarioId, int $bloqueId): int
    {
        $sql = "SELECT COUNT(DISTINCT ru.idEjercicio) as respondidos
                FROM {$this->table} ru 
                JOIN ejercicio e ON e.idEjercicio = ru.idEjercicio 
                WHERE ru.idUsuario = :usuario AND e.idBloque = :bloque";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT],
            'bloque' => [$bloqueId, PDO::PARAM_INT]
        ];

        $result = $this->query($sql, $params);
        return (int)($result['data'][0]['respondidos'] ?? 0);
    }
}

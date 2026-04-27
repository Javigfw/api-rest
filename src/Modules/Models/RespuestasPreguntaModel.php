<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para las respuestas de usuarios a preguntas de formulario
 * (Nota: El esquema sugiere que esta tabla guarda respuestas de usuarios, no opciones predefinidas)
 */
class RespuestasPreguntaModel extends Model
{
    protected string $table = 'respuestas_pregunta';
    protected string $primaryKey = 'idRespuesta';

    protected array $fillable = [
        'respuesta',
        'idPregunta',
        'idUsuario'
    ];

    /**
     * Obtener respuestas por pregunta
     *
     * @param int $preguntaId
     * @return array
     */
    public function findByPregunta(int $preguntaId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE idPregunta = :pregunta";

        $params = [
            'pregunta' => [$preguntaId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Guardar respuesta de usuario
     *
     * @param int $preguntaId
     * @param int $usuarioId
     * @param string $respuestaTexto
     * @return array
     */
    public function saveRespuesta(int $preguntaId, int $usuarioId, string $respuestaTexto): array
    {
        $data = [
            'idPregunta' => $preguntaId,
            'idUsuario' => $usuarioId,
            'respuesta' => $respuestaTexto
        ];

        return $this->create($data);
    }
}

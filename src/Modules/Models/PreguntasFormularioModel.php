<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para las preguntas de formularios
 */
class PreguntasFormularioModel extends Model
{
    protected string $table = 'preguntas_formulario';
    protected string $primaryKey = 'idPregunta';

    protected array $fillable = [
        'pregunta'
    ];

    /**
     * Obtener todas las preguntas
     *
     * @return array
     */
    public function findAllQuestions(): array
    {
        return $this->findAll();
    }

    /**
     * Obtener respuestas de los usuarios a esta pregunta
     *
     * @param int $preguntaId
     * @return array
     */
    public function findWithUserRespuestas(int $preguntaId): array
    {
        $sql = "SELECT p.*, rp.respuesta, rp.idUsuario
                FROM {$this->table} p
                LEFT JOIN respuestas_pregunta rp ON rp.idPregunta = p.idPregunta
                WHERE p.idPregunta = :pregunta";

        $params = [
            'pregunta' => [$preguntaId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }
}

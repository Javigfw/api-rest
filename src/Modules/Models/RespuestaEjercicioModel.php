<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para las respuestas de ejercicios (opciones de respuesta)
 */
class RespuestaEjercicioModel extends Model
{
    protected string $table = 'respuesta_ejercicio';
    protected string $primaryKey = 'idRespuesta';

    protected array $fillable = [
        'idEjercicio',
        'texto',
        'esCorrecta'
    ];

    /**
     * Obtener respuestas de un ejercicio
     *
     * @param int $ejercicioId
     * @param bool $incluirCorrecta Si se debe incluir el campo esCorrecta
     * @return array
     */
    public function findByEjercicio(int $ejercicioId, bool $incluirCorrecta = false): array
    {
        // En el nuevo esquema no hay campo 'orden', ordenamos por idRespuesta
        $campos = $incluirCorrecta ? '*' : 'idRespuesta, idEjercicio, texto';
        
        $sql = "SELECT $campos FROM {$this->table} 
                WHERE idEjercicio = :ejercicio 
                ORDER BY idRespuesta ASC";

        $params = [
            'ejercicio' => [$ejercicioId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Obtener respuesta correcta de un ejercicio
     *
     * @param int $ejercicioId
     * @return array
     */
    public function findRespuestaCorrecta(int $ejercicioId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE idEjercicio = :ejercicio AND esCorrecta = 1 
                LIMIT 1";

        $params = [
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
            'error' => 'Respuesta correcta no encontrada'
        ];
    }

    /**
     * Verificar si una respuesta es correcta para un ejercicio específico
     *
     * @param int $respuestaId
     * @param int $ejercicioId
     * @return bool
     */
    public function esRespuestaCorrecta(int $respuestaId, int $ejercicioId): bool
    {
        $sql = "SELECT esCorrecta FROM {$this->table} 
                WHERE idRespuesta = :id AND idEjercicio = :ejercicio";
        
        $params = [
            'id' => [$respuestaId, PDO::PARAM_INT],
            'ejercicio' => [$ejercicioId, PDO::PARAM_INT]
        ];

        $result = $this->query($sql, $params);

        if ($result['status'] === 'ok' && !empty($result['data'])) {
            return (bool) $result['data'][0]['esCorrecta'];
        }

        return false;
    }
}

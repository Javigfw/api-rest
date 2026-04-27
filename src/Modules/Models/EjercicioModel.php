<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use App\Helpers\Database;
use PDO;

/**
 * Modelo para la gestión de ejercicios
 */
class EjercicioModel extends Model
{
    protected string $table = 'ejercicio';
    protected string $primaryKey = 'idEjercicio';

    protected array $fillable = [
        'tipo',
        'pregunta',
        'idBloque'
    ];

    /**
     * Obtener ejercicios de un bloque
     *
     * @param int $bloqueId
     * @return array
     */
    public function findByBloque(int $bloqueId): array
    {
        // ejercicio tiene campo 'orden'? El script SQL NO define campo 'orden' para ejercicio.
        // Solo idEjercicio, tipo, pregunta, idBloque.
        // Ordenaremos por idEjercicio.
        $sql = "SELECT e.*, b.nombre as bloque_nombre
                FROM {$this->table} e
                JOIN bloque b ON b.idBloque = e.idBloque
                WHERE e.idBloque = :bloque
                ORDER BY e.idEjercicio ASC";

        $params = [
            'bloque' => [$bloqueId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Contar ejercicios de un bloque
     *
     * @param int $bloqueId
     * @return int
     */
    public function countByBloque(int $bloqueId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE idBloque = :bloque";
        $params = ['bloque' => [$bloqueId, PDO::PARAM_INT]];
        $result = $this->query($sql, $params);
        return (int)($result['data'][0]['total'] ?? 0);
    }

    /**
     * Obtener ejercicios con sus respuestas correctas
     *
     * @param int $ejercicioId
     * @return array
     */
    public function findWithRespuestas(int $ejercicioId): array
    {
        // Obtener ejercicio con idNivel de nivel_ejercicio
        $sql = "SELECT e.*, ne.idNivel
                FROM {$this->table} e
                LEFT JOIN nivel_ejercicio ne ON ne.idEjercicio = e.idEjercicio
                WHERE e.idEjercicio = :ejercicio";
        
        $params = [
            'ejercicio' => [$ejercicioId, PDO::PARAM_INT]
        ];

        $ejercicioResult = $this->query($sql, $params);

        if ($ejercicioResult['status'] !== 'ok' || empty($ejercicioResult['data'])) {
            return [
                'status' => 'error',
                'error' => 'Ejercicio no encontrado'
            ];
        }

        $ejercicio = $ejercicioResult['data'][0];

        // Obtener respuestas del ejercicio (respuesta_ejercicio)
        $sqlRespuestas = "SELECT * FROM respuesta_ejercicio WHERE idEjercicio = :ejercicio";
        $respuestas = $this->query($sqlRespuestas, $params);

        if ($respuestas['status'] === 'ok') {
            $ejercicio['respuestas'] = $respuestas['data'];
        } else {
            $ejercicio['respuestas'] = [];
        }

        return [
            'status' => 'ok',
            'data' => $ejercicio
        ];
    }
    /**
     * Obtener ejercicios con sus opciones filtrados por bloque o nivel
     *
     * @param int|null $idBloque
     * @param int|null $idNivel
     * @return array
     */
    public function findEjerciciosConOpciones(?int $idBloque = null, ?int $idNivel = null): array
    {
        if ($idNivel) {
            $sql = "SELECT 
                        e.idEjercicio, 
                        e.pregunta, 
                        e.tipo, 
                        re.idRespuesta, 
                        re.texto AS opcion, 
                        re.esCorrecta AS opcionEsCorrecta
                    FROM nivel_ejercicio ne
                    INNER JOIN ejercicio e ON e.idEjercicio = ne.idEjercicio
                    LEFT JOIN respuesta_ejercicio re ON re.idEjercicio = e.idEjercicio
                    WHERE ne.idNivel = :idNivel
                    ORDER BY e.idEjercicio, re.idRespuesta";
            $params = ['idNivel' => [$idNivel, PDO::PARAM_INT]];
        } else {
            $sql = "SELECT 
                        e.idEjercicio, 
                        e.pregunta, 
                        e.tipo, 
                        re.idRespuesta, 
                        re.texto AS opcion, 
                        re.esCorrecta AS opcionEsCorrecta
                    FROM {$this->table} e
                    LEFT JOIN respuesta_ejercicio re ON re.idEjercicio = e.idEjercicio
                    WHERE e.idBloque = :idBloque
                    ORDER BY e.idEjercicio, re.idRespuesta";
            $params = ['idBloque' => [$idBloque, PDO::PARAM_INT]];
        }

        return $this->query($sql, $params);
    }

    /**
     * Buscar ejercicios/actividades con filtros (Admin)
     *
     * @param string $search
     * @param int|null $idBloque
     * @param int|null $idNivel
     * @return array
     */
    public function buscarActividades(string $search = '', ?int $idBloque = null, ?int $idNivel = null): array
    {
        $sql = "SELECT 
                    e.idEjercicio,
                    e.tipo,
                    e.pregunta,
                    b.nombre as nombre_bloque,
                    b.idBloque,
                    ne.idNivel,
                    (SELECT COUNT(DISTINCT ru.idUsuario) 
                        FROM respuesta_usuario ru 
                        WHERE ru.idEjercicio = e.idEjercicio) as estudiantes_asignados
                FROM {$this->table} e
                INNER JOIN bloque b ON e.idBloque = b.idBloque
                LEFT JOIN nivel_ejercicio ne ON e.idEjercicio = ne.idEjercicio
                WHERE 1=1";
        
        $params = [];
        
        if ($idNivel) {
            $sql .= " AND ne.idNivel = :idNivel";
            $params['idNivel'] = [$idNivel, PDO::PARAM_INT];
        }
        
        if ($idBloque) {
            $sql .= " AND e.idBloque = :idBloque";
            $params['idBloque'] = [$idBloque, PDO::PARAM_INT];
        }

        if (!empty($search)) {
            $sql .= " AND (e.pregunta LIKE :search1 OR e.tipo LIKE :search2 OR CAST(e.idEjercicio AS CHAR) LIKE :search3)";
            $params['search1'] = ["%$search%", PDO::PARAM_STR];
            $params['search2'] = ["%$search%", PDO::PARAM_STR];
            $params['search3'] = ["%$search%", PDO::PARAM_STR];
        }

        $sql .= " ORDER BY e.idEjercicio DESC";

        return $this->query($sql, $params);
    }

    /**
     * Crear un ejercicio completo con respuestas y nivel en una transacción
     *
     * @param array $data ['idBloque' => int, 'idNivel' => int|null, 'tipo' => string, 'pregunta' => string, 'respuestas' => array]
     * @return array
     */
    public function createWithRespuestasAndNivel(array $data): array
    {
        // Validar parámetros requeridos
        if (empty($data['idBloque']) || empty($data['tipo']) || empty($data['pregunta'])) {
            return [
                'status' => 'error',
                'error' => 'Faltan parámetros requeridos: idBloque, tipo, pregunta'
            ];
        }

        try {
            $pdo = Database::instance()->getConnection();
            $pdo->beginTransaction();

            // 1. Crear ejercicio
            $stmtEjercicio = $pdo->prepare(
                "INSERT INTO ejercicio (idBloque, tipo, pregunta) VALUES (:idBloque, :tipo, :pregunta)"
            );
            $stmtEjercicio->execute([
                ':idBloque' => (int)$data['idBloque'],
                ':tipo' => $data['tipo'],
                ':pregunta' => $data['pregunta']
            ]);

            $idEjercicio = (int)$pdo->lastInsertId();

            if ($idEjercicio <= 0) {
                $pdo->rollBack();
                return [
                    'status' => 'error',
                    'error' => 'Error al crear ejercicio: ID inválido'
                ];
            }

            // 2. Si hay idNivel, crear relación nivel_ejercicio
            if (!empty($data['idNivel'])) {
                $stmtNivel = $pdo->prepare(
                    "INSERT INTO nivel_ejercicio (idNivel, idEjercicio) VALUES (:idNivel, :idEjercicio)"
                );
                $stmtNivel->execute([
                    ':idNivel' => (int)$data['idNivel'],
                    ':idEjercicio' => $idEjercicio
                ]);
            }

            // 3. Crear respuestas si existen
            if (!empty($data['respuestas']) && is_array($data['respuestas'])) {
                $stmtRespuesta = $pdo->prepare(
                    "INSERT INTO respuesta_ejercicio (idEjercicio, texto, esCorrecta) VALUES (:idEjercicio, :texto, :esCorrecta)"
                );

                foreach ($data['respuestas'] as $respuesta) {
                    if (empty($respuesta['texto'])) continue;

                    $esCorrecta = 0;
                    if (isset($respuesta['esCorrecta']) && $respuesta['esCorrecta']) {
                        $esCorrecta = 1;
                    } elseif (isset($respuesta['esCorrecto']) && $respuesta['esCorrecto']) {
                        $esCorrecta = 1;
                    }

                    $stmtRespuesta->execute([
                        ':idEjercicio' => $idEjercicio,
                        ':texto' => $respuesta['texto'],
                        ':esCorrecta' => $esCorrecta
                    ]);
                }
            }

            $pdo->commit();

            return [
                'status' => 'ok',
                'data' => $idEjercicio
            ];

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'status' => 'error',
                'error' => 'Error al crear ejercicio: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar un ejercicio completo con respuestas y nivel
     *
     * @param int $idEjercicio
     * @param array $data Puede contener: tipo, pregunta, idBloque, idNivel, respuestas
     * @return array
     */
    public function updateWithRespuestasAndNivel(int $idEjercicio, array $data): array
    {
        try {
            $pdo = Database::instance()->getConnection();
            $pdo->beginTransaction();

            // 1. Actualizar datos básicos del ejercicio
            $updateFields = [];
            $updateParams = [':id' => $idEjercicio];

            if (isset($data['tipo'])) {
                $updateFields[] = 'tipo = :tipo';
                $updateParams[':tipo'] = $data['tipo'];
            }
            if (isset($data['pregunta'])) {
                $updateFields[] = 'pregunta = :pregunta';
                $updateParams[':pregunta'] = $data['pregunta'];
            }
            if (isset($data['idBloque'])) {
                $updateFields[] = 'idBloque = :idBloque';
                $updateParams[':idBloque'] = $data['idBloque'];
            }

            if (!empty($updateFields)) {
                $sql = "UPDATE ejercicio SET " . implode(', ', $updateFields) . " WHERE idEjercicio = :id";
                $stmtUpdate = $pdo->prepare($sql);
                $stmtUpdate->execute($updateParams);
            }

            // 2. Actualizar relación nivel_ejercicio si se proporciona idNivel
            if (isset($data['idNivel'])) {
                // Eliminar relaciones anteriores
                $stmtDeleteNivel = $pdo->prepare("DELETE FROM nivel_ejercicio WHERE idEjercicio = :idEjercicio");
                $stmtDeleteNivel->execute([':idEjercicio' => $idEjercicio]);

                // Crear nueva relación si idNivel no es null
                if (!empty($data['idNivel'])) {
                    $stmtNivel = $pdo->prepare(
                        "INSERT INTO nivel_ejercicio (idNivel, idEjercicio) VALUES (:idNivel, :idEjercicio)"
                    );
                    $stmtNivel->execute([
                        ':idNivel' => (int)$data['idNivel'],
                        ':idEjercicio' => $idEjercicio
                    ]);
                }
            }

            // 3. Actualizar respuestas si se proporcionan
            if (isset($data['respuestas']) && is_array($data['respuestas'])) {
                // Eliminar respuestas anteriores
                $stmtDeleteResp = $pdo->prepare("DELETE FROM respuesta_ejercicio WHERE idEjercicio = :idEjercicio");
                $stmtDeleteResp->execute([':idEjercicio' => $idEjercicio]);

                // Crear nuevas respuestas
                $stmtRespuesta = $pdo->prepare(
                    "INSERT INTO respuesta_ejercicio (idEjercicio, texto, esCorrecta) VALUES (:idEjercicio, :texto, :esCorrecta)"
                );

                foreach ($data['respuestas'] as $respuesta) {
                    if (empty($respuesta['texto'])) continue;

                    $esCorrecta = 0;
                    if (isset($respuesta['esCorrecta']) && $respuesta['esCorrecta']) {
                        $esCorrecta = 1;
                    } elseif (isset($respuesta['esCorrecto']) && $respuesta['esCorrecto']) {
                        $esCorrecta = 1;
                    }

                    $stmtRespuesta->execute([
                        ':idEjercicio' => $idEjercicio,
                        ':texto' => $respuesta['texto'],
                        ':esCorrecta' => $esCorrecta
                    ]);
                }
            }

            $pdo->commit();

            return ['status' => 'ok'];

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'status' => 'error',
                'error' => 'Error al actualizar ejercicio: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar todos los ejercicios de un bloque con sus relaciones
     *
     * @param int $idBloque
     * @return array
     */
    public function deleteByBloqueWithRelations(int $idBloque): array
    {
        try {
            $pdo = Database::instance()->getConnection();
            $pdo->beginTransaction();

            // 1. Eliminar respuestas de usuarios para los ejercicios de este bloque
            $stmtDeleteRespUsuario = $pdo->prepare("
                DELETE ru FROM respuesta_usuario ru
                INNER JOIN ejercicio e ON ru.idEjercicio = e.idEjercicio
                WHERE e.idBloque = :idBloque
            ");
            $stmtDeleteRespUsuario->execute([':idBloque' => $idBloque]);

            // 2. Eliminar respuestas de ejercicios
            $stmtDeleteRespEjercicio = $pdo->prepare("
                DELETE re FROM respuesta_ejercicio re
                INNER JOIN ejercicio e ON re.idEjercicio = e.idEjercicio
                WHERE e.idBloque = :idBloque
            ");
            $stmtDeleteRespEjercicio->execute([':idBloque' => $idBloque]);

            // 3. Eliminar relaciones nivel_ejercicio
            $stmtDeleteNivelEjercicio = $pdo->prepare("
                DELETE ne FROM nivel_ejercicio ne
                INNER JOIN ejercicio e ON ne.idEjercicio = e.idEjercicio
                WHERE e.idBloque = :idBloque
            ");
            $stmtDeleteNivelEjercicio->execute([':idBloque' => $idBloque]);

            // 4. Eliminar los ejercicios
            $stmtDeleteEjercicios = $pdo->prepare("DELETE FROM ejercicio WHERE idBloque = :idBloque");
            $stmtDeleteEjercicios->execute([':idBloque' => $idBloque]);
            $ejerciciosEliminados = $stmtDeleteEjercicios->rowCount();

            $pdo->commit();

            return [
                'status' => 'ok',
                'ejerciciosEliminados' => $ejerciciosEliminados
            ];

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'status' => 'error',
                'error' => 'Error al eliminar ejercicios: ' . $e->getMessage()
            ];
        }
    }
}

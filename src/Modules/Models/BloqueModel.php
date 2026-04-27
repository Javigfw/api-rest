<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

/**
 * Modelo para la gestión de bloques educativos
 */
class BloqueModel extends Model
{
    protected string $table = 'bloque';
    protected string $primaryKey = 'idBloque';

    protected array $fillable = [
        'nombre',
        'descripcion',
        'finalidad'
    ];

    /**
     * Obtener bloques por nivel (Dado un idNivel, obtiene el bloque asociado)
     * Nota: En el nuevo esquema, Nivel es hijo de Bloque.
     *
     * @param int $nivelId idNivel
     * @return array
     */
    public function findByNivel(int $nivelId): array
    {
        // En el nuevo esquema, Nivel tiene idBloque.
        // Buscamos el bloque que corresponde a este nivel específico.
        $sql = "SELECT b.*, n.nivel as numero_nivel
                FROM {$this->table} b
                JOIN nivel n ON n.idBloque = b.idBloque
                WHERE n.idNivel = :nivel";

        $params = [
            'nivel' => [$nivelId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Obtener todos los bloques (con información extra si necesaria)
     *
     * @return array
     */
    public function findAllWithNivel(): array
    {
        // Como Nivel es hijo, un bloque puede tener varios niveles.
        // Aquí retornamos los bloques simples.
        return $this->findAll();
    }

    /**
     * Obtener bloque con sus ejercicios
     *
     * @param int $bloqueId
     * @return array
     */
    public function findWithEjercicios(int $bloqueId): array
    {
        $sql = "SELECT b.*
                FROM {$this->table} b
                WHERE b.idBloque = :bloque";

        $params = [
            'bloque' => [$bloqueId, PDO::PARAM_INT]
        ];

        $bloqueResult = $this->query($sql, $params);

        if ($bloqueResult['status'] !== 'ok' || empty($bloqueResult['data'])) {
            return [
                'status' => 'error',
                'error' => 'Bloque no encontrado'
            ];
        }

        $bloque = $bloqueResult['data'][0];

        // Obtener ejercicios del bloque
        $sqlEjercicios = "SELECT * FROM ejercicio WHERE idBloque = :bloque ORDER BY idEjercicio ASC";
        $ejercicios = $this->query($sqlEjercicios, $params);

        if ($ejercicios['status'] === 'ok') {
            $bloque['ejercicios'] = $ejercicios['data'];
        } else {
            $bloque['ejercicios'] = [];
        }

        return [
            'status' => 'ok',
            'data' => $bloque
        ];
    }

    /**
     * Obtener progreso del usuario en un bloque
     *
     * @param int $usuarioId
     * @param int|null $nivelId (Opcional, no usado directamente en filtro de bloque principal pero mantenido por compatibilidad)
     * @return array
     */
    public function findWithProgreso(int $usuarioId, ?int $nivelId = null): array
    {
        // progreso_bloque(idProgreso, idUsuario, idBloque, porcentajeCompletado)
        $sql = "SELECT b.*, 
                       pb.porcentajeCompletado
                FROM {$this->table} b
                LEFT JOIN progreso_bloque pb ON pb.idBloque = b.idBloque AND pb.idUsuario = :usuario
                WHERE 1=1";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ];

        // Si se pasa nivelId (idNivel), filtramos solo el bloque de ese nivel
        if ($nivelId !== null) {
            $sql .= " AND b.idBloque IN (SELECT idBloque FROM nivel WHERE idNivel = :nivel)";
            $params['nivel'] = [$nivelId, PDO::PARAM_INT];
        }

        return $this->query($sql, $params);
    }
    /**
     * Obtener bloques según el estado de suscripción del usuario
     *
     * @param int $usuarioId
     * @param bool $isSuscritoOrAdmin
     * @return array
     */
    public function findBloquesPorSuscripcion(int $usuarioId, bool $isSuscritoOrAdmin): array
    {
        if ($isSuscritoOrAdmin) {
            // Usuario SUSCRITO o ADMIN: todos los bloques + progreso + total ejercicios
            $sql = "SELECT 
                        b.idBloque, b.nombre, b.descripcion,
                        COALESCE(pb.porcentajeCompletado, 0) as porcentajeCompletado,
                        (SELECT COUNT(*) FROM ejercicio e WHERE e.idBloque = b.idBloque) as totalEjercicios
                    FROM {$this->table} AS b
                    LEFT JOIN progreso_bloque pb ON pb.idBloque = b.idBloque AND pb.idUsuario = :usuario
                    ORDER BY b.idBloque";
            $params = ['usuario' => [$usuarioId, PDO::PARAM_INT]];
        } else {
            // Usuario NO SUSCRITO: solo bloques desbloqueados y vigentes + progreso + total ejercicios
            $sql = "SELECT 
                        b.idBloque, b.nombre, b.descripcion, b.finalidad,
                        COALESCE(pb.porcentajeCompletado, 0) as porcentajeCompletado,
                        (SELECT COUNT(*) FROM ejercicio e WHERE e.idBloque = b.idBloque) as totalEjercicios
                    FROM bloque_usuario bu
                    JOIN {$this->table} b ON b.idBloque = bu.idBloque
                    LEFT JOIN progreso_bloque pb ON pb.idBloque = b.idBloque AND pb.idUsuario = :usuario
                    WHERE bu.idUsuario = :usuario
                    AND NOW() BETWEEN bu.fechaDesbloqueo AND bu.fechaFin
                    ORDER BY b.idBloque";
            $params = ['usuario' => [$usuarioId, PDO::PARAM_INT]];
        }

        return $this->query($sql, $params);
    }
    /**
     * Obtener contenido de todos los bloques con progreso calculado (conteo de ejercicios respondidos)
     *
     * @param int $usuarioId
     * @return array
     */
    public function findProgresoNivelesEjercicios(int $usuarioId): array
    {
        $sql = "SELECT
                    b.idBloque,
                    b.nombre,
                    b.descripcion,
                    COUNT(DISTINCT e.idEjercicio) AS totalTests,
                    COUNT(DISTINCT CASE WHEN ru.idUsuario = :usuario THEN ru.idEjercicio END) AS completados
                FROM {$this->table} b
                LEFT JOIN ejercicio e ON e.idBloque = b.idBloque
                LEFT JOIN respuesta_usuario ru ON ru.idEjercicio = e.idEjercicio
                GROUP BY b.idBloque, b.nombre, b.descripcion
                ORDER BY b.idBloque";

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Obtener niveles (tests) de un bloque específico con conteo de ejercicios
     *
     * @param int $idBloque
     * @return array
     */
    public function getNivelesByBloque(int $idBloque): array
    {
        $sql = "SELECT 
                    n.idNivel, 
                    n.nivel,
                    n.idBloque,
                    COUNT(ne.idEjercicio) as totalEjercicios
                FROM nivel n
                LEFT JOIN nivel_ejercicio ne ON ne.idNivel = n.idNivel
                WHERE n.idBloque = :idBloque
                GROUP BY n.idNivel, n.nivel, n.idBloque
                ORDER BY n.nivel ASC";

        $params = [
            'idBloque' => [$idBloque, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Buscar un bloque por su nombre exacto
     *
     * @param string $nombre
     * @return array
     */
    public function findByNombre(string $nombre): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE nombre = :nombre LIMIT 1";
        
        $params = [
            'nombre' => [$nombre, PDO::PARAM_STR]
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
            'error' => 'Bloque no encontrado'
        ];
    }

    /**
     * Crear un nuevo bloque con todos sus datos
     *
     * @param array $data ['nombre' => string, 'descripcion' => string|null, 'finalidad' => string|null]
     * @return array
     */
    public function createWithData(array $data): array
    {
        // Validar que al menos el nombre esté presente
        if (empty($data['nombre'])) {
            return [
                'status' => 'error',
                'error' => 'El nombre del bloque es requerido'
            ];
        }

        // Preparar datos para inserción
        $insertData = [
            'nombre' => trim($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'finalidad' => $data['finalidad'] ?? null
        ];

        // Usar el método create del modelo base
        $result = $this->create($insertData);

        if ($result['status'] === 'ok') {
            // Obtener el ID del bloque recién creado
            $lastId = $this->db->lastInsertId();
            return [
                'status' => 'ok',
                'data' => (int)$lastId
            ];
        }

        return [
            'status' => 'error',
            'error' => $result['error'] ?? 'Error al crear bloque'
        ];
    }
}


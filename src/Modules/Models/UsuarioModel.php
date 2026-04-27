<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use App\Helpers\Utils;
use PDO;

/**
 * Modelo para la gestión de usuarios
 */
class UsuarioModel extends Model
{
    protected string $table = 'usuario';
    protected string $primaryKey = 'idUsuario';

    protected array $fillable = [
        'nombre',
        'email',
        'password',
        'fechaRegistro',
        'esAdmin',
        'username',
        'url_imagen',
        'telefono'
    ];

    /**
     * Buscar usuario por email
     *
     * @param string $email
     * @return array
     */
    public function findByEmail(string $email): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $params = [
            'email' => [$email, PDO::PARAM_STR]
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
            'error' => 'Usuario no encontrado'
        ];
    }

    /**
     * Autenticar usuario (soporta email o username)
     *
     * @param string $login (email o username)
     * @param string $password
     * @return array
     */
    public function authenticate(string $login, string $password): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :login OR username = :login LIMIT 1";
        $params = [
            'login' => [$login, PDO::PARAM_STR]
        ];

        $result = $this->query($sql, $params);

        if ($result['status'] !== 'ok' || empty($result['data'])) {
            return [
                'status' => 'error',
                'error' => 'Credenciales inválidas'
            ];
        }

        $usuario = $result['data'][0];

        // Verificar contraseña
        if (password_verify($password, $usuario['password'])) {
            // Ocultar contraseña antes de devolver
            unset($usuario['password']);

            return [
                'status' => 'ok',
                'data' => $usuario
            ];
        }

        return [
            'status' => 'error',
            'error' => 'Credenciales inválidas'
        ];
    }

    /**
     * Crear usuario con contraseña hasheada
     *
     * @param array $data
     * @return array
     */
    public function createUser(array $data): array
    {
        // Hashear contraseña si existe
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->create($data);
    }

    /**
     * Actualizar perfil de usuario
     *
     * @param int $id
     * @param array $data
     * @return array
     */
    public function updateProfile(int $id, array $data): array
    {
        // Si se incluye contraseña, hashearla
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // No actualizar contraseña si está vacía
            unset($data['password']);
        }

        return $this->update($id, $data);
    }

    /**
     * Actualizar imagen de perfil
     *
     * @param int $id
     * @param string $imageBase64
     * @param string $mimeType
     * @return array
     */
    public function updateProfileImage(int $id, string $imageBase64, string $mimeType): array
    {
        try {
            // Nota: API_BASE_PATH y IMAGE_SIZE_SMALL deben estar definidos en constantes globales o config
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . (defined('API_BASE_PATH') ? API_BASE_PATH : '') . '/imagenes/usuario/';

            // Procesar y guardar imagen (función auxiliar)
            $filename = Utils::processAndSaveImages($imageBase64, $mimeType, $imagePath, $id, defined('IMAGE_SIZE_SMALL') ? IMAGE_SIZE_SMALL : 800);

            // Actualizar URL en base de datos
            // Asumiendo que Utils retorna el nombre del archivo o void. Si retorna void, construimos el nombre.
            // Ajustamos el update para guardar 'url_imagen'
            return $this->update($id, ['url_imagen' => $filename]);

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }


    /**
     * Obtener progreso del usuario en bloques
     *
     * @param int $usuarioId
     * @return array
     */
    public function getProgreso(int $usuarioId): array
    {
        // Ajustado a esquema: progreso_bloque(idProgreso, idUsuario, idBloque), bloque(idBloque)
        $sql = "SELECT pb.*, b.nombre as bloque_nombre, b.descripcion as bloque_descripcion
                FROM progreso_bloque pb
                JOIN bloque b ON b.idBloque = pb.idBloque
                WHERE pb.idUsuario = :usuario
                ORDER BY pb.idProgreso DESC"; // No hay fecha_inicio en SQL script para progreso_bloque

        $params = [
            'usuario' => [$usuarioId, PDO::PARAM_INT]
        ];

        return $this->query($sql, $params);
    }

    /**
     * Obtener estadísticas del usuario
     * Tests completados, bloques completados, mensajes foro, % aciertos, bloques desbloqueados
     *
     * @param int $idUsuario
     * @return array
     */
    public function getEstadisticas(int $idUsuario): array
    {
        try {
            // 1. Estadísticas principales
            $sqlMain = "SELECT
                            (SELECT COUNT(DISTINCT ru2.fecha)
                             FROM respuesta_usuario ru2
                             WHERE ru2.idUsuario = :idUsuario1) AS testCompletados,
                            
                            (SELECT COUNT(*)
                             FROM progreso_bloque pb
                             WHERE pb.idUsuario = :idUsuario2 AND pb.porcentajeCompletado = 100) AS bloquesCompletados,
                            
                            (SELECT COUNT(*)
                             FROM mensaje_foro mf
                             WHERE mf.idUsuario = :idUsuario3) AS mensajesForo,
                            
                            COALESCE(ROUND(100 * AVG(ru.esCorrecta), 0), 0) AS porcentajeGlobal
                        FROM respuesta_usuario ru
                        WHERE ru.idUsuario = :idUsuario4";

            $paramsMain = [
                'idUsuario1' => [$idUsuario, PDO::PARAM_INT],
                'idUsuario2' => [$idUsuario, PDO::PARAM_INT],
                'idUsuario3' => [$idUsuario, PDO::PARAM_INT],
                'idUsuario4' => [$idUsuario, PDO::PARAM_INT]
            ];

            $mainStatsResult = $this->query($sqlMain, $paramsMain);

            if ($mainStatsResult['status'] !== 'ok' || empty($mainStatsResult['data'])) {
                $stats = [
                    'testCompletados' => 0,
                    'bloquesCompletados' => 0,
                    'mensajesForo' => 0,
                    'porcentajeGlobal' => 0
                ];
            } else {
                $stats = $mainStatsResult['data'][0];
            }

            // 2. Bloques desbloqueados (activos en este momento)
            $sqlBloques = "SELECT COUNT(DISTINCT bu.idBloque) AS bloquesDesbloqueados
                           FROM bloque_usuario bu
                           WHERE bu.idUsuario = :idUsuario
                           AND NOW() BETWEEN bu.fechaDesbloqueo AND bu.fechaFin";

            $paramsId = ['idUsuario' => [$idUsuario, PDO::PARAM_INT]];
            $bloquesResult = $this->query($sqlBloques, $paramsId);
            $bloquesDesbloqueados = ($bloquesResult['status'] === 'ok' && !empty($bloquesResult['data'])) ? $bloquesResult['data'][0]['bloquesDesbloqueados'] : 0;

            // 3. Total de bloques en el sistema
            $sqlTotalSistema = "SELECT COUNT(*) as total FROM bloque";
            $totalSistemaResult = $this->query($sqlTotalSistema);
            $totalBloquesSistema = ($totalSistemaResult['status'] === 'ok' && !empty($totalSistemaResult['data'])) ? $totalSistemaResult['data'][0]['total'] : 0;

            // 4. Total de bloques asignados al usuario (activos)
            $sqlTotalUser = "SELECT COUNT(*) as total 
                             FROM bloque_usuario 
                             WHERE idUsuario = :idUsuario 
                             AND NOW() BETWEEN fechaDesbloqueo AND fechaFin";
            $totalUserResult = $this->query($sqlTotalUser, $paramsId);
            $totalBloquesUsuario = ($totalUserResult['status'] === 'ok' && !empty($totalUserResult['data'])) ? $totalUserResult['data'][0]['total'] : 0;

            return [
                'status' => 'ok',
                'data' => [
                    'testCompletados' => (int) $stats['testCompletados'],
                    'bloquesCompletados' => (int) $stats['bloquesCompletados'],
                    'mensajesForo' => (int) $stats['mensajesForo'],
                    'porcentajeGlobal' => (int) $stats['porcentajeGlobal'],
                    'bloquesDesbloqueados' => (int) $bloquesDesbloqueados,
                    'totalBloquesSistema' => (int) $totalBloquesSistema,
                    'totalBloquesUsuario' => (int) $totalBloquesUsuario
                ]
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Buscar usuario por username o por nombre formateado (sin espacios, minúsculas)
     *
     * @param string $username
     * @return array
     */
    public function findByUsernameOrFormattedName(string $username): array
    {
        $sql = "SELECT idUsuario, nombre, username, email, fechaRegistro, url_imagen
                FROM {$this->table}
                WHERE username = :username1 
                   OR (:username2 = LOWER(REPLACE(nombre, ' ', '')))
                LIMIT 1";

        $params = [
            'username1' => [$username, PDO::PARAM_STR],
            'username2' => [$username, PDO::PARAM_STR]
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
            'error' => 'Usuario no encontrado'
        ];
    }

    /**
     * Buscar usuarios (Admin)
     *
     * @param string $search Búsqueda por nombre o email
     * @param string $type Tipo de usuario: all, admin, candidate
     * @param int $limit Límite de resultados
     * @return array
     */
    public function buscarUsuarios(string $search = '', string $type = 'all', int $limit = 50): array
    {
        $sql = "SELECT idUsuario, nombre, email, fechaRegistro, esAdmin, username, url_imagen 
                FROM {$this->table} 
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (nombre LIKE :search OR email LIKE :search OR username LIKE :search)";
            $params['search'] = ["%$search%", PDO::PARAM_STR];
        }

        if ($type === 'admin') {
            $sql .= " AND esAdmin = 1";
        } elseif ($type === 'candidate') {
            $sql .= " AND esAdmin = 0";
        }

        $sql .= " ORDER BY nombre ASC LIMIT :limit";
        $params['limit'] = [$limit, PDO::PARAM_INT];

        $result = $this->query($sql, $params);

        if ($result['status'] === 'ok') {
            foreach ($result['data'] as &$u) {
                $u['esAdmin'] = (bool) $u['esAdmin'];
            }
        }

        return $result;
    }
}

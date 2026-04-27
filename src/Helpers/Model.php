<?php

namespace App\Helpers;

use PDO;
use Exception;

/**
 * Clase base Model para encapsular lógica de acceso a datos
 * Todos los modelos específicos deben extender de esta clase
 */
abstract class Model
{
    /**
     * Nombre de la tabla en la base de datos
     * Debe ser definido por cada modelo hijo
     */
    protected string $table;

    /**
     * Nombre de la clave primaria
     * Por defecto es 'codigo', pero puede ser sobrescrito
     */
    protected string $primaryKey = 'codigo';

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected array $fillable = [];

    /**
     * Conexión a la base de datos
     */
    protected PDO $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener todos los registros de la tabla
     *
     * @param array $conditions Condiciones WHERE opcionales ['campo' => 'valor']
     * @param string $orderBy Ordenamiento opcional
     * @return array
     */
    public function findAll(array $conditions = [], string $orderBy = ''): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $params = [];

            if (!empty($conditions)) {
                $whereClauses = [];
                foreach ($conditions as $field => $value) {
                    $whereClauses[] = "$field = :$field";
                    $params[$field] = [$value, $this->getPdoType($value)];
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }

            if ($orderBy) {
                $sql .= " ORDER BY $orderBy";
            }

            return Queries::listar($sql, $params);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar un registro por su ID
     *
     * @param mixed $id
     * @return array
     */
    public function find($id): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $params = [
                'id' => [$id, $this->getPdoType($id)]
            ];

            $result = Queries::listar($sql, $params);
            
            if ($result['status'] == 'ok' && !empty($result['data'])) {
                return [
                    'status' => 'ok',
                    'data' => $result['data'][0]
                ];
            }

            return [
                'status' => 'error',
                'error' => 'Registro no encontrado'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear un nuevo registro
     *
     * @param array $data Datos del registro ['campo' => 'valor']
     * @return array
     */
    public function create(array $data): array
    {
        try {
            // Filtrar solo los campos permitidos
            $data = $this->filterFillable($data);

            $fields = array_keys($data);
            $placeholders = array_map(fn($field) => ":$field", $fields);

            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";

            $params = [];
            foreach ($data as $field => $value) {
                $params[$field] = [$value, $this->getPdoType($value)];
            }

            return Queries::crear($sql, $params);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar un registro existente
     *
     * @param mixed $id ID del registro
     * @param array $data Datos a actualizar
     * @return array
     */
    public function update($id, array $data): array
    {
        try {
            // Filtrar solo los campos permitidos
            $data = $this->filterFillable($data);

            $setClauses = [];
            foreach (array_keys($data) as $field) {
                $setClauses[] = "$field = :$field";
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . 
                   " WHERE {$this->primaryKey} = :id";

            $params = [
                'id' => [$id, $this->getPdoType($id)]
            ];

            foreach ($data as $field => $value) {
                $params[$field] = [$value, $this->getPdoType($value)];
            }

            return Queries::actualizar($sql, $params);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar un registro
     *
     * @param mixed $id ID del registro
     * @return array
     */
    public function delete($id): array
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $params = [
                'id' => [$id, $this->getPdoType($id)]
            ];

            return Queries::borrar($sql, $params);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ejecutar una consulta SQL personalizada
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros
     * @return array
     */
    public function query(string $sql, array $params = []): array
    {
        try {
            return Queries::listar($sql, $params);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Filtrar datos para incluir solo campos permitidos
     *
     * @param array $data
     * @return array
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Determinar el tipo PDO basado en el valor
     *
     * @param mixed $value
     * @return int
     */
    protected function getPdoType($value): int
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            return PDO::PARAM_BOOL;
        } elseif (is_null($value)) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }

    /**
     * Validar datos requeridos
     *
     * @param array $required Campos requeridos
     * @param array $data Datos a validar
     * @return string Mensaje de error o cadena vacía si todo es válido
     */
    protected function validateRequired(array $required, array $data): string
    {
        return Utils::requiredParams($required, $data);
    }

    /**
     * Obtener la conexión a la base de datos
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }
}

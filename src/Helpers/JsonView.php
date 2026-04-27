<?php

namespace App\Helpers;

/**
 * Clase para renderizar vistas JSON
 * En una API REST, las "vistas" son las representaciones JSON de los recursos
 */
class JsonView
{
    /**
     * Renderizar una colección de recursos
     *
     * @param array $items Items a renderizar
     * @param callable|null $transformer Función para transformar cada item
     * @return array
     */
    public static function collection(array $items, ?callable $transformer = null): array
    {
        if ($transformer === null) {
            return $items;
        }

        return array_map($transformer, $items);
    }

    /**
     * Renderizar un único recurso
     *
     * @param array $item Item a renderizar
     * @param callable|null $transformer Función para transformar el item
     * @return array
     */
    public static function item(array $item, ?callable $transformer = null): array
    {
        if ($transformer === null) {
            return $item;
        }

        return $transformer($item);
    }

    /**
     * Ocultar campos sensibles de un array
     *
     * @param array $data Datos originales
     * @param array $hiddenFields Campos a ocultar
     * @return array
     */
    public static function hideFields(array $data, array $hiddenFields): array
    {
        foreach ($hiddenFields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Seleccionar solo ciertos campos de un array
     *
     * @param array $data Datos originales
     * @param array $fields Campos a incluir
     * @return array
     */
    public static function only(array $data, array $fields): array
    {
        return array_intersect_key($data, array_flip($fields));
    }

    /**
     * Transformador para Usuario (oculta campos sensibles)
     *
     * @param array $usuario
     * @return array
     */
    public static function transformUsuario(array $usuario): array
    {
        return self::hideFields($usuario, ['contrasena', 'password', 'token']);
    }

    /**
     * Transformador para lista de usuarios
     *
     * @param array $usuarios
     * @return array
     */
    public static function transformUsuarios(array $usuarios): array
    {
        return self::collection($usuarios, [self::class, 'transformUsuario']);
    }
}

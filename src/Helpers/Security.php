<?php

namespace App\Helpers;

/**
 * Description of Security
 *
 * @author TPV
 */
class Security
{

    //*************************************************************************
    //* DECODIFICAR CONTRASEÑA BASE 64
    //*************************************************************************
    public static function decodeBase64(string $pass): string
    {
        // ciframos
        $contrasena = base64_decode($pass);
        // retornamos la contraseña
        return $contrasena;
    }

    //*************************************************************************
    //* CODIFICAR CONTRASEÑA BASE 64
    //*************************************************************************
    public static function encodeBase64(string $pass): string
    {
        // ciframos
        $contrasena = base64_encode($pass);
        // retornamos la contraseña
        return $contrasena;
    }


    //*************************************************************************
    //* CODIFICAR CONTRASEÑA sha512
    //*************************************************************************
    public static function encodeSHA512(string $pass): string
    {
        // codificamos la respuesta
        $contrasena = hash('sha512', $pass, false);
        // retornamos la contraseña
        return $contrasena;
    }

    /**
     * Comprueba si el verbo HTTP utilizado en la petición está permitido por el
     * método actual.
     * 
     * @param string $verbo Cadena con le verbo HTTP permitido.
     * @return void
     */
    public static function comprobarVerboHttp($verbo): void
    {
        if ($verbo !== $_SERVER['REQUEST_METHOD']) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['estado' => 'error', 'datos' => 'Verbo HTTP no permitido']);
            exit();
        }
    }


    /**
     * Obtiene los datos pasados por POST y los filtra, devolviendolos en un array.
     * 
     * @param array $datos_post
     * @return array
     */
    public static function filtrarDatos(string $tipo, array $datos_post): array
    {
        $tipo_input = '';
        $datos = [];
        switch ($tipo) {
            case 'GET':
                $tipo_input = INPUT_GET;
                break;
            default:
                $tipo_input = INPUT_POST;
                break;
        }
        foreach ($datos_post as $clave => $valor) {
            switch ($clave) {
                case 'email':
                    $datos[$clave] = filter_input($tipo_input, $clave, FILTER_SANITIZE_EMAIL);
                    break;
                default:
                    $datos[$clave] = filter_input($tipo_input, $clave, FILTER_SANITIZE_STRING);
                    break;
            }
        }
        return $datos;
    }
    private static function Aud()
    {
        $aud = '';
        $aud .= $_SERVER['REMOTE_ADDR'];
        $aud .= gethostname();

        return sha1($aud);
    }
}

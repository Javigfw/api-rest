<?php

namespace App\Helpers;

class Logger
{

    /**
     * Crea mensajes de registro en un archivo.
     *      
     * @param integer $local Código del local, 0 si no hay
     * @param integer $dispostivo Código del dispositivo, 0 si no hay
     * @param string $mensaje El mensaje a almacenar en el archivo.
     * @param bool $error = true Si es mensaje de error/acceso
     */
    public static function log($local, $dispositivo, $texto, $error = true)
    {
        // Definimos una cadena vacía                                 
        $cadena = '';
        // Obtenemos la fecha del evento ocurrido  
        $mensaje['event_datetime'] = '[' . date('h:i:s A') . '] [' . $_SERVER['REMOTE_ADDR'] . '] [' . str_pad($local, 3, '0', STR_PAD_LEFT) . '] [' . str_pad($dispositivo, 2, '0', STR_PAD_LEFT) . ']';
        // Si el mensaje es de tipo array ...  
        if (is_array($texto)) {
            // Concatenamos los mensajes con el datetime  
            foreach ($texto as $txt) {
                $cadena .= $mensaje['event_datetime'] . ' ' . $txt . "\r\n";
            }
        } else {
            // Concatenamos el mensaje con el datetime  
            $cadena .= $mensaje['event_datetime'] . ' ' . $texto . "\r\n";
        }
        // Creamos el archivo con la fecha actual  
        if ($error) {
            $archivo = 'Logs/log_error_' . date('Ymd') . '.log';
        } else {
            $archivo = 'Logs/log_acceso_' . date('Ymd') . '.log';
        }
        // Abrimos el archivo en el modo append
        $manejador = fopen($archivo, 'a+');
        // Escribimos la información en el archivo  
        fwrite($manejador, $cadena);
        // Cerramos el archivo
        fclose($manejador);
    }
}

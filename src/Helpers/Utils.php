<?php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use PDO;
use PDOException;
use Exception;

error_reporting(0);

class Utils
{
    /**
     * Function que devuelve una respuesta sin status
     * 
     * @param Response $response    Objeto Response a modificar     
     * @param mixed $data           Datos de la respuesta.
     * @param int $code             Código del estado de la respuesta, por defecto `200`
     * 
     * @return Response             Respuesta modificada
     */
    public static function responseJson(Response $response, $data, int $code = 200): Response
    {
        $payload = json_encode($data, JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    }


    /**
     * Function que devuelve una respuesta correcta
     * 
     * @param Response $response    Objeto Response a modificar     
     * @param mixed $data           Datos de la respuesta.
     * @param int $code             Código del estado de la respuesta, por defecto `200`
     * 
     * @return Response             Respuesta modificada
     */
    public static function responseJsonOk(Response $response, mixed $data, int $code = 200): Response
    {
        $data = ['status' => 'ok', 'data' => $data];
        $payload = json_encode($data, JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    }


    /**
     * Function que devuelve una respuesta en caso de error
     * 
     * @param Response $response    Objeto Response a modificar
     * @param string $message       Mensaje de error
     * @param mixed $data           Datos de la respuesta. Por defecto `''`
     * @param int $code             Código del estado de la respuesta, por defecto `401`
     * 
     * @return Response             Respuesta modificada
     */
    public static function responseJsonError(Response $response, string $message, $data = '', int $code = 401): Response
    {
        $respuesta = ['status' => 'error', 'error' => $message];
        if ($data != '') {
            $respuesta['data'] = $data;
        }
        $payload = json_encode($respuesta, JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    }


    /**
     * Function para saber si una variable existe o no.
     * 
     * @param mixed $variable       Variable a comprobar si existe.
     * @param mixed $defecto        Valor por defecto en caso de que se nula. Por defectu `null`
     * 
     * @return mixed                 Devuelve true|false dependiendo de si existe
     */
    public static function existeVariable($variable, $defecto = null): mixed
    {
        return isset($variable) ? $variable : $defecto;
    }

    /**
     * Método que registra en un fichero un texto
     * 
     * @param string $texto             Texto a introducir en el fichero
     * @param string $fileName          Nombre del fichero antes de añadirle la fecha
     * @param int $tipoFileName         Tipo de nombre para el fichero (por defecto `0`)
     *                                      `0`: fichero20231231235959.json (año mes dia hora minutos segundos)
     *                                      `1`: fichero20231231.json (año mes dia)
     *                                      `2`: fichero.json
     * @param string $extension         Extensión para el fichero (por defecto `.json`)
     * 
     * @return void     
     */


    public static function lineaLog(string $texto, string $fileName = '', int $tipo = 0, string $extension = '.json')
    {
        $dir = __DIR__ . '/../../logs';

        if (!file_exists($dir))
            mkdir($dir);

        $nombre = $dir . '/' . $fileName;
        if ($tipo == 0) {
            $nombre .= date('YmdHis');
        } elseif ($tipo == 1) {
            $nombre .= date('Ymd');
        }
        $nombre .=  $extension;

        $stream = fopen($nombre, "w");
        fwrite($stream, $texto);
        fclose($stream);
    }

    /**
     * Método que detiene la ejecución si falta algún parámetro
     * @param array $required       Lista de parámetros requeridos
     * @param array $params         Parámetros que comprobar
     * 
     * @return string               Devuelmve mensaje de error si falta algún parámetro
     *                              Si es correcto devuelve cadena vacía
     */
    public static function requiredParams(array $required, mixed $params): string
    {
        foreach ($required as $value) {
            if (!isset($params[$value]) || $params[$value] === null) {
                return "Parámetro '$value' requerido";
            }
        }
        return '';
    }



    /**
     * Function para comprobar el ApiKey y la clave
     * 
     * @param String $apiKey    numero del api
     * @param String $clave     clave del api
     * 
     * @return $datos[]         retorna 0 si no conincide y 1 si es valida
     */
    public static function comprobarApiClave($apiKey, $clave)
    {
        $sql = "SELECT COUNT(*) as cantidad FROM DISPOSITIVO WHERE api_key = :api_Key and clave = :clave";
        $datos = Queries::leer($sql, [
            'api_Key' => [$apiKey, PDO::PARAM_STR],
            'clave' => [$clave, PDO::PARAM_STR]
        ]);

        return $datos['data']['cantidad'];
    }


    /**
     * obtenerIdFamiliaPorNombre
     *
     * @param  mixed $nombreFamilia
     * @return Response
     */
    public static function obtenerIdFamiliaPorNombre($nombreFamilia)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM pl_familia WHERE nombre = :nombreFamilia ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombreFamilia' => [$nombreFamilia, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }


    /**
     * obtenerIdSubfamiliaPorNombre
     *
     * @param  mixed $nombreSubfamilia
     * @return void
     */
    public static function obtenerIdSubfamiliaPorNombre($nombreSubfamilia)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM pl_subfamilia WHERE nombre = :nombreSubfamilia ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombreSubfamilia' => [$nombreSubfamilia, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }



    /**
     * obtenerIdMarcaPorNombre
     *
     * @param  mixed $nombreMarca
     * @return void
     */
    public static function obtenerIdMarcaPorNombre($nombreMarca)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM pl_marca WHERE nombre = :nombreMarca ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombreMarca' => [$nombreMarca, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }


    /**
     * obtenerIdIvaPorNombre
     *
     * @param  mixed $nombreIva
     * @return void
     */
    public static function obtenerIdIvaPorNombre($nombreIva)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM pl_iva WHERE nombre = :nombreIva ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombreIva' => [$nombreIva, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }


    /**
     * obtenerIdPantallaPorNombre
     *
     * @param  mixed $nombrePantalla
     * @return void
     */
    public static function obtenerIdPantallaPorNombre($nombrePantalla)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM pl_pantalla WHERE nombre = :nombrePantalla ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'nombrePantalla' => [$nombrePantalla, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }

    public static function obtenerIdUsuario($usuario)
    {
        // generamos la consulta
        $sql = "SELECT codigo FROM usuario WHERE usuario = :usuario ";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, [
            'usuario' => [$usuario, PDO::PARAM_STR]
        ]);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }


    /**
     * Function para convertir las horas a minutos
     * 
     * @param int $numero     horas a convertir
     * 
     * @return String         retorna un string con las horas convertidas
     */
    public static function convertirHHMM($numero)
    {
        // Transformamos el número en float
        $numero = floatval($numero);
        // Obtenemos su parte entera
        $hrs = intval($numero);
        // Restamos la parte entera obtenida al número, multiplcamos por 60 para
        // obtener los minutos y redondeamos por si salen decimales
        $min = round(($numero - $hrs) * 60);
        if ($min == 60) {
            $min = 0;
            $hrs = $hrs + 1;
        }
        // Devolvemos la cadena correctamente formateada
        return str_pad($hrs, 2, '0', STR_PAD_LEFT) . ':' . str_pad($min, 2, '0', STR_PAD_LEFT);
    }



    /**
     * Function para extraer errores propios de la base de datos
     *    
     * @param String $cadena      texto del error
     * 
     * @return String             retorno el error
     */
    public static function extraerError($cadena)
    {
        $array = explode('$$$$$', $cadena);
        if (sizeof($array) >= 3) {
            return 'ERROR: ' . $array[1];
        } else {
            return $cadena;
        }
    }


    /**
     * comprobarFormatoImagen
     *
     * @param  mixed $image_data
     * @return void
     */
    public static function comprobarFormatoImagen($image_data)
    {
        if (substr($image_data, 0, 3) == "\xff\xd8\xff") {
            return 'image/jpeg';
        } elseif (substr($image_data, 0, 4) == "\x89PNG") {
            return 'image/png';
        } elseif (substr($image_data, 0, 6) == "GIF87a" || substr($image_data, 0, 6) == "GIF89a") {
            return 'image/gif';
        } else {
            return false;
        }
    }

    /**
     * obtenerUltimoAutoIncrement
     *
     * @return int
     */
    public static function obtenerUltimoAutoIncrement(): int
    {
        // generamos la consulta
        $sql = "SELECT LAST_INSERT_ID() as codigo";
        // ejecutamos la consulta
        $datos = Queries::leer($sql, []);
        // devolvemos el resultado
        return $datos['data']['codigo'];
    }

    /**
     * Decodifica una imagen base64, comprueba su extensión, la convierte a JPG si es necesario y la guarda con el nuevo nombre.
     * 
     * @param string $base64Image La imagen en base64.
     * @param string $newName El nuevo nombre para la imagen (sin extensión).
     * @param string $outputDir El directorio de salida donde guardar la imagen.
     * @return array ['status'=>bool, 'msg'=>string] Retorna true si la operación fue exitosa, false en caso contrario.
     */

    public static function procesarImagen($base64Image, $newName, $outputDir): array
    {
        $respuesta =  ['status' => false, 'msg' => ""];
        // Comprobar si la cadena base64 no es null y no está vacía
        if (!is_null($base64Image) && !empty($base64Image)) {
            // Decodificar la cadena base64
            $image_data = base64_decode($base64Image);

            // Comprobar si la decodificación fue exitosa
            if ($image_data !== false) {
                // Detectar el formato de la imagen
                $mime_type = Utils::comprobarFormatoImagen($image_data);

                // Asignar la extensión basada en el tipo MIME
                $extension = 'jpg'; // Por defecto
                if ($mime_type === 'image/jpeg') {
                    $extension = 'jpg';
                } elseif ($mime_type === 'image/png') {
                    $extension = 'png';
                } elseif ($mime_type === 'image/gif') {
                    $extension = 'gif';
                }

                // Especificar la ruta completa para guardar la imagen
                $directory = rtrim($outputDir, '/') . '/';

                // Verificar si la carpeta existe, si no, crearla
                if (!file_exists($directory)) {
                    if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                        $respuesta['msg'] = "Error al crear el directorio.";
                        return $respuesta;
                    }
                }

                // Nombre del archivo con la extensión apropiada
                $file_name = $newName . '.' . $extension;
                $file_path = $directory . $file_name;

                // Guardar la imagen directamente en la ubicación deseada
                if (file_put_contents($file_path, $image_data) !== false) {
                    // Si es PNG o GIF, convertirla a JPG
                    if ($extension !== 'jpg') {
                        $image = imagecreatefromstring($image_data);
                        if ($image !== false) {
                            $jpg_file = $directory . $newName . '.jpg';
                            imagejpeg($image, $jpg_file);
                            imagedestroy($image); // Liberar memoria
                            unlink($file_path); // Eliminar el archivo original si es necesario

                            $respuesta['status'] = true;
                            $respuesta['msg'] = "Imagen convertida a JPG y guardada como $jpg_file";
                        } else {
                            $respuesta['msg'] = "Error al crear la imagen desde los datos decodificados.";
                        }
                    } else {

                        $respuesta['status'] = true;
                        $respuesta['msg'] = "Imagen decodificada y guardada como $file_path";
                    }
                } else {
                    $respuesta['msg'] = "Error al guardar la imagen en $file_path.";
                }
            } else {
                $respuesta['msg'] = "Error al decodificar la cadena base64.";
            }
        } else {
            $respuesta['msg'] = "No se proporcionó una cadena base64 válida.";
        }
        return $respuesta;
    }


    /**
     * obtenerImagenArticulo
     *
     * @param  mixed $id
     * @param  mixed $tipo
     * @return mixed
     */
    public static function obtenerImagenArticulo(int $id, int $tipo = 0): mixed
    {
        // Definir las rutas para las imágenes pequeña y grande
        $rutaP = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/p/' . $id . '.jpg';
        $rutaG = $_SERVER['DOCUMENT_ROOT'] . API_BASE_PATH . '/imagenes/articulo/g/' . $id . '.jpg';

        // Inicializar las variables de contenido
        $contenidoP = '';
        $contenidoG = '';

        // Verificar si se debe incluir la imagen pequeña y si existe
        if (($tipo == 0 || $tipo == 1) && file_exists($rutaP)) {
            $contenidoP = base64_encode(file_get_contents($rutaP));
        }

        // Verificar si se debe incluir la imagen grande y si existe
        if (($tipo == 0 || $tipo == 2) && file_exists($rutaG)) {
            $contenidoG = base64_encode(file_get_contents($rutaG));
        }

        // Retornar la imagen o las imágenes según el tipo
        if ($tipo == 0) {
            return [
                'p' => $contenidoP,
                'g' => $contenidoG
            ];
        } elseif ($tipo == 1) {
            return $contenidoP;
        } elseif ($tipo == 2) {
            return $contenidoG;
        } else {
            return '';
        }
    }



    /**
     * convertToJpeg
     *
     * @param  mixed $base64Image
     * @return void
     */
    public static function convertToJpeg($base64Image)
    {
        // Decodificar Base64 y obtener los datos binarios de la imagen
        $imageData = base64_decode(explode(',', $base64Image)[1]);

        if ($imageData === false) {
            throw new Exception('Error al decodificar la imagen Base64.');
        }
        // Crear una imagen desde los datos binarios
        $image = imagecreatefromstring($imageData);

        if ($image === false) {
            throw new Exception('No se pudo crear la imagen desde los datos proporcionados.');
        }

        // Obtener la información de la imagen para determinar su tipo
        $imageInfo = getimagesizefromstring($imageData);
        $mimeType = $imageInfo['mime'];

        // Capturar la salida en un buffer
        ob_start();
        if ($mimeType === 'image/jpeg') {
            // Si ya es JPEG, simplemente devolver los datos tal cual
            imagejpeg($image);
        } else {
            // Convertir la imagen a JPEG
            imagejpeg($image);
        }
        $jpegData = ob_get_clean();
        imagedestroy($image);

        return $jpegData;
    }

    /**
     * resizeImageBase64
     *
     * @param  mixed $base64String
     * @param  mixed $desiredWidth
     * @return void
     */
    /**
     * Redimensiona una imagen binaria y devuelve la imagen redimensionada en formato binario.
     *
     * @param string $imageData Los datos binarios de la imagen.
     * @param int $desiredWidth El ancho deseado para la imagen redimensionada.
     * @return string Los datos binarios de la imagen redimensionada en formato JPEG.
     * @throws Exception Si ocurre algún error durante el procesamiento.
     */
    public static function resizeImageBinary($imageData, $desiredWidth)
    {
        // Crear una imagen desde los datos binarios
        $sourceImage = imagecreatefromstring($imageData);

        if ($sourceImage === false) {
            throw new Exception('No se pudo crear la imagen desde los datos proporcionados.');
        }
        // Obtener las dimensiones originales de la imagen
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Si la anchura original es menor o igual a la anchura deseada, no redimensionar
        if ($originalWidth <= $desiredWidth) {
            imagedestroy($sourceImage);
            return $imageData;
        }

        // Calcular la nueva altura manteniendo la relación de aspecto
        $ratio = $originalHeight / $originalWidth;
        $newHeight = $desiredWidth * $ratio;

        // Crear una nueva imagen con las dimensiones redimensionadas
        $newImage = imagecreatetruecolor($desiredWidth, $newHeight);

        // Configurar el color de fondo a blanco para evitar imágenes con fondo transparente
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);

        // Copiar y redimensionar la imagen original en la nueva imagen
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $desiredWidth, $newHeight, $originalWidth, $originalHeight);

        // Capturar la imagen redimensionada en un buffer
        ob_start();
        imagejpeg($newImage, null, 90);
        $resizedImageData = ob_get_contents();
        ob_end_clean();

        // Liberar memoria
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $resizedImageData;
    }


    /**
     * Guarda una imagen binaria en una ruta específica del servidor.
     *
     * @param string $imageData Los datos binarios de la imagen.
     * @param string $filePath La ruta completa en el servidor donde se debe guardar la imagen.
     * @return bool Retorna true si la imagen se guardó correctamente, false en caso contrario.
     * @throws Exception Si ocurre algún error durante el proceso.
     */
    public static function saveBinaryImageToFile($imageData, $filePath, $fileName)
    {
        // Verifica que los datos de la imagen no estén vacíos
        if (empty($imageData)) {
            throw new Exception('Los datos de la imagen están vacíos.');
        }

        // Verifica que el directorio de destino exista y tenga permisos de escritura
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            throw new Exception('El directorio de destino no existe.');
        }

        if (!is_writable($directory)) {
            throw new Exception('El directorio de destino no tiene permisos de escritura.');
        }

        // Intenta guardar la imagen en la ruta especificada
        if (file_put_contents($filePath . $fileName, $imageData) === false) {
            throw new Exception('Error al guardar la imagen en el archivo.');
        }

        return true;
    }

    /**
     * Procesa y guarda una imagen en el servidor.
     *
     * @param array $params Array con parámetros necesarios:
     *                      - 'imagenP': Imagen en base64
     *                      - 'mimeP': Tipo MIME de la imagen
     * @param string $basePath Ruta base para guardar la imagen
     * @param int $imageSize Tamaño deseado para redimensionar la imagen
     * @return bool Retorna true si la imagen se guardó correctamente, false en caso contrario
     * @throws Exception Si ocurre un error durante el proceso
     */
    public static function processAndSaveImages($imgBase64, $mime, $rutaCarpeta, $id, $imageSize)
    {
        // Preparar datos de imagen        
        $rutaImagen = 'data:' . $mime . ';base64,' . $imgBase64;        

        // Crear el directorio de destino si no existe
        if (!is_dir($rutaCarpeta)) {
            mkdir($rutaCarpeta, 0755, true);
        }

        // Determinar el nombre del archivo
        $fileName = $id.'.jpg';

        // Procesar imagen
        try {
            $jpegBase64P = self::convertToJpeg($rutaImagen);
            $imagen = self::resizeImageBinary($jpegBase64P, $imageSize);
            self::saveBinaryImageToFile($imagen, $rutaCarpeta, $fileName);
        } catch (Exception $e) {
            //throw new Exception('Error al procesar la imagen: ' . $e->getMessage());
            return false;
        }
        return true;
    }

    // fin clase
}

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$url = 'https://www.google.com'; // Una URL simple para probar conectividad HTTPS
echo "Intentando conectar a: " . $url . "<br>";

if (function_exists('curl_init')) {
    echo "cURL está disponible.<br>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Mantener activado para seguridad
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);   // Mantener activado

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        echo "Error cURL desde PHP: " . htmlspecialchars($error) . "<br>";
    } else {
        echo "Código de respuesta HTTP desde PHP cURL: " . $httpcode . "<br>";
        echo "Respuesta (primeros 200 caracteres): <br><pre>" . htmlspecialchars(substr($response, 0, 200)) . "...</pre><br>";
    }
} else {
    echo "cURL NO está disponible en PHP.<br>";
    echo "Intentando con file_get_contents...<br>";
    if (ini_get('allow_url_fopen')) {
        echo "allow_url_fopen está ON.<br>";
        $response_fgc = file_get_contents($url);
        if ($response_fgc === false) {
            echo "file_get_contents falló.<br>";
            print_r(error_get_last());
        } else {
            echo "file_get_contents tuvo éxito (primeros 200 caracteres): <br><pre>" . htmlspecialchars(substr($response_fgc, 0, 200)) . "...</pre><br>";
        }
    } else {
        echo "allow_url_fopen está OFF.<br>";
    }
}
?>
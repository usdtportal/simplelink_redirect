<?php
error_reporting(0);
ini_set('display_errors', 0);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

$allowedDomainPattern = '/^https:\/\/([a-z0-9-]+\.)?usdtportal\.com$/i';

if (preg_match($allowedDomainPattern, $origin)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Credentials: true");
}

$fileName = __DIR__ . '/simplelink_access.php';


if (!isset($_POST['username'], $_POST['api_key'])) {
    exit(json_encode(['ERROR' => 'Mising username and api_key params']));
}

if (file_exists($fileName)) {
    require $fileName;
} else {
    $username = $_POST['username'];
    $apiKey = $_POST['api_key'];

    $fileContent = "<?php\n";
    $fileContent .= "\$username = '" . addslashes($username) . "';\n";
    $fileContent .= "\$api_key = '" . addslashes($apiKey) . "';\n";

    if (!file_put_contents($fileName, $fileContent) !== false) {
        exit(json_encode(['status' => 'error', 'message' => 'Failed to write simplelink_access.php.']));
    }
}

if ($_POST['username'] !== $username || $_POST['api_key'] !== $api_key) {
    exit(json_encode(['ERROR' => 'Missing or incorrect credentials']));
}




if (isset($_POST['api_url'])) {
    $api_url = $_POST['api_url'];
}
$method = isset($_POST['method']) ? strtoupper($_POST['method']) : 'GET';
$params = isset($_POST['parameters']) ? json_decode($_POST['parameters'], true) : [];

if (!isset($api_url) ) {
    exit(json_encode(['ERROR' => 'Missing required parameters: api_url']));
}

$response = sendRequest($api_url, $method, $params);

header("Content-Type: application/json; charset=utf-8");
http_response_code($response['http_code']);


if ($_POST['simplelink_test']) {
    $response['response'] .= "This is SimpleLink API TEST<br>";
    $response['response'] .= "<br>Yours server IPv4 is: ".getServerIP();
    $response['response'] .= "<br>Yours server IPv6 is: ".getServerIPv6();
    $response['response'] .= "<br><br><br>";;
}

echo json_encode([
    'response' => $response['response'],
    'http_code' => $response['http_code']
]);



function sendRequest($url, $method = 'GET', $params = []) {
    $curl = curl_init();

    try {
        if ($method === 'POST') {
            $postData = http_build_query($params);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        } else {
            $url = $url . '?' . http_build_query($params);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($curl);
        $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
        }

        return [
            'status' => 'success',
            'response' => $response,
            'http_code' => $httpStatusCode
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'error' => $e->getMessage(),
            'http_code' => null,
            'response' => null
        ];
    } finally {
        curl_close($curl);
    }
}



function getServerIP() {
    $ch = curl_init('https://api.ipify.org?format=json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response);
    return isset($json->ip) ? $json->ip : '0';
}

function getServerIPv6() {
    $ch = curl_init('https://api64.ipify.org?format=json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response);
    return isset($json->ip) ? $json->ip : '0';
}

?>

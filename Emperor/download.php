<?php
function yandexDiskRequest($token, $method, $url, $data = null) {
    $ch = curl_init();
    $headers = ['Authorization: OAuth ' . $token, 'Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_URL, 'https://cloud-api.yandex.net/v1/disk/' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

$token = 'y0__xDGwqSSCBjxizog6fyVqRQw_5Hysgh6b_fCpA3Nx9uJjalZpTXjwhqwIw';

if (isset($_GET['path'])) {
    $filePath = urldecode($_GET['path']);
    
    $result = yandexDiskRequest($token, 'GET', 'resources/download?path=' . urlencode($filePath));
    
    if ($result['code'] === 200 && isset($result['data']['href'])) {
        header('Location: ' . $result['data']['href']);
    } else {
        echo "Ошибка при получении ссылки для скачивания";
    }
} else {
    echo "Не указан путь к файлу";
}
exit;
?>
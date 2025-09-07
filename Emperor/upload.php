<?php
function yandexDiskRequest($token, $method, $url, $data = null) {
    $ch = curl_init();
    $headers = ['Authorization: OAuth ' . $token, 'Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_URL, 'https://cloud-api.yandex.net/v1/disk/' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') curl_setopt($ch, CURLOPT_POST, true);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

$token = 'y0__xDGwqSSCBjxizog6fyVqRQw_5Hysgh6b_fCpA3Nx9uJjalZpTXjwhqwIw';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $fileName = basename($_FILES['file']['name']);
    $result = yandexDiskRequest($token, 'GET', 'resources/upload?path=' . urlencode($fileName) . '&overwrite=true');
    
    if ($result['code'] === 200 && isset($result['data']['href'])) {
        $uploadUrl = $result['data']['href'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uploadUrl);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, fopen($_FILES['file']['tmp_name'], 'r'));
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($_FILES['file']['tmp_name']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $uploadResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 201) {
            header('Location: index.php?upload=success');
        } else {
            header('Location: index.php?error=Ошибка загрузки файла');
        }
    } else {
        header('Location: index.php?error=Не удалось получить URL для загрузки');
    }
} else {
    header('Location: index.php');
}
exit;
?>
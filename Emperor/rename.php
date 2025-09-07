<?php
function yandexDiskRequest($token, $method, $url, $data = null) {
    $ch = curl_init();
    $headers = ['Authorization: OAuth ' . $token, 'Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_URL, 'https://cloud-api.yandex.net/v1/disk/' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') curl_setopt($ch, CURLOPT_POST, true);
    if ($method === 'DELETE') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    if ($method === 'PUT') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

$token = 'y0__xDGwqSSCBjxizog6fyVqRQw_5Hysgh6b_fCpA3Nx9uJjalZpTXjwhqwIw';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['old_path']) && isset($_POST['new_name'])) {
    $oldPath = urldecode($_POST['old_path']);
    $newName = trim($_POST['new_name']);
    $fileInfo = yandexDiskRequest($token, 'GET', 'resources?path=' . urlencode($oldPath));
    
    if ($fileInfo['code'] !== 200) {
        header('Location: index.php?error=Файл не найден');
        exit;
    }

    $oldFileName = basename($oldPath);
    $fileExtension = pathinfo($oldFileName, PATHINFO_EXTENSION);

    $newFileName = $newName;
    if ($fileExtension && !pathinfo($newName, PATHINFO_EXTENSION)) {
        $newFileName = $newName . '.' . $fileExtension;
    }

    $pathParts = explode('/', $oldPath);
    array_pop($pathParts); 
    $newPath = implode('/', $pathParts) . '/' . $newFileName;

    if ($oldPath === $newPath) {
        header('Location: index.php?error=Новое имя совпадает со старым');
        exit;
    }

    $result = yandexDiskRequest($token, 'POST', 'resources/move?from=' . urlencode($oldPath) . 
                                              '&path=' . urlencode($newPath) . '&overwrite=true');
    
    if ($result['code'] === 201 || $result['code'] === 202) {
        header('Location: index.php?update=success');
    } else {
        $error = $result['data']['message'] ?? 'Ошибка переименования';
        header('Location: index.php?error=' . urlencode($error));
    }
} else {
    header('Location: index.php?error=Неверные параметры запроса');
}
exit;
?>
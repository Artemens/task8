<?php
function yandexDiskRequest($token, $method, $url, $data = null) {
    $ch = curl_init();
    
    $headers = [
        'Authorization: OAuth ' . $token,
        'Content-Type: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_URL, 'https://cloud-api.yandex.net/v1/disk/' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

$token = 'y0__xDGwqSSCBjxizog6fyVqRQw_5Hysgh6b_fCpA3Nx9uJjalZpTXjwhqwIw';

$result = yandexDiskRequest($token, 'GET', 'resources?path=/&limit=100');

if ($result['code'] === 200 && isset($result['data']['_embedded']['items'])) {
    $files = $result['data']['_embedded']['items'];
} else {
    $error = "Ошибка при получении файлов: " . ($result['data']['message'] ?? 'Неизвестная ошибка');
}

$message = '';
if (isset($_GET['upload']) && $_GET['upload'] === 'success') {
    $message = 'Файл успешно загружен!';
} elseif (isset($_GET['delete']) && $_GET['delete'] === 'success') {
    $message = 'Файл успешно удален!';
} elseif (isset($_GET['update']) && $_GET['update'] === 'success') {
    $message = 'Файл успешно переименован!';
} elseif (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD приложение Империй!</title>
    <style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
    background: linear-gradient(135deg, #000000 0%, #1a1a1a 25%, #2d2d2d 50%, #1a1a1a 75%, #000000 100%);
    color: #ffffff;
    min-height: 100vh;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    background: rgba(0, 0, 0, 0.8);
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(255, 69, 0, 0.3);
    border: 1px solid #ff4500;
}

.success {
    color: #4cff4c;
    padding: 12px;
    background: rgba(0, 100, 0, 0.2);
    border: 1px solid #32cd32;
    border-radius: 6px;
    margin-bottom: 20px;
    text-shadow: 0 0 5px rgba(76, 255, 76, 0.3);
    box-shadow: 0 0 10px rgba(0, 255, 0, 0.1);
}

.error {
    color: #ff6b6b;
    padding: 12px;
    background: rgba(139, 0, 0, 0.2);
    border: 1px solid #ff4757;
    border-radius: 6px;
    margin-bottom: 20px;
    text-shadow: 0 0 5px rgba(255, 71, 87, 0.3);
    box-shadow: 0 0 10px rgba(255, 0, 0, 0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: rgba(30, 30, 30, 0.9);
    border-radius: 8px;
    overflow: hidden;
}

table, th, td {
    border: 1px solid #ff5722;
}

th, td {
    padding: 14px;
    text-align: left;
}

th {
    background: linear-gradient(45deg, #ff6b35, #ff4500);
    color: #000;
    font-weight: bold;
    text-shadow: 0 0 2px rgba(255, 255, 255, 0.5);
}

.btn {
    padding: 8px 16px;
    margin: 4px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: bold;
    transition: all 0.3s ease;
    text-shadow: 0 0 2px rgba(0, 0, 0, 0.5);
    box-shadow: 0 0 10px rgba(255, 107, 53, 0.3);
}

.btn-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
}

.btn-danger {
    background: linear-gradient(45deg, #dc3545, #c82333);
    color: white;
}

.btn-success {
    background: linear-gradient(45deg, #28a745, #1e7e34);
    color: white;
}

.btn-warning {
    background: linear-gradient(45deg, #ffc107, #e0a800);
    color: black;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 15px rgba(255, 140, 0, 0.5);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
}

.modal-content {
    background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
    margin: 15% auto;
    padding: 25px;
    width: 320px;
    border-radius: 10px;
    box-shadow: 0 0 30px rgba(255, 69, 0, 0.4);
    border: 2px solid #ff4500;
    color: #ffffff;
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Империум</h1>
        
        <?php if ($message): ?>
            <div class="success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h2>Загрузить файл</h2>
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <input type="file" name="file" required>
                <button type="submit" class="btn btn-primary">Загрузить</button>
            </form>
        </div>
        <div class="files-list">
            <h2>Список файлов</h2>
            <?php if (!empty($files)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Тип</th>
                            <th>Размер</th>
                            <th>Дата изменения</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= $item['type'] == 'dir' ? 'Папка' : 'Файл' ?></td>
                                <td><?= $item['type'] == 'file' ? formatSize($item['size']) : '-' ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($item['modified'])) ?></td>
                                <td>
                                    <?php if ($item['type'] == 'file'): ?>
                                        <a href="download.php?path=<?= urlencode($item['path']) ?>" 
                                           class="btn btn-primary" title="Скачать">⬇️</a>

                                        <button onclick="showRenameModal('<?= urlencode($item['path']) ?>', '<?= htmlspecialchars($item['name']) ?>')" 
                                                class="btn btn-warning" title="Переименовать">✏️</button>

                                        <a href="delete.php?path=<?= urlencode($item['path']) ?>" 
                                           class="btn btn-danger" title="Удалить" 
                                           onclick="return confirm('Удалить файл <?= htmlspecialchars($item['name']) ?>?')">🗑️</a>
                                    <?php else: ?>
                                        <span class="btn" title="Действия для папок недоступны">📁</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>На диске нет файлов</p>
            <?php endif; ?>
        </div>
    </div>
<div id="renameModal" class="modal">
    <div class="modal-content">
        <h3>✏️ Переименовать файл</h3>
        <form id="renameForm" action="rename.php" method="post">
            <input type="hidden" id="oldPath" name="old_path">
            <div style="margin: 10px 0;">
                <div style="display: flex; align-items: center;">
                    <input type="text" id="newName" name="new_name" required 
                           style="flex: 1; padding: 5px; margin: 5px 0;">
                    <span id="fileExtension" style="padding: 5px; background:rgb(0, 0, 0); border-radius: 3px;"></span>
                </div>
                <small style="color: #666;">Расширение файла сохраняется автоматически</small>
            </div>
            <div style="text-align: right;">
                <button type="button" onclick="hideModal()" class="btn btn-danger">Отмена</button>
                <button type="submit" class="btn btn-success">Переименовать</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRenameModal(path, currentName) {
    var fileNameWithoutExt = currentName;
    var fileExtension = '';

    var lastDotIndex = currentName.lastIndexOf('.');
    if (lastDotIndex > 0) {
        fileNameWithoutExt = currentName.substring(0, lastDotIndex);
        fileExtension = currentName.substring(lastDotIndex);
    }
    
    document.getElementById('oldPath').value = path;
    document.getElementById('newName').value = fileNameWithoutExt;
    document.getElementById('fileExtension').textContent = fileExtension;
    document.getElementById('renameModal').style.display = 'block';
    
    setTimeout(function() {
        document.getElementById('newName').focus();
    }, 100);
}

function hideModal() {
    document.getElementById('renameModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('renameModal')) {
        hideModal();
    }
}
</script>
</body>
</html>

<?php
function formatSize($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $index = 0;
    
    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
    }
    
    return round($size, 2) . ' ' . $units[$index];
}
?>
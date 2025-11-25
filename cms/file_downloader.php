<?php
// Папки для категорий
$uploadDirs = [
    'img' => '../files/img/',
    'music' => '../files/music/',
    'files' => '../files/files/'
];

// Создаём папки, если их нет
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $file = $_FILES['file'] ?? null;

    if ($file && isset($uploadDirs[$category])) {
        $targetDir = $uploadDirs[$category];
        $targetFile = $targetDir . basename($file['name']);

        // Ограничение по размеру (например, 1000 МБ)
        $maxFileSize = 1000 * 1024 * 1024;
        if ($file['size'] > $maxFileSize) {
            $message = "Файл слишком большой!";
        } else {
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $message = "Файл успешно загружен в категорию '$category'.";
            } else {
                $message = "Ошибка при загрузке файла.";
            }
        }
    } else {
        $message = "Выберите файл и категорию!";
    }
}
?>

<?php
// Папки для категорий
$uploadDirs = [
    'img' => '../files/img/',
    'music' => '../files/music/',
    'files' => '../files/files/'
];

// Выбранная категория (по умолчанию — все)
$selectedCategory = $_GET['category'] ?? 'all';

// Функция для получения файлов из папки
function getFilesFromDir($dir) {
    $files = [];
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $files[] = $file;
        }
    }
    return $files;
}

// Функция безопасного удаления файла
if (isset($_GET['delete']) && isset($_GET['category'])) {
    $category = $_GET['category'];
    $file = $_GET['delete'];
    if (isset($uploadDirs[$category])) {
        $filePath = $uploadDirs[$category] . $file;
        if (file_exists($filePath)) {
            unlink($filePath);
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // Перезагрузка страницы без GET
            exit;
        }
    }
}

// Функция скачивания файла
if (isset($_GET['download']) && isset($_GET['category'])) {
    $category = $_GET['category'];
    $file = $_GET['download'];
    if (isset($uploadDirs[$category])) {
        $filePath = $uploadDirs[$category] . $file;
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
}

// Собираем файлы
$filesList = [];
if ($selectedCategory === 'all') {
    foreach ($uploadDirs as $category => $dir) {
        $filesList[$category] = getFilesFromDir($dir);
    }
} elseif (isset($uploadDirs[$selectedCategory])) {
    $filesList[$selectedCategory] = getFilesFromDir($uploadDirs[$selectedCategory]);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Файловый менеджер</title>
<link rel="stylesheet" type="text/css" href="../editor.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
</head>
<body>

    <div class="header">
        <h2><a href="editor_list.php">Editor/</a><h3 class="new">Файловый менеджер</h2>
    </div>

<?php if($message): ?>
<p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<div class="block_admin_file">
        <div class="left">
            <form action="" method="post" enctype="multipart/form-data">
                <label>Выберите категорию:</label>
                <select name="category">
                    <option value="img">Медиа</option>
                    <option value="music">Музыка</option>
                    <option value="files">Файлы</option>
                </select>
                <br><br>
                <label>Выберите файл:</label>
                <input type="file" name="file">
                <br><br>
                <button type="submit">Загрузить</button>
            </form>
        </div>
        <div class="right">
            <!-- Фильтр по категории -->
            <form method="get">
                <label>Категория:</label>
                <select name="category" onchange="this.form.submit()">
                    <option value="all" <?= $selectedCategory === 'all' ? 'selected' : '' ?>>Все</option>
                    <option value="img" <?= $selectedCategory === 'img' ? 'selected' : '' ?>>Изображения</option>
                    <option value="music" <?= $selectedCategory === 'music' ? 'selected' : '' ?>>Музыка</option>
                    <option value="files" <?= $selectedCategory === 'files' ? 'selected' : '' ?>>Файлы</option>
                </select>
            </form>

        </div>
</div>

<?php if(empty($filesList)): ?>
<p>Файлов нет.</p>
<?php else: ?>
<?php foreach($filesList as $category => $files): ?>
    <h3><?= htmlspecialchars($category) ?></h3>
    <?php if(empty($files)): ?>
        <p>Файлов нет.</p>
    <?php else: ?>
        <ul>
        <?php foreach($files as $file): ?>
            <div class="link_editor_notes">
            <div class="left">
                <a href="uploads/<?= $category ?>/<?= urlencode($file) ?>" target="_blank">
                    <?= htmlspecialchars($file) ?>
                </a>
            </div>
            <div class="right">
                [<a href="?category=<?= urlencode($category) ?>&delete=<?= urlencode($file) ?>" onclick="return confirm('Удалить файл <?= addslashes($file) ?>?')" style="color: #840707;">x</a>]
                [<a href="?category=<?= urlencode($category) ?>&download=<?= urlencode($file) ?>">Скачать</a>] 
            </div>
        </div>
        <?php endforeach; ?>
        </ul>



    <?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>


</body>
</html>

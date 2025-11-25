<?php
// Папка для сохранения заметок
$dir = '../notes/';

// Создаём папку, если её нет
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Получаем данные
$filename = isset($_POST['filename']) ? trim($_POST['filename']) : '';
$text = isset($_POST['text']) ? $_POST['text'] : '';
$check = isset($_POST['check']); // проверка существования файла

if ($filename === '') {
    echo 'ERROR';
    exit;
}

// Добавляем .txt если его нет
if (!str_ends_with($filename, '.txt')) {
    $filename .= '.txt';
}

// Защита от нежелательных символов в имени файла (с поддержкой русских букв)
$filename = preg_replace('/[^\p{L}0-9_\-\.]/u', '_', $filename);

// Путь к файлу
$filepath = $dir . $filename;

// Если проверка существования
if ($check) {
    if (file_exists($filepath)) {
        echo 'EXISTS';
    } else {
        echo 'NO';
    }
    exit;
}

// Сохраняем текст
if (file_put_contents($filepath, $text) !== false) {
    echo 'OK';
} else {
    echo 'ERROR';
}
?>

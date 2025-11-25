<?php
define('ROOT_DIR', realpath(dirname(__FILE__) . '/../') . '/'); // корень проекта
define('CONTENT_DIR', ROOT_DIR . 'notes/'); // папка notes в корне
$default_title = 'defenders08';
$file_format = ".txt";

function list_articles($dir) {
    $files = scandir($dir);
    $articles = [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . $file;
        if (is_dir($path)) {
            $articles = array_merge($articles, list_articles($path));
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'txt') {
            // возвращаем только имя файла без расширения
            $articles[] = pathinfo($file, PATHINFO_FILENAME);
        }
    }
    return $articles;
}

// пример использования
$articles = list_articles(CONTENT_DIR);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editor - defenders08</title>
    <link rel="stylesheet" type="text/css" href="../editor.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <h2><a href="editor_list.php">Editor/</a></h2><a href="editor.php" class="new">Создать статью</a>
    </div>
    <a href="file_downloader.php">Файловый менеджер</a>

    <div class="content">
        <div class="list">
            <div class="list">
<?php foreach($articles as $a): ?>
    <div class="link_editor_notes" data-file="<?php echo htmlspecialchars($a); ?>">
        <div class="left">
            <a href="/notes/<?php echo urlencode($a . '.txt'); ?>">[<?php echo htmlspecialchars($a); ?>]</a>
        </div>
        <div class="right">
            <a href="#" class="delete-file" style="color: #840707;">[x]</a>
            <a href="editor.php?file=<?php echo urlencode($a); ?>" data-tag="edit">[изменить]</a>
            <a href="/notes/<?php echo urlencode($a . '.txt'); ?>" download>[скачать]</a>
        </div>
    </div>
<?php endforeach; ?>
</div>

<script>
    document.querySelectorAll('.delete-file').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();

        const fileDiv = this.closest('.link_editor_notes');
        const fileName = fileDiv.getAttribute('data-file');

        if(!fileName) return;

        if(confirm(`Удалить файл "${fileName}"? Это действие нельзя отменить.`)) {
            fetch('delete_note.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'file=' + encodeURIComponent(fileName)
            })
            .then(res => res.text())
            .then(res => {
                if(res === 'OK') {
                    fileDiv.remove();
                } else if(res === 'NOTFOUND') {
                    alert('Файл не найден.');
                } else {
                    alert('Ошибка при удалении файла.');
                }
            })
            .catch(() => alert('Ошибка запроса на сервер.'));
        }
    });
});

</script>




</div>

        </div>
    </div>

</body>
</html>

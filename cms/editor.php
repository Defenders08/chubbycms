<?php
define('ROOT_DIR', realpath(dirname(__FILE__) . '/../') . '/');
define('CONTENT_DIR', ROOT_DIR . 'notes/');

$file_name = '';
$content = '';

// Считываем имя файла из GET
if (!empty($_GET['file'])) {
    $file_name = basename($_GET['file']); // защита от ../ атак
    $file_path = CONTENT_DIR . $file_name . '.txt';

    // Если файл существует, читаем его содержимое
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editor - <?php echo htmlspecialchars($file_name); ?></title>
    <link rel="stylesheet" type="text/css" href="../editor.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
    <h2><a href="editor_list.php">Editor/</a></h2>
    <input placeholder="Название файла" id="name_note" value="<?php echo htmlspecialchars($file_name); ?>">
    <button data-tag="save">Сохранить локально (.txt)</button>
<button id="save_server">Сохранить на сервер</button>
</div>

    <div class="content">
        <div class="left">
            <div class="editor">
                <div class="editor_res" id="editor_res">
                <!-- сюда приходит HTML из parser.php -->
                </div>

                <div class="editor_btns">
                    <button data-tag="tab">tab</button>
                    <button data-tag="img_little">img_little</button>
                    <button data-tag="a">ссылка</button>
                    <button data-tag="список">список</button>
                    <button data-tag="h3">h3</button>
                </div>
            </div>
        </div>

        <div class="right">
            <div class="editor_prewiew">
                <textarea id="editor_textarea"><?php echo htmlspecialchars($content); ?></textarea>

            </div>
        </div>
    </div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const textarea = document.getElementById("editor_textarea");
    const resBlock = document.getElementById("editor_res");
    const buttons = document.querySelectorAll(".editor_btns button");

    // Если есть файл из PHP, подставляем его в textarea
    if (!textarea.value) {
        textarea.value = ""; // пустое значение, если нет файла
    }

    // Вставка шаблонов по кнопкам
    buttons.forEach(btn => {
        btn.addEventListener("click", () => {
            const tag = btn.dataset.tag;
            let insertText = "";

            if (tag === "tab") {
                insertText = "[tab]\n\n[/tab]";
            } else if (tag === "img_little") {
                insertText = "[img_little](url)Описание под картинкой[/img_little]";
            } else if (tag === "a") {
                insertText = "[a](http://example.com)Текст ссылки[/a]";
            } else if (tag === "список") {
                insertText = "[ul]\n[li]пункт[/li]\n[li]пункт2[/li]\n[/ul]";
            } else {
                insertText = `[${tag}]\n\n[/${tag}]`;
            }

            // вставка текста именно в позицию курсора
const startPos = textarea.selectionStart;
const endPos = textarea.selectionEnd;

// текст до курсора + вставка + текст после курсора
textarea.value = textarea.value.substring(0, startPos) 
               + insertText 
               + textarea.value.substring(endPos);

// обновляем позицию курсора после вставки
textarea.selectionStart = textarea.selectionEnd = startPos + insertText.length;

// вызываем парсер и фокус
parseText();
textarea.focus();

        });
    });

    // Обработчик ввода с debounce
    let timer = null;
    textarea.addEventListener("input", () => {
        clearTimeout(timer);
        timer = setTimeout(parseText, 250);
    });

    // Первичное парсирование
    parseText();

    function parseText() {
    const text = textarea.value.trim();

    if (!text) {
        resBlock.innerHTML = "<i>Предпросмотр появится здесь, когда ты что-то напишешь...</i>";
        return;
    }

    fetch("parser_editor.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"},
        body: "text=" + encodeURIComponent(text)
    })
    .then(r => r.text())
    .then(html => {
        resBlock.innerHTML = html;

        // Делаем все блоки редактируемыми, без разрушения структуры
        resBlock.querySelectorAll('.editor_res .block,.editor_res .text,.editor_res .media,.editor_res p,.editor_res h3,.editor_res li,.editor_res cite').forEach(el => {
            el.setAttribute('contenteditable', 'true');

            // Событие input — сохраняем прямо HTML блока в textarea
            el.addEventListener('input', () => {
                textarea.value = resBlock.innerHTML;
            });
        });
    })
    .catch(e => {
        resBlock.innerHTML = "<b>Ошибка связи с parser.php:</b><br>" + e;
        console.error(e);
    });
}

});
</script>


<script>
// Сохранение локально
document.querySelector('button[data-tag="save"]').addEventListener('click', function() {
    const text = document.getElementById('editor_textarea').value;
    let filename = document.getElementById('name_note').value.trim();
    
    if (!filename) {
        alert("Пожалуйста, укажи название файла!");
        return;
    }
    
    if (!filename.endsWith('.txt')) filename += '.txt';
    
    const blob = new Blob([text], { type: 'text/plain' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
});

// Сохранение на сервер через PHP с проверкой существования
document.getElementById('save_server').addEventListener('click', function() {
    const text = document.getElementById('editor_textarea').value;
    const filenameInput = document.getElementById('name_note').value.trim();
    
    if (!filenameInput) {
        alert("Пожалуйста, укажи название файла!");
        return;
    }

    let filename = filenameInput;
    if (!filename.endsWith('.txt')) filename += '.txt';

    // Проверяем, существует ли файл на сервере
    fetch('save_note.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'check=1&filename=' + encodeURIComponent(filename)
    })
    .then(res => res.text())
    .then(exists => {
        if (exists === 'EXISTS') {
            if (!confirm(`Файл "${filename}" уже существует. Перезаписать?`)) {
                return;
            }
        }

        // Сохраняем на сервер
        fetch('save_note.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'filename=' + encodeURIComponent(filename) + '&text=' + encodeURIComponent(text)
        })
        .then(res => res.text())
        .then(data => alert(data))
        .catch(err => alert('Ошибка: ' + err));
    })
    .catch(err => alert('Ошибка проверки файла: ' + err));
});
</script>

</body>
</html>

<?php
define('ROOT_DIR', realpath(dirname(__FILE__)) . '/');
define('CONTENT_DIR', ROOT_DIR . 'notes/');
$default_title = 'главная';
$file_format = ".txt";

include "cms/parser.php";

// =====================
//  ОПРЕДЕЛЕНИЕ URL
// =====================
$url = '';
$request_url = $_SERVER['REQUEST_URI'] ?? '';
$script_url  = $_SERVER['PHP_SELF'] ?? '';

if($request_url != $script_url) {
    $url = trim(
        preg_replace(
            '/'. str_replace('/', '\/', str_replace('index.php', '', $script_url)) .'/', 
            '', 
            $request_url, 
            1
        ),
        '/'
    );

    // Раскодируем URL, чтобы русские буквы работали
    $url = urldecode($url);
}

// =====================
//  КАКОЙ ФАЙЛ ГРУЗИМ
// =====================
if($url) {
    $file = CONTENT_DIR . $url . $file_format;
} else {
    // по умолчанию грузим статью $default_title
    $file = CONTENT_DIR . $default_title . $file_format;
}

if(file_exists($file)) $content_raw = file_get_contents($file);
else                   $content_raw = file_get_contents(CONTENT_DIR . '404' . $file_format);

// Парсим txt → HTML  
$content = parseCustomTags($content_raw);

// =====================
//  Список статей
// =====================
function list_articles($dir) {
    $files = scandir($dir);
    $articles = [];
    foreach($files as $file) {
        if($file === '.' || $file === '..') continue;
        $path = $dir . $file;
        if(is_dir($path)) {
            $articles = array_merge($articles, list_articles($path));
        } elseif(pathinfo($path, PATHINFO_EXTENSION) === 'txt') {
            // Обрезаем путь и оставляем название файла
            $articles[] = htmlspecialchars(
                str_replace([CONTENT_DIR, '.txt'], '', $path), 
                ENT_QUOTES, 
                'UTF-8'
            );
        }
    }
    return $articles;
}

$articles_list = list_articles(CONTENT_DIR);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo ($url ? htmlspecialchars($url, ENT_QUOTES, 'UTF-8') : $default_title); ?> - defenders08</title>
	<link rel="stylesheet" type="text/css" href="style.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
</head>
<body>
	<div class="body">
		<div class="header">
			<div class="left">
				<a href="/главная">defenders08</a>
			</div>
			<div class="right">
				<a href="/музыка">музыка</a>
				<a href="#">статьи</a>
				<a href="#">файлопомойка</a>
			</div>
		</div>

		<div class="content">
			<div class="left">
                <div class="markdown" style="width: 488px; display: flex; flex-direction: column; gap: 10px;">
                	<style>
						
				</style>
                    <?php echo $content; ?>
                </div>
            </div>

			<div class="right">
				<div class="name">Последнее</div>
				<div class="list">
					<?php foreach($articles_list as $a): ?>
						<a href="/<?php echo urlencode($a); ?>"><?php echo $a; ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="footer">
			<div class="left">
				<a href="#">контакты</a>
			</div>
			<div class="right">
				design by defenders08
			</div>
		</div>
	</div>
	<script>
		document.querySelectorAll('a').forEach(a => {
    a.innerText = `[${a.innerText}]`;
});

	</script>
</body>
</html>

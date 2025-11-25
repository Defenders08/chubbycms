<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: text/html; charset=utf-8");

$text = $_POST['text'] ?? '';

/**
 * Полноценный парсер с блоками, медиа и текстом
 */
function parseCustomTags(string $text): string {
    $media_store = [];
    $text_store  = [];

    // 1) [img_little](src)desc[/img_little] → __MEDIA_n__
    $text = preg_replace_callback(
        '/\[img_little\]\((.*?)\)(.*?)\[\/img_little\]/s',
        function($m) use (&$media_store) {
            $id = count($media_store) + 1;
            $media_store[$id] = [
                'src' => trim($m[1]),
                'desc_raw' => trim($m[2])
            ];
            return "__MEDIA_{$id}__";
        },
        $text
    );

    // 2) [text]...[/text] → __TEXT_n__, внутри [tab] → <cite>
    $text = preg_replace_callback(
        '/\[text\](.*?)\[\/text\]/s',
        function($m) use (&$text_store) {
            $inner = preg_replace_callback(
                '/\[tab\](.*?)\[\/tab\]/s',
                function($tm){ return '<cite>'.trim($tm[1]).'</cite>'; },
                $m[1]
            );
            $id = count($text_store) + 1;
            $text_store[$id] = trim($inner);
            return "__TEXT_{$id}__";
        },
        $text
    );

    // 3) Простые заголовки
    $text = preg_replace('/\[h3\](.*?)\[\/h3\]/s', '<h3>$1</h3>', $text);

    // 4) [tab]...[/tab] → <div class="block">
    $text = preg_replace_callback(
        '/\[tab\](.*?)\[\/tab\]/s',
        function($m) use (&$media_store, &$text_store) {
            $inside = $m[1];
            preg_match_all('/__MEDIA_(\d+)__|__TEXT_(\d+)__/', $inside, $matches, PREG_SET_ORDER);

            $parts = [];
            foreach ($matches as $match) {
                if (!empty($match[1])) $parts[] = ['type'=>'media','id'=>(int)$match[1]];
                elseif (!empty($match[2])) $parts[] = ['type'=>'text','id'=>(int)$match[2]];
            }

            if (!empty($parts)) {
                $html = '<div class="block">';
                foreach ($parts as $p) {
                    if ($p['type']==='media' && isset($media_store[$p['id']])) {
                        $mdata = $media_store[$p['id']];
                        $desc = $mdata['desc_raw'];
                        // Проверяем наличие __TEXT_N__ в описании
                        if (preg_match_all('/__TEXT_(\d+)__/', $desc, $t_matches)) {
                            $desc_clean = preg_replace('/__TEXT_(\d+)__/', '', $desc);
                            $desc_clean = trim($desc_clean);
                            $html .= '<div class="media"><img class="little_img" src="'.htmlspecialchars($mdata['src'],ENT_QUOTES).'">'
                                   . ($desc_clean!==''?'<div class="media_description">'.$desc_clean.'</div>':'')
                                   .'</div>';
                            foreach ($t_matches[1] as $tid) {
                                if(isset($text_store[(int)$tid])) {
                                    $html .= '<div class="text">'.$text_store[(int)$tid].'</div>';
                                    unset($text_store[(int)$tid]);
                                }
                            }
                        } else {
                            $html .= '<div class="media"><img class="little_img" src="'.htmlspecialchars($mdata['src'],ENT_QUOTES).'">'
                                   . ($desc!==''?'<div class="media_description">'.$desc.'</div>':'')
                                   .'</div>';
                        }
                        unset($media_store[$p['id']]);
                    } elseif ($p['type']==='text' && isset($text_store[$p['id']])) {
                        $html .= '<div class="text">'.$text_store[$p['id']].'</div>';
                        unset($text_store[$p['id']]);
                    }
                }
                $html .= '</div>';
                return $html;
            }
            return '<cite>'.trim($inside).'</cite>';
        },
        $text
    );

    // 5) Подставляем оставшиеся медиа
    foreach ($media_store as $id=>$m) {
        $text = str_replace("__MEDIA_{$id}__", '<div class="media"><img class="little_img" src="'.htmlspecialchars($m['src'],ENT_QUOTES).'">'
            .($m['desc_raw']!==''?'<div class="media_description">'.$m['desc_raw'].'</div>':'')
            .'</div>', $text);
    }

    // 6) Подставляем оставшиеся тексты
    foreach ($text_store as $id=>$t) {
        $text = str_replace("__TEXT_{$id}__", '<div class="text">'.$t.'</div>', $text);
    }

    // 7) Списки
    $text = str_replace(['[ul]','[/ul]'], ['<ul>','</ul>'], $text);
    $text = str_replace(['[li]','[/li]'], ['<li>','</li>'], $text);

    return $text;
}

// Выводим результат
echo parseCustomTags($text);

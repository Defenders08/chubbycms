<?php
function parseCustomTags(string $text): string {
    // хранилища
    $media_store = [];
    $text_store  = [];

    // 0) Обрабатываем ссылки [a](url)Текст[/a] -> <a href="url">Текст</a>
    $text = preg_replace_callback(
        '/\[a\]\((.*?)\)(.*?)\[\/a\]/s',
        function($m) {
            $url  = trim($m[1]);
            $label = trim($m[2]);
            return '<a href="'.htmlspecialchars($url, ENT_QUOTES).'" target="_blank">'.htmlspecialchars($label).'</a>';
        },
        $text
    );

    // 1) Заменяем [img_little](src)desc[/img_little] -> токен __MEDIA_n__
    $text = preg_replace_callback(
        '/\[img_little\]\((.*?)\)(.*?)\[\/img_little\]/s',
        function($m) use (&$media_store) {
            $src  = trim($m[1]);
            $desc = trim($m[2]);
            $id = count($media_store) + 1;
            $media_store[$id] = [
                'src' => $src,
                'desc_raw' => $desc
            ];
            return "__MEDIA_{$id}__";
        },
        $text
    );

    // 2) Заменяем [text]...[/text] -> токен __TEXT_n__
    //    и внутри [text] обрабатываем [tab]...[/tab] -> <cite>
    $text = preg_replace_callback(
        '/\[text\](.*?)\[\/text\]/s',
        function($m) use (&$text_store) {
            $inner = $m[1];
            // внутри текста [tab] -> cite
            $inner = preg_replace_callback(
                '/\[tab\](.*?)\[\/tab\]/s',
                function($tm){
                    return '<cite>'.trim($tm[1]).'</cite>';
                },
                $inner
            );
            $id = count($text_store) + 1;
            $text_store[$id] = trim($inner);
            return "__TEXT_{$id}__";
        },
        $text
    );

    // 3) Простейшие замены списков и заголовков
    $text = str_replace(['[ul]','[/ul]'], ['<ul>','</ul>'], $text);
    $text = str_replace(['[li]','[/li]'], ['<li>','</li>'], $text);
    $text = preg_replace('/\[h3\](.*?)\[\/h3\]/s', '<h3>$1</h3>', $text);

    // 4) Обрабатываем [tab]...[/tab] для блоков
    $text = preg_replace_callback(
        '/\[tab\](.*?)\[\/tab\]/s',
        function($m) use (&$media_store, &$text_store) {
            $inside = $m[1];

            preg_match_all('/__MEDIA_(\d+)__|__TEXT_(\d+)__/i', $inside, $matches, PREG_SET_ORDER);
            $parts = [];
            foreach ($matches as $match) {
                if (!empty($match[1])) $parts[] = ['type' => 'media', 'id' => (int)$match[1]];
                elseif (!empty($match[2])) $parts[] = ['type' => 'text', 'id' => (int)$match[2]];
            }

            if (!empty($parts)) {
                $html = '<div class="block">';
                foreach ($parts as $p) {
                    if ($p['type'] === 'media') {
                        $mdata = $media_store[$p['id']];
                        $desc_raw = $mdata['desc_raw'];

                        if (preg_match_all('/__TEXT_(\d+)__/', $desc_raw, $tmatches)) {
                            $desc_clean = preg_replace('/__TEXT_(\d+)__/', '', $desc_raw);
                            $desc_clean = trim($desc_clean);

                            $html .= '<div class="media">'
                                  .  '<img class="little_img" src="'.htmlspecialchars($mdata['src'], ENT_QUOTES). '">'
                                  .  ($desc_clean !== '' ? '<div class="media_description">'. $desc_clean .'</div>' : '')
                                  .  '</div>';

                            foreach ($tmatches[1] as $textId) {
                                $tcontent = $text_store[(int)$textId] ?? '';
                                $html .= '<div class="text">'. $tcontent .'</div>';
                                unset($text_store[(int)$textId]);
                            }
                        } else {
                            $html .= '<div class="media">'
                                  .  '<img class="little_img" src="'.htmlspecialchars($mdata['src'], ENT_QUOTES). '">'
                                  .  ($desc_raw !== '' ? '<div class="media_description">'. $desc_raw .'</div>' : '')
                                  .  '</div>';
                        }

                        unset($media_store[$p['id']]);
                    } else {
                        if (isset($text_store[$p['id']])) {
                            $html .= '<div class="text">'. $text_store[$p['id']] .'</div>';
                            unset($text_store[$p['id']]);
                        }
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
    foreach ($media_store as $id => $mdata) {
        $html = '<div class="media">'
              .  '<img class="little_img" src="'.htmlspecialchars($mdata['src'], ENT_QUOTES). '">'
              .  ($mdata['desc_raw'] !== '' ? '<div class="media_description">'. $mdata['desc_raw'] .'</div>' : '')
              .  '</div>';
        $text = str_replace("__MEDIA_{$id}__", $html, $text);
    }

    // 6) Подставляем оставшиеся тексты
    foreach ($text_store as $id => $t) {
        $html = '<div class="text">'. $t .'</div>';
        $text = str_replace("__TEXT_{$id}__", $html, $text);
    }

    // 7) Финальная замена списков и заголовков
    $text = str_replace(['[ul]','[/ul]'], ['<ul>','</ul>'], $text);
    $text = str_replace(['[li]','[/li]'], ['<li>','</li>'], $text);
    $text = preg_replace('/\[h3\](.*?)\[\/h3\]/s', '<h3>$1</h3>', $text);

    return $text;
}

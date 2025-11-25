<?php
$dir = '../notes/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['file'])) {
    $file = basename($_POST['file']); // защита от ../
    $filePath = $dir . $file . '.txt';

    if(file_exists($filePath)) {
        if(unlink($filePath)) {
            echo 'OK';
        } else {
            echo 'ERROR';
        }
    } else {
        echo 'NOTFOUND';
    }
} else {
    echo 'ERROR';
}

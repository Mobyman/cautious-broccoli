<?php

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

if ($argv[1]) {


    $fileName = __DIR__ . '/../src/test/resources/data/userpasswords.csv';
    file_put_contents($fileName, 'user,password' . PHP_EOL) ;

    for ($i = 0; $i < (int)$argv[1]; ++$i) {
        file_put_contents($fileName, generateRandomString() . ',' . generateRandomString() . PHP_EOL, FILE_APPEND);

        if ($i % 100 === 0) {
            echo $i . PHP_EOL;
        }
    }
}



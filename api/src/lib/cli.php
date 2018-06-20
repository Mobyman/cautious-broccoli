<?php


function cli_start($argc, $argv)
{

    if ($argv[1] === 'migrate') {
        echo 'Start migrate...' . PHP_EOL;
        $orders       = file_get_contents(__DIR__ . '/../data/orders.sql');
        $transactions = file_get_contents(__DIR__ . '/../data/transactions.sql');
        $users        = file_get_contents(__DIR__ . '/../data/users.sql');

        $r = mysqli_query(db_getConnection('order'), $orders);
        $r = $r && mysqli_query(db_getConnection('transaction'), $transactions);
        $r = $r && mysqli_query(db_getConnection('user'), $users);

        if (!$r) {
            echo 'Migrate failed!' . PHP_EOL;
            exit(1);
        }

        echo 'End migrate...' . PHP_EOL;

    }

}

;
